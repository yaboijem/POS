<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_every_cashiers_sales(): void
    {
        $admin = User::factory()->admin()->create();
        $cashierA = User::factory()->cashier()->create();
        $cashierB = User::factory()->cashier()->create();

        $saleA = Sale::factory()->for($cashierA, 'cashier')->create(['transaction_number' => 'KAR-TEST-AAAAA']);
        $saleB = Sale::factory()->for($cashierB, 'cashier')->create(['transaction_number' => 'KAR-TEST-BBBBB']);

        $this->actingAs($admin)
            ->get(route('sales.index'))
            ->assertOk()
            ->assertSee($saleA->transaction_number)
            ->assertSee($saleB->transaction_number);
    }

    public function test_cashier_only_sees_their_own_sales(): void
    {
        $cashier = User::factory()->cashier()->create();
        $otherCashier = User::factory()->cashier()->create();

        $ownSale = Sale::factory()->for($cashier, 'cashier')->create(['transaction_number' => 'KAR-TEST-OWN01']);
        $otherSale = Sale::factory()->for($otherCashier, 'cashier')->create(['transaction_number' => 'KAR-TEST-OTH01']);

        $this->actingAs($cashier)
            ->get(route('sales.index'))
            ->assertOk()
            ->assertSee($ownSale->transaction_number)
            ->assertDontSee($otherSale->transaction_number);
    }

    public function test_cashier_cannot_open_another_cashiers_transaction(): void
    {
        $cashier = User::factory()->cashier()->create();
        $otherCashier = User::factory()->cashier()->create();
        $otherSale = Sale::factory()->for($otherCashier, 'cashier')->create();

        $this->actingAs($cashier)
            ->get(route('sales.show', $otherSale))
            ->assertForbidden();
    }

    public function test_sale_detail_shows_saved_items_and_payment(): void
    {
        $cashier = User::factory()->cashier()->create();
        $sale = Sale::factory()->for($cashier, 'cashier')->create([
            'transaction_number' => 'KAR-TEST-DETAIL',
            'payment_method' => 'gcash',
            'payment_reference' => 'GCASH-12345',
            'total' => '150.00',
            'subtotal' => '150.00',
        ]);
        SaleItem::factory()->for($sale)->create([
            'product_name' => 'Pancit Bihon',
            'quantity' => 2,
            'unit_price' => '75.00',
            'line_total' => '150.00',
        ]);

        $this->actingAs($cashier)
            ->get(route('sales.show', $sale))
            ->assertOk()
            ->assertSee('Pancit Bihon')
            ->assertSee('GCASH-12345')
            ->assertSee('₱150.00');
    }

    public function test_sales_can_be_filtered_by_payment_method(): void
    {
        $admin = User::factory()->admin()->create();
        $cashier = User::factory()->cashier()->create();

        $gcashSale = Sale::factory()->for($cashier, 'cashier')->create([
            'transaction_number' => 'KAR-TEST-GCASH',
            'payment_method' => 'gcash',
        ]);
        $cashSale = Sale::factory()->for($cashier, 'cashier')->create([
            'transaction_number' => 'KAR-TEST-CASH1',
            'payment_method' => 'cash',
        ]);

        $this->actingAs($admin)
            ->get(route('sales.index', ['payment_method' => 'gcash']))
            ->assertOk()
            ->assertSee($gcashSale->transaction_number)
            ->assertDontSee($cashSale->transaction_number);
    }

    public function test_admin_can_export_filtered_sales_as_csv(): void
    {
        $admin = User::factory()->admin()->create();
        $cashier = User::factory()->cashier()->create();
        $sale = Sale::factory()->for($cashier, 'cashier')->create([
            'transaction_number' => 'KAR-TEST-EXPORT',
            'total' => '275.50',
        ]);
        SaleItem::factory()->for($sale)->create([
            'product_name' => 'Pancit Bihon',
            'quantity' => 1,
            'unit_price' => '275.50',
            'line_total' => '275.50',
        ]);

        $response = $this->actingAs($admin)->get(route('sales.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('Transaction number', $content);
        $this->assertStringContainsString('KAR-TEST-EXPORT', $content);
        $this->assertStringContainsString('Pancit Bihon', $content);
        $this->assertStringContainsString('275.50', $content);
    }

    public function test_cashier_cannot_export_sales(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)
            ->get(route('sales.export'))
            ->assertForbidden();
    }
}
