<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Rice Meals', 'color' => '#F97316'],
            ['name' => 'Ulam / Viands', 'color' => '#DC2626'],
            ['name' => 'Silog Meals', 'color' => '#F59E0B'],
            ['name' => 'Snacks', 'color' => '#10B981'],
            ['name' => 'Candies', 'color' => '#EC4899'],
            ['name' => 'Soft Drinks', 'color' => '#3B82F6'],
            ['name' => 'Bottled Water', 'color' => '#06B6D4'],
            ['name' => 'Coffee and Other Drinks', 'color' => '#8B5CF6'],
            ['name' => 'Canned Goods', 'color' => '#64748B'],
            ['name' => 'Other Products', 'color' => '#94A3B8'],
        ];

        foreach ($categories as $index => $category) {
            Category::query()->updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'color' => $category['color'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
