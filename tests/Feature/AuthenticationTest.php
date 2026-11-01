<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/pos')->assertRedirect('/login');
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_admin_can_log_in_with_email_and_lands_on_dashboard(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'owner@karinderya.test',
            'password' => 'password',
        ]);

        $this->post('/login', [
            'login' => 'owner@karinderya.test',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->assertNotNull($admin->fresh()->last_login_at);
    }

    public function test_cashier_can_log_in_with_username_and_lands_on_pos(): void
    {
        $cashier = User::factory()->cashier()->create([
            'username' => 'cashier1',
            'email' => 'cashier1@karinderya.test',
            'password' => 'password',
        ]);

        $this->post('/login', [
            'login' => 'cashier1',
            'password' => 'password',
        ])->assertRedirect(route('pos.index'));

        $this->assertAuthenticatedAs($cashier);
    }

    public function test_inactive_account_cannot_log_in(): void
    {
        User::factory()->inactive()->create([
            'email' => 'inactive@karinderya.test',
            'password' => 'password',
        ]);

        $this->post('/login', [
            'login' => 'inactive@karinderya.test',
            'password' => 'password',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'staff@karinderya.test',
            'password' => 'password',
        ]);

        $this->post('/login', [
            'login' => 'staff@karinderya.test',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_deactivated_user_is_logged_out_on_next_request(): void
    {
        $user = User::factory()->cashier()->create();
        $this->actingAs($user);

        $user->update(['is_active' => false]);

        $this->get('/pos')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_cashier_cannot_access_admin_dashboard(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)->get('/dashboard')->assertForbidden();
    }

    public function test_admin_can_open_pos_and_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/dashboard')->assertOk();
        $this->actingAs($admin)->get('/pos')->assertOk();
    }

    public function test_root_redirects_by_role(): void
    {
        $admin = User::factory()->admin()->create();
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($admin)->get('/')->assertRedirect(route('dashboard'));
        $this->actingAs($cashier)->get('/')->assertRedirect(route('pos.index'));
    }
}
