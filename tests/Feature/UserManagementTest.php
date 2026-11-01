<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_cashier_account(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Maria Santos',
            'username' => 'maria',
            'email' => 'maria@karinderya.test',
            'role' => 'cashier',
            'is_active' => '1',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success');

        $staff = User::query()->where('username', 'maria')->firstOrFail();

        $this->assertSame('Maria Santos', $staff->name);
        $this->assertSame(User::ROLE_CASHIER, $staff->role);
        $this->assertTrue($staff->is_active);
        $this->assertTrue(Hash::check('newpassword123', $staff->password));
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.created', 'auditable_id' => $staff->id]);
    }

    public function test_cashier_cannot_manage_users(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)->get(route('users.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('users.create'))->assertForbidden();
        $this->actingAs($cashier)->post(route('users.store'), [])->assertForbidden();
    }

    public function test_admin_can_update_staff_details(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->cashier()->create();

        $this->actingAs($admin)->put(route('users.update', $staff), [
            'name' => 'Updated Name',
            'username' => 'updated-user',
            'email' => 'updated@karinderya.test',
            'role' => 'admin',
            'is_active' => '1',
        ])->assertRedirect(route('users.index'));

        $staff->refresh();

        $this->assertSame('Updated Name', $staff->name);
        $this->assertSame('updated-user', $staff->username);
        $this->assertSame(User::ROLE_ADMIN, $staff->role);
        $this->assertTrue($staff->is_active);
    }

    public function test_last_active_admin_cannot_be_demoted_or_deactivated(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('users.update', $admin), [
            'name' => $admin->name,
            'username' => $admin->username,
            'email' => $admin->email,
            'role' => 'cashier',
            'is_active' => '1',
        ])->assertSessionHasErrors('role');

        $this->assertSame(User::ROLE_ADMIN, $admin->fresh()->role);

        $this->actingAs($admin)
            ->patch(route('users.status', $admin))
            ->assertSessionHas('error');

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_last_active_admin_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $admin))
            ->assertSessionHas('error');

        $this->assertNotNull(User::query()->find($admin->id));
    }

    public function test_admin_can_deactivate_and_reactivate_a_cashier(): void
    {
        $admin = User::factory()->admin()->create();
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($admin)
            ->patch(route('users.status', $cashier))
            ->assertSessionHas('success');

        $this->assertFalse($cashier->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.deactivated', 'auditable_id' => $cashier->id]);

        $this->actingAs($admin)
            ->patch(route('users.status', $cashier))
            ->assertSessionHas('success');

        $this->assertTrue($cashier->fresh()->is_active);
    }

    public function test_admin_can_reset_a_staff_password(): void
    {
        $admin = User::factory()->admin()->create();
        $cashier = User::factory()->cashier()->create(['password' => 'oldpassword']);

        $this->actingAs($admin)->patch(route('users.password', $cashier), [
            'password' => 'brandnewpass123',
            'password_confirmation' => 'brandnewpass123',
        ])->assertSessionHas('success');

        $this->assertTrue(Hash::check('brandnewpass123', $cashier->fresh()->password));
        $this->assertFalse(Hash::check('oldpassword', $cashier->fresh()->password));
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.password_reset', 'auditable_id' => $cashier->id]);
    }

    public function test_user_creation_validates_unique_username_and_email(): void
    {
        $admin = User::factory()->admin()->create();
        $existing = User::factory()->cashier()->create();

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Duplicate',
            'username' => $existing->username,
            'email' => $existing->email,
            'role' => 'cashier',
            'is_active' => '1',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors(['username', 'email']);

        $this->assertSame(2, User::query()->count());
    }

    public function test_cashier_cannot_reset_passwords(): void
    {
        $cashier = User::factory()->cashier()->create();
        $target = User::factory()->cashier()->create();

        $this->actingAs($cashier)->patch(route('users.password', $target), [
            'password' => 'brandnewpass123',
            'password_confirmation' => 'brandnewpass123',
        ])->assertForbidden();
    }

    public function test_audit_log_records_staff_changes(): void
    {
        $admin = User::factory()->admin()->create();
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($admin)->patch(route('users.status', $cashier));

        $log = AuditLog::query()->where('action', 'user.deactivated')->firstOrFail();

        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame($cashier->id, $log->auditable_id);
        $this->assertSame('App\\Models\\User', $log->auditable_type);
    }
}
