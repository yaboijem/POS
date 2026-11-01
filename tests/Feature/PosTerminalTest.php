<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosTerminalTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_open_the_pos_terminal(): void
    {
        $cashier = User::factory()->cashier()->create();
        Category::factory()->create(['is_active' => true]);

        $this->actingAs($cashier)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertSee('Current order')
            ->assertSee('Search dish, drink, or product code');
    }

    public function test_product_feed_only_returns_available_products_in_active_categories(): void
    {
        $cashier = User::factory()->cashier()->create();
        $activeCategory = Category::factory()->create(['is_active' => true]);
        $inactiveCategory = Category::factory()->create(['is_active' => false]);

        $visible = Product::factory()->for($activeCategory)->create([
            'name' => 'Chicken Adobo',
            'is_available' => true,
        ]);
        Product::factory()->for($activeCategory)->create([
            'name' => 'Hidden Product',
            'is_available' => false,
        ]);
        Product::factory()->for($inactiveCategory)->create([
            'name' => 'Archived Category Product',
            'is_available' => true,
        ]);

        $this->actingAs($cashier)
            ->getJson(route('pos.products'))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $visible->id)
            ->assertJsonPath('data.0.name', 'Chicken Adobo');
    }

    public function test_product_feed_supports_search_and_category_filtering(): void
    {
        $cashier = User::factory()->cashier()->create();
        $meals = Category::factory()->create(['is_active' => true]);
        $drinks = Category::factory()->create(['is_active' => true]);

        Product::factory()->for($meals)->create(['name' => 'Adobo Rice Meal']);
        Product::factory()->for($meals)->create(['name' => 'Sinigang Rice Meal']);
        Product::factory()->for($drinks)->create(['name' => 'Coke Mismo']);

        $this->actingAs($cashier)
            ->getJson(route('pos.products', ['category' => $meals->id, 'search' => 'Sinigang']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Sinigang Rice Meal');
    }

    public function test_inactive_users_cannot_access_the_terminal_product_feed(): void
    {
        $cashier = User::factory()->cashier()->create(['is_active' => false]);

        $this->actingAs($cashier)
            ->getJson(route('pos.products'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('login');
    }
}
