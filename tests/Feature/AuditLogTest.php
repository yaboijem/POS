<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_audit_log(): void
    {
        $admin = User::factory()->admin()->create();

        AuditLog::record($admin, 'product.updated', 'Updated the price of Adobo Rice Meal.');

        $this->actingAs($admin)
            ->get(route('audit.index'))
            ->assertOk()
            ->assertSee('Updated the price of Adobo Rice Meal.')
            ->assertSee('Product Updated')
            ->assertSee($admin->name);
    }

    public function test_cashier_cannot_view_the_audit_log(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)
            ->get(route('audit.index'))
            ->assertForbidden();
    }

    public function test_audit_log_can_be_filtered_by_action(): void
    {
        $admin = User::factory()->admin()->create();

        AuditLog::record($admin, 'user.created', 'Created a cashier account.');
        AuditLog::record($admin, 'product.updated', 'Changed a product name.');

        $this->actingAs($admin)
            ->get(route('audit.index', ['action' => 'user.created']))
            ->assertOk()
            ->assertSee('Created a cashier account.')
            ->assertDontSee('Changed a product name.');
    }

    public function test_audit_log_can_be_searched(): void
    {
        $admin = User::factory()->admin()->create();

        AuditLog::record($admin, 'inventory.adjusted', 'Adjusted stock for Pancit Bihon.');
        AuditLog::record($admin, 'settings.updated', 'Updated the receipt footer.');

        $this->actingAs($admin)
            ->get(route('audit.index', ['search' => 'Pancit']))
            ->assertOk()
            ->assertSee('Adjusted stock for Pancit Bihon.')
            ->assertDontSee('Updated the receipt footer.');
    }

    public function test_audit_log_shows_before_and_after_values(): void
    {
        $admin = User::factory()->admin()->create();

        AuditLog::record($admin, 'settings.updated', 'Updated settings.', null, ['tax_rate' => '0'], ['tax_rate' => '12.50']);

        $this->actingAs($admin)
            ->get(route('audit.index'))
            ->assertOk()
            ->assertSee('12.50');
    }
}
