<?php

namespace Tests\Feature;

use App\Models\BusinessSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'business_name' => "Aling Nena's Karinderya",
            'business_address' => '123 Mabini Street',
            'business_phone' => '0917 123 4567',
            'business_email' => 'hello@karinderya.test',
            'receipt_footer' => 'Salamat po!',
            'low_stock_threshold' => '5',
            'payment_methods' => ['cash', 'gcash', 'maya', 'other'],
            'tax_enabled' => '0',
            'tax_rate' => '0',
            'tax_inclusive' => '0',
        ], $overrides);
    }

    public function test_admin_can_update_business_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('settings.update'), $this->payload([
                'business_name' => 'New Karinderya',
                'business_address' => '45 Rizal Avenue',
                'business_phone' => '0918 000 1111',
                'business_email' => 'new@karinderya.test',
                'receipt_footer' => 'Maraming salamat!',
                'low_stock_threshold' => '8',
                'payment_methods' => ['cash', 'maya'],
            ]))
            ->assertRedirect(route('settings.index'))
            ->assertSessionHas('success');

        $this->assertSame('New Karinderya', setting('business_name'));
        $this->assertSame('45 Rizal Avenue', setting('business_address'));
        $this->assertSame('8', setting('low_stock_threshold'));
        $this->assertSame(['cash', 'maya'], json_decode((string) setting('payment_methods'), true));
        $this->assertDatabaseHas('audit_logs', ['action' => 'settings.updated']);
    }

    public function test_optional_tax_can_be_enabled_with_a_rate(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('settings.update'), $this->payload([
                'tax_enabled' => '1',
                'tax_rate' => '12.5',
                'tax_inclusive' => '1',
            ]))
            ->assertRedirect(route('settings.index'));

        $this->assertSame('1', setting('tax_enabled'));
        $this->assertSame('12.50', setting('tax_rate'));
        $this->assertSame('1', setting('tax_inclusive'));
    }

    public function test_tax_requires_a_rate_when_enabled(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('settings.update'), $this->payload([
                'tax_enabled' => '1',
                'tax_rate' => '',
            ]))
            ->assertSessionHasErrors('tax_rate');
    }

    public function test_at_least_one_payment_method_is_required(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('settings.update'), $this->payload([
                'payment_methods' => [],
            ]))
            ->assertSessionHasErrors('payment_methods');
    }

    public function test_logo_can_be_uploaded_and_removed(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('settings.update'), $this->payload([
                'logo' => UploadedFile::fake()->image('logo.jpg', 300, 300),
            ]))
            ->assertRedirect(route('settings.index'));

        $path = setting('logo_path');
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        $this->actingAs($admin)
            ->put(route('settings.update'), $this->payload([
                'remove_logo' => '1',
            ]))
            ->assertRedirect(route('settings.index'));

        $this->assertNull(setting('logo_path'));
        Storage::disk('public')->assertMissing($path);
    }

    public function test_settings_page_requires_admin(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)->get(route('settings.index'))->assertForbidden();
        $this->actingAs($cashier)->put(route('settings.update'), $this->payload())->assertForbidden();
    }

    public function test_settings_page_shows_current_business_details(): void
    {
        $admin = User::factory()->admin()->create();
        BusinessSetting::put('business_name', 'Existing Karinderya');

        $this->actingAs($admin)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Existing Karinderya')
            ->assertSee('Asia/Manila')
            ->assertSee('PHP');
    }
}
