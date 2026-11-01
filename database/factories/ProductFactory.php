<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'selling_price' => 50,
            'cost_price' => 30,
            'unit' => 'piece',
            'track_inventory' => true,
            'stock_quantity' => 10,
            'low_stock_threshold' => 3,
            'is_available' => true,
        ];
    }

    public function untracked(): static
    {
        return $this->state(fn () => [
            'track_inventory' => false,
            'stock_quantity' => 0,
            'low_stock_threshold' => null,
            'unit' => 'serving',
        ]);
    }

    public function unavailable(): static
    {
        return $this->state(fn () => ['is_available' => false]);
    }

    public function price(string $price): static
    {
        return $this->state(fn () => ['selling_price' => $price]);
    }
}
