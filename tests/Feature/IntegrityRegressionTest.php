<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\SalesReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class IntegrityRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function order(Product $product, string $quantity = '1'): array
    {
        return ['items' => [['product_id' => $product->id, 'quantity' => $quantity]],
            'payment_method' => 'cash', 'amount_tendered' => '10000', 'idempotency_key' => (string) Str::uuid()];
    }

    private function productData(Product $product): array
    {
        return array_merge($product->only(['category_id', 'name', 'sku', 'selling_price', 'cost_price', 'unit', 'track_inventory', 'stock_quantity', 'is_available']), ['name' => 'Updated name']);
    }

    public function test_stale_product_form_does_not_restore_sold_stock(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['unit' => 'piece', 'stock_quantity' => 10, 'track_inventory' => true]);
        $stale = $this->productData($product);
        $this->actingAs($admin)->postJson(route('pos.checkout'), $this->order($product, '2'))->assertOk();
        $this->put(route('products.update', $product), $stale)->assertRedirect();
        $this->assertSame('8.000', $product->fresh()->stock_quantity);
        $this->assertDatabaseCount('inventory_movements', 1);
    }

    public function test_checkout_rejects_unrepresentable_or_fractional_whole_quantities(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['unit' => 'piece', 'stock_quantity' => 10]);
        foreach (['1.0004', '0.0004', '1.5'] as $quantity) {
            $this->actingAs($admin)->postJson(route('pos.checkout'), $this->order($product, $quantity))
                ->assertUnprocessable()->assertJsonValidationErrors('items.0.quantity');
        }
        $product->update(['unit' => 'kilogram']);
        foreach (['0.0004', '1.1234'] as $quantity) {
            $this->postJson(route('pos.checkout'), $this->order($product, $quantity))
                ->assertUnprocessable()->assertJsonValidationErrors('items.0.quantity');
        }
        $this->postJson(route('pos.checkout'), $this->order($product, '0.125'))->assertOk();
        $this->assertSame('0.125', Sale::firstOrFail()->items->first()->quantity);
    }

    public function test_whole_unit_inventory_rejects_fractional_adjustments(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['unit' => 'piece', 'track_inventory' => true, 'stock_quantity' => 10]);
        foreach (['stock_in', 'stock_out', 'adjustment'] as $type) {
            $this->actingAs($admin)->postJson(route('inventory.adjust', $product), ['type' => $type, 'quantity' => '0.5', 'reason' => 'Count'])
                ->assertUnprocessable()->assertJsonValidationErrors('quantity');
        }
        $this->assertSame('10.000', $product->fresh()->stock_quantity);
    }

    public function test_whole_unit_product_creation_rejects_fractional_stock(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();
        $this->actingAs($admin)->postJson(route('products.store'), [
            'category_id' => $category->id, 'name' => 'Test', 'selling_price' => 100,
            'unit' => 'piece', 'stock_quantity' => '0.5',
        ])->assertUnprocessable()->assertJsonValidationErrors('stock_quantity');
    }

    public function test_fractional_stock_cannot_be_relabelled_as_whole_units(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['unit' => 'kilogram', 'stock_quantity' => '1.125']);
        $this->actingAs($admin)->putJson(route('products.update', $product), array_merge(
            $this->productData($product), ['unit' => 'piece']
        ))->assertUnprocessable()->assertJsonValidationErrors('unit');
        $this->assertSame('kilogram', $product->fresh()->unit);
    }

    public function test_idempotency_key_cannot_resolve_a_different_order_to_an_old_sale(): void
    {
        $cashier = User::factory()->cashier()->create();
        $product = Product::factory()->create(['stock_quantity' => 10]);
        $payload = $this->order($product);
        $this->actingAs($cashier)->postJson(route('pos.checkout'), $payload)->assertOk();
        $payload['items'][0]['quantity'] = '2';
        $this->postJson(route('pos.checkout'), $payload)->assertUnprocessable()->assertJsonValidationErrors('idempotency_key');
        $this->assertDatabaseCount('sales', 1);
    }

    public function test_historical_category_report_survives_recategorization(): void
    {
        $cashier = User::factory()->cashier()->create();
        $original = Category::factory()->create(['name' => 'Original']);
        $new = Category::factory()->create(['name' => 'New']);
        $product = Product::factory()->for($original)->create();
        $this->actingAs($cashier)->postJson(route('pos.checkout'), $this->order($product))->assertOk();
        $product->update(['category_id' => $new->id]);
        $categories = app(SalesReportService::class)->categoryTotals(now(), now());
        $this->assertSame('Original', $categories->first()->name);
    }

    public function test_admin_removal_takes_a_shared_database_lock_before_mutation(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();
        $locked = false;
        DB::listen(function ($query) use (&$locked) {
            if (str_contains($query->sql, 'from `users`') && str_contains($query->sql, 'for update')) {
                $locked = DB::transactionLevel() > 0;
            }
        });
        $this->actingAs($admin)->put(route('users.update', $other), array_merge(
            $other->only(['name', 'username', 'email']), ['role' => 'cashier', 'is_active' => 1]
        ))->assertRedirect();
        $this->assertTrue($locked, 'Admin removals must serialize under a shared row lock.');
        $this->assertSame(1, User::where('role', 'admin')->where('is_active', true)->count());
    }

    public function test_duplicate_checkout_uses_current_reads_after_serializing_the_cashier(): void
    {
        $cashier = User::factory()->cashier()->create();
        $product = Product::factory()->create(['unit' => 'piece', 'stock_quantity' => 1, 'track_inventory' => true]);
        $locked = false;
        DB::listen(function ($query) use (&$locked) {
            if (str_contains($query->sql, 'from `users`') && str_contains($query->sql, 'for update')) {
                $locked = DB::transactionLevel() > 0;
            }
        });
        $payload = $this->order($product);
        $first = $this->actingAs($cashier)->postJson(route('pos.checkout'), $payload)->assertOk();
        $second = $this->postJson(route('pos.checkout'), $payload)->assertOk();
        $this->assertSame($first->json('sale.reference'), $second->json('sale.reference'));
        $this->assertTrue($locked, 'Duplicate attempts must serialize before testing stock.');
        $this->assertDatabaseCount('sales', 1);
    }
}
