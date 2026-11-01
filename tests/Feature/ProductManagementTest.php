<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_product_with_an_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin)->post('/products', [
            'category_id' => $category->id,
            'name' => 'Chicken Adobo Rice Meal',
            'sku' => 'rm-adobo-test',
            'selling_price' => '89.50',
            'cost_price' => '45',
            'unit' => 'serving',
            'track_inventory' => '0',
            'stock_quantity' => '0',
            'is_available' => '1',
            'image' => UploadedFile::fake()->image('adobo.jpg'),
        ])->assertRedirect(route('products.index'));

        $product = Product::query()->where('name', 'Chicken Adobo Rice Meal')->firstOrFail();

        $this->assertSame('RM-ADOBO-TEST', $product->sku);
        $this->assertSame('89.50', $product->selling_price);
        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_product_price_cannot_be_negative(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin)->post('/products', [
            'category_id' => $category->id,
            'name' => 'Bad Price',
            'selling_price' => '-10',
            'unit' => 'piece',
            'stock_quantity' => '0',
        ])->assertSessionHasErrors('selling_price');

        $this->assertDatabaseMissing('products', ['name' => 'Bad Price']);
    }

    public function test_price_update_records_history_and_audit(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->price('100.00')->create();

        $this->actingAs($admin)
            ->patch(route('products.price', $product), [
                'selling_price' => '120.00',
                'note' => 'Supplier price increase',
            ])
            ->assertRedirect();

        $this->assertSame('120.00', $product->fresh()->selling_price);
        $this->assertDatabaseHas('product_price_histories', [
            'product_id' => $product->id,
            'changed_by' => $admin->id,
            'old_selling_price' => '100.00',
            'new_selling_price' => '120.00',
            'note' => 'Supplier price increase',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'product.price_updated',
        ]);
    }

    public function test_changing_a_price_does_not_change_historical_sale_items(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->price('100.00')->create();
        $sale = Sale::factory()->create(['cashier_id' => $admin->id, 'subtotal' => 100, 'total' => 100, 'amount_tendered' => 100]);
        $item = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => '100.00',
            'quantity' => 1,
            'line_total' => '100.00',
        ]);

        $this->actingAs($admin)->patch(route('products.price', $product), ['selling_price' => '150.00']);

        $this->assertSame('100.00', $item->fresh()->unit_price);
        $this->assertSame('100.00', $sale->fresh()->total);
    }

    public function test_admin_can_archive_a_product(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();

        $this->actingAs($admin)
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'));

        $this->assertSoftDeleted($product);
    }

    public function test_cashier_cannot_manage_products_or_prices(): void
    {
        $cashier = User::factory()->cashier()->create();
        $product = Product::factory()->create();

        $this->actingAs($cashier)->get('/products')->assertForbidden();
        $this->actingAs($cashier)->patch(route('products.price', $product), ['selling_price' => '1.00'])->assertForbidden();
    }
}
