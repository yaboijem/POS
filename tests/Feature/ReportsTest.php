<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_sales_report_totals(): void
    {
        $admin = User::factory()->admin()->create();
        $cashier = User::factory()->cashier()->create();
        $sale = Sale::factory()->for($cashier, 'cashier')->create([
            'sold_at' => now()->setDate(2026, 10, 15)->setTime(12, 0),
            'subtotal' => '250.00',
            'total' => '250.00',
            'amount_tendered' => '250.00',
            'payment_method' => 'cash',
        ]);
        SaleItem::factory()->for($sale)->create([
            'product_name' => 'Pancit Bihon',
            'quantity' => 2,
            'unit_price' => '125.00',
            'line_total' => '250.00',
        ]);

        $this->actingAs($admin)
            ->get(route('reports.index', ['from' => '2026-10-01', 'to' => '2026-10-31']))
            ->assertOk()
            ->assertSee('₱250.00')
            ->assertSee('Pancit Bihon')
            ->assertSee('Cashier performance')
            ->assertSee('Daily totals');
    }

    public function test_reports_exclude_sales_outside_the_date_range(): void
    {
        $admin = User::factory()->admin()->create();
        $cashier = User::factory()->cashier()->create();

        $insideSale = Sale::factory()->for($cashier, 'cashier')->create([
            'sold_at' => now()->setDate(2026, 10, 15)->setTime(12, 0),
            'total' => '100.00',
        ]);
        SaleItem::factory()->for($insideSale)->create(['product_name' => 'Inside Product']);

        $outsideSale = Sale::factory()->for($cashier, 'cashier')->create([
            'sold_at' => now()->setDate(2026, 9, 1)->setTime(12, 0),
            'total' => '999.00',
        ]);
        SaleItem::factory()->for($outsideSale)->create(['product_name' => 'Outside Product']);

        $this->actingAs($admin)
            ->get(route('reports.index', ['from' => '2026-10-01', 'to' => '2026-10-31']))
            ->assertOk()
            ->assertSee('Inside Product')
            ->assertDontSee('Outside Product')
            ->assertDontSee('₱999.00');
    }

    public function test_reports_validate_the_date_range(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('reports.index', ['from' => '2026-10-31', 'to' => '2026-10-01']))
            ->assertSessionHasErrors('to');
    }

    public function test_admin_can_export_the_report_as_csv(): void
    {
        $admin = User::factory()->admin()->create();
        $cashier = User::factory()->cashier()->create();
        $sale = Sale::factory()->for($cashier, 'cashier')->create([
            'sold_at' => now()->setDate(2026, 10, 15)->setTime(12, 0),
            'subtotal' => '275.50',
            'total' => '275.50',
        ]);
        SaleItem::factory()->for($sale)->create([
            'product_name' => 'Pancit Bihon',
            'quantity' => 1,
            'unit_price' => '275.50',
            'line_total' => '275.50',
        ]);

        $response = $this->actingAs($admin)->get(route('reports.export', [
            'from' => '2026-10-01',
            'to' => '2026-10-31',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('Sales report', $content);
        $this->assertStringContainsString('Gross sales', $content);
        $this->assertStringContainsString('275.50', $content);
        $this->assertStringContainsString('Pancit Bihon', $content);
    }

    public function test_cashier_cannot_view_or_export_reports(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('reports.export'))->assertForbidden();
    }

    public function test_report_supports_weekly_and_monthly_grouping(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('reports.index', ['from' => '2026-01-01', 'to' => '2026-10-31', 'group' => 'month']))
            ->assertOk()
            ->assertSee('Monthly');

        $this->actingAs($admin)
            ->get(route('reports.index', ['from' => '2026-10-01', 'to' => '2026-10-31', 'group' => 'week']))
            ->assertOk()
            ->assertSee('Weekly');
    }
}
