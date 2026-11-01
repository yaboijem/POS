<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_category(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/categories', [
            'name' => 'Merienda',
            'color' => '#F97316',
            'description' => 'Afternoon snacks',
            'is_active' => '1',
        ])->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'name' => 'Merienda',
            'slug' => 'merienda',
        ]);
    }

    public function test_category_name_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        Category::factory()->create(['name' => 'Snacks']);

        $this->actingAs($admin)->post('/categories', [
            'name' => 'Snacks',
            'color' => '#F97316',
        ])->assertSessionHasErrors('name');
    }

    public function test_admin_can_reorder_categories(): void
    {
        $admin = User::factory()->admin()->create();
        $first = Category::factory()->create(['name' => 'First', 'sort_order' => 1]);
        $second = Category::factory()->create(['name' => 'Second', 'sort_order' => 2]);

        $this->actingAs($admin)->post(route('categories.move', [$second, 'up']))->assertRedirect();

        $this->assertSame(1, $second->fresh()->sort_order);
        $this->assertSame(2, $first->fresh()->sort_order);
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        $this->actingAs($admin)
            ->delete(route('categories.destroy', $category))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($category);
    }

    public function test_empty_category_can_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'));

        $this->assertSoftDeleted($category);
    }

    public function test_cashier_cannot_manage_categories(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)->get('/categories')->assertForbidden();
        $this->actingAs($cashier)->post('/categories', ['name' => 'X', 'color' => '#F97316'])->assertForbidden();
    }
}
