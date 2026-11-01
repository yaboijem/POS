<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_in_increases_stock_and_records_a_movement(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['stock_quantity' => 10, 'track_inventory' => true]);

        $this->actingAs($admin)
            ->post(route('inventory.adjust', $product), [
                'type' => 'stock_in',
                'quantity' => '5',
                'reason' => 'Supplier delivery',
            ])
            ->assertRedirect();

        $this->assertSame('15.000', $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'type' => 'stock_in',
            'quantity_change' => '5.000',
            'quantity_before' => '10.000',
            'quantity_after' => '15.000',
        ]);
    }

    public function test_stock_out_decreases_stock(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['stock_quantity' => 10, 'track_inventory' => true]);

        $this->actingAs($admin)->post(route('inventory.adjust', $product), [
            'type' => 'stock_out',
            'quantity' => '3',
            'reason' => 'Damaged items',
        ])->assertRedirect();

        $this->assertSame('7.000', $product->fresh()->stock_quantity);
    }

    public function test_adjustment_sets_an_exact_count(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['stock_quantity' => 10, 'track_inventory' => true]);

        $this->actingAs($admin)->post(route('inventory.adjust', $product), [
            'type' => 'adjustment',
            'quantity' => '8',
            'reason' => 'Physical count',
        ])->assertRedirect();

        $this->assertSame('8.000', $product->fresh()->stock_quantity);
    }

    public function test_stock_cannot_go_below_zero(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['stock_quantity' => 2, 'track_inventory' => true]);

        $this->actingAs($admin)->post(route('inventory.adjust', $product), [
            'type' => 'stock_out',
            'quantity' => '5',
            'reason' => 'Test',
        ])->assertSessionHasErrors('quantity');

        $this->assertSame('2.000', $product->fresh()->stock_quantity);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_reason_is_required_for_stock_out_and_adjustments(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['stock_quantity' => 5, 'track_inventory' => true]);

        $this->actingAs($admin)->post(route('inventory.adjust', $product), [
            'type' => 'stock_out',
            'quantity' => '1',
        ])->assertSessionHasErrors('reason');
    }

    public function test_cashier_cannot_access_inventory_management(): void
    {
        $cashier = User::factory()->cashier()->create();
        $product = Product::factory()->create();

        $this->actingAs($cashier)->get('/inventory')->assertForbidden();
        $this->actingAs($cashier)->post(route('inventory.adjust', $product), [
            'type' => 'stock_in',
            'quantity' => '1',
            'reason' => 'Test',
        ])->assertForbidden();
    }
}
