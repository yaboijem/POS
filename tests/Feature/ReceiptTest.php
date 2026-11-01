<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_view_their_own_receipt(): void
    {
        $cashier = User::factory()->cashier()->create();
        $sale = Sale::factory()->for($cashier, 'cashier')->create(['total' => '150.00']);
        SaleItem::factory()->for($sale)->create([
            'product_name' => 'Pancit Bihon',
            'quantity' => 2,
            'unit_price' => '75.00',
            'line_total' => '150.00',
        ]);

        $this->actingAs($cashier)
            ->get(route('receipts.show', $sale))
            ->assertOk()
            ->assertSee($sale->transaction_number)
            ->assertSee('Pancit Bihon')
            ->assertSee('₱150.00');
    }

    public function test_admin_can_view_any_receipt(): void
    {
        $admin = User::factory()->admin()->create();
        $sale = Sale::factory()->create();

        $this->actingAs($admin)
            ->get(route('receipts.show', $sale))
            ->assertOk();
    }

    public function test_cashier_cannot_view_another_cashiers_receipt(): void
    {
        $cashier = User::factory()->cashier()->create();
        $otherCashier = User::factory()->cashier()->create();
        $sale = Sale::factory()->for($otherCashier, 'cashier')->create();

        $this->actingAs($cashier)
            ->get(route('receipts.show', $sale))
            ->assertForbidden();

        $this->actingAs($cashier)
            ->get(route('receipts.pdf', $sale))
            ->assertForbidden();
    }

    public function test_receipt_uses_saved_item_prices_after_product_price_changes(): void
    {
        $cashier = User::factory()->cashier()->create();
        $product = Product::factory()->create(['selling_price' => '100.00']);
        $sale = Sale::factory()->for($cashier, 'cashier')->create([
            'subtotal' => '100.00',
            'total' => '100.00',
        ]);
        SaleItem::factory()->for($sale)->for($product)->create([
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => '100.00',
            'line_total' => '100.00',
        ]);

        $product->update(['selling_price' => '999.00']);

        $this->actingAs($cashier)
            ->get(route('receipts.show', $sale))
            ->assertOk()
            ->assertSee('₱100.00')
            ->assertDontSee('₱999.00');
    }

    public function test_receipt_can_be_downloaded_as_a_pdf(): void
    {
        $cashier = User::factory()->cashier()->create();
        $sale = Sale::factory()->for($cashier, 'cashier')->create();

        $response = $this->actingAs($cashier)->get(route('receipts.pdf', $sale));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
