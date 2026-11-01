<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\User;
use App\Services\SalesReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_real_totals_from_completed_sales(): void
    {
        $this->travelTo(Carbon::parse('2026-10-30 12:00:00'));

        $admin = User::factory()->admin()->create();

        Sale::factory()->create([
            'cashier_id' => $admin->id,
            'total' => 500,
            'subtotal' => 500,
            'amount_tendered' => 500,
            'sold_at' => now()->setTime(10, 15),
        ]);

        Sale::factory()->create([
            'cashier_id' => $admin->id,
            'total' => 300,
            'subtotal' => 300,
            'amount_tendered' => 300,
            'sold_at' => now()->setTime(11, 45),
        ]);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('₱800.00')
            ->assertSee('2 transactions');
    }

    public function test_summary_excludes_void_or_non_completed_sales(): void
    {
        $this->travelTo(Carbon::parse('2026-10-30 12:00:00'));

        $cashier = User::factory()->cashier()->create();

        Sale::factory()->create([
            'cashier_id' => $cashier->id,
            'total' => 400,
            'subtotal' => 400,
            'amount_tendered' => 400,
            'sold_at' => now(),
            'status' => 'voided',
        ]);

        Sale::factory()->create([
            'cashier_id' => $cashier->id,
            'total' => 150,
            'subtotal' => 150,
            'amount_tendered' => 150,
            'sold_at' => now(),
            'status' => Sale::STATUS_COMPLETED,
        ]);

        $summary = app(SalesReportService::class)->summary(now()->startOfDay(), now()->endOfDay());

        $this->assertSame(150.0, $summary['gross']);
        $this->assertSame(1, $summary['transactions']);
    }

    public function test_empty_dashboard_shows_an_empty_state(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('No sales yet');
    }

    public function test_sidebar_identifies_the_current_page_for_assistive_navigation(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/dashboard')->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $links = $xpath->query('//nav[@aria-label="Main navigation"]//a[@aria-current="page"]');

        $this->assertCount(2, $links);
        foreach ($links as $link) {
            $this->assertSame(route('dashboard'), $link->getAttribute('href'));
        }
    }

    public function test_configured_logo_is_available_in_mobile_navigation(): void
    {
        $admin = User::factory()->admin()->create();
        \App\Models\BusinessSetting::put('logo_path', 'logos/business.png');

        $response = $this->actingAs($admin)->get('/dashboard')->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $logos = $xpath->query('//*[@aria-label="Navigation menu"]//img');

        $this->assertCount(1, $logos);
        $this->assertSame(asset('storage/logos/business.png'), $logos->item(0)->getAttribute('src'));
    }
}
