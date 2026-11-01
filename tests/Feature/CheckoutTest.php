<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cashier = User::factory()->cashier()->create();
        $this->admin = User::factory()->admin()->create();
        $this->category = Category::factory()->create(['is_active' => true]);
        $this->product = Product::factory()->create([
            'category_id' => $this->category->id,
            'name' => 'Adobo Rice Meal',
            'selling_price' => '100.00',
            'cost_price' => '50.00',
            'unit' => 'serving',
            'track_inventory' => true,
            'stock_quantity' => 10,
            'is_available' => true,
        ]);
    }

    private User $cashier;

    private User $admin;

    private Category $category;

    private Product $product;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2],
            ],
            'payment_method' => 'cash',
            'amount_tendered' => '250.00',
            'idempotency_key' => (string) Str::uuid(),
        ], $overrides);
    }

    public function test_cash_checkout_creates_sale_items_payment_and_stock_movement(): void
    {
        $response = $this->actingAs($this->cashier)->postJson(route('pos.checkout'), $this->payload());

        $response->assertOk()
            ->assertJsonPath('sale.total', '200.00')
            ->assertJsonPath('sale.change', '50.00')
            ->assertJsonPath('sale.payment_method', 'cash');

        $this->assertDatabaseHas('sales', [
            'cashier_id' => $this->cashier->id,
            'subtotal' => '200.00',
            'total' => '200.00',
            'amount_tendered' => '250.00',
            'change_amount' => '50.00',
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('sale_items', [
            'product_id' => $this->product->id,
            'product_name' => 'Adobo Rice Meal',
            'unit_price' => '100.00',
            'quantity' => '2.000',
            'line_total' => '200.00',
        ]);
        $this->assertDatabaseHas('payments', [
            'method' => 'cash',
            'amount' => '250.00',
            'status' => 'recorded',
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $this->product->id,
            'type' => 'sale',
            'quantity_change' => '-2.000',
            'quantity_before' => '10.000',
            'quantity_after' => '8.000',
        ]);
        $this->assertSame('8.000', $this->product->fresh()->stock_quantity);
    }

    public function test_server_ignores_client_supplied_prices_and_totals(): void
    {
        $payload = $this->payload([
            'total' => '1.00',
            'subtotal' => '1.00',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => '0.01',
                    'line_total' => '0.02',
                ],
            ],
        ]);

        $this->actingAs($this->cashier)
            ->postJson(route('pos.checkout'), $payload)
            ->assertOk()
            ->assertJsonPath('sale.total', '200.00');

        $this->assertDatabaseHas('sales', ['total' => '200.00']);
    }

    public function test_insufficient_cash_is_rejected_and_stock_is_unchanged(): void
    {
        $response = $this->actingAs($this->cashier)->postJson(route('pos.checkout'), $this->payload([
            'amount_tendered' => '150.00',
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors('amount_tendered');

        $this->assertDatabaseCount('sales', 0);
        $this->assertSame('10.000', $this->product->fresh()->stock_quantity);
    }

    public function test_insufficient_stock_is_rejected(): void
    {
        $response = $this->actingAs($this->cashier)->postJson(route('pos.checkout'), $this->payload([
            'items' => [['product_id' => $this->product->id, 'quantity' => 11]],
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors('items.0.quantity');

        $this->assertDatabaseCount('sales', 0);
        $this->assertSame('10.000', $this->product->fresh()->stock_quantity);
    }

    public function test_cashier_cannot_apply_a_discount(): void
    {
        $response = $this->actingAs($this->cashier)->postJson(route('pos.checkout'), $this->payload([
            'discount_type' => 'fixed',
            'discount_value' => '10.00',
            'amount_tendered' => '250.00',
        ]));

        $response->assertStatus(422);
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_admin_can_apply_a_discount_and_totals_use_server_math(): void
    {
        $this->actingAs($this->admin)->postJson(route('pos.checkout'), $this->payload([
            'discount_type' => 'percent',
            'discount_value' => '10',
            'amount_tendered' => '250.00',
        ]))->assertOk()
            ->assertJsonPath('sale.subtotal', '200.00')
            ->assertJsonPath('sale.discount', '20.00')
            ->assertJsonPath('sale.total', '180.00');

        $this->assertDatabaseHas('sales', [
            'subtotal' => '200.00',
            'discount_amount' => '20.00',
            'total' => '180.00',
        ]);
    }

    public function test_duplicate_idempotency_key_returns_the_same_sale_without_double_deduction(): void
    {
        $payload = $this->payload();

        $first = $this->actingAs($this->cashier)->postJson(route('pos.checkout'), $payload)->assertOk();
        $second = $this->actingAs($this->cashier)->postJson(route('pos.checkout'), $payload)->assertOk();

        $this->assertSame(
            $first->json('sale.reference'),
            $second->json('sale.reference')
        );
        $this->assertDatabaseCount('sales', 1);
        $this->assertSame('8.000', $this->product->fresh()->stock_quantity);
        $this->assertDatabaseCount('inventory_movements', 1);
    }

    public function test_digital_payment_is_recorded_not_claimed_as_verified(): void
    {
        $this->actingAs($this->cashier)->postJson(route('pos.checkout'), $this->payload([
            'payment_method' => 'gcash',
            'amount_tendered' => '200.00',
            'reference_number' => 'GC-123456',
            'change_amount' => '999',
        ]))->assertOk()
            ->assertJsonPath('sale.change', '0.00');

        $this->assertDatabaseHas('payments', [
            'method' => 'gcash',
            'reference_number' => 'GC-123456',
            'status' => 'recorded',
        ]);
    }

    public function test_other_payment_requires_a_label(): void
    {
        $response = $this->actingAs($this->cashier)->postJson(route('pos.checkout'), $this->payload([
            'payment_method' => 'other',
            'payment_label' => '',
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors('payment_label');
    }

    public function test_unavailable_product_cannot_be_sold(): void
    {
        $this->product->update(['is_available' => false]);

        $response = $this->actingAs($this->cashier)->postJson(route('pos.checkout'), $this->payload());

        $response->assertStatus(422);
        $this->assertDatabaseCount('sales', 0);
    }
}
