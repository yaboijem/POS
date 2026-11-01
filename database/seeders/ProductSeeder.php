<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['RM-ADOBO', 'Adobo Rice Meal', 'Rice Meals', 89, 45, 'serving', false, 0, null],
            ['RM-SINIGANG', 'Sinigang Rice Meal', 'Rice Meals', 95, 50, 'serving', false, 0, null],
            ['RM-FRIEDCHIX', 'Fried Chicken Rice Meal', 'Rice Meals', 99, 55, 'serving', false, 0, null],
            ['RM-SISIG', 'Pork Sisig Rice Meal', 'Rice Meals', 95, 48, 'serving', false, 0, null],
            ['UL-ADOBO', 'Chicken Adobo (Ulam)', 'Ulam / Viands', 70, 38, 'order', false, 0, null],
            ['UL-MENUDO', 'Pork Menudo (Ulam)', 'Ulam / Viands', 65, 35, 'order', false, 0, null],
            ['UL-GULAY', 'Ginataang Gulay (Ulam)', 'Ulam / Viands', 55, 28, 'order', false, 0, null],
            ['UL-CALDERETA', 'Beef Caldereta (Ulam)', 'Ulam / Viands', 85, 48, 'order', false, 0, null],
            ['SL-TAPSILOG', 'Tapsilog', 'Silog Meals', 95, 50, 'order', false, 0, null],
            ['SL-LONGSILOG', 'Longsilog', 'Silog Meals', 85, 45, 'order', false, 0, null],
            ['SL-TOCILOG', 'Tocilog', 'Silog Meals', 85, 42, 'order', false, 0, null],
            ['SN-CHICHARON', 'Chicharon', 'Snacks', 25, 15, 'pack', true, 20, 5],
            ['SN-BANANACUE', 'Banana Cue', 'Snacks', 20, 10, 'piece', true, 15, 4],
            ['SN-PIATTOS', 'Piattos', 'Snacks', 25, 18, 'pack', true, 30, 6],
            ['CD-MENTOS', 'Mentos', 'Candies', 12, 8, 'pack', true, 40, 10],
            ['CD-MAXX', 'Maxx Candy', 'Candies', 5, 3, 'piece', true, 60, 15],
            ['SD-COKE', 'Coke Mismo', 'Soft Drinks', 35, 25, 'bottle', true, 24, 6],
            ['SD-ROYAL', 'Royal Mismo', 'Soft Drinks', 35, 25, 'bottle', true, 24, 6],
            ['SD-SPRITE15', 'Sprite 1.5L', 'Soft Drinks', 89, 68, 'bottle', true, 10, 3],
            ['BW-500', 'Bottled Water 500ml', 'Bottled Water', 20, 12, 'bottle', true, 30, 10],
            ['CF-KOPIKO', 'Kopiko 3-in-1', 'Coffee and Other Drinks', 10, 6, 'pack', true, 50, 12],
            ['CF-NESCAFE', 'Nescafe Original', 'Coffee and Other Drinks', 10, 6, 'pack', true, 50, 12],
            ['CG-555', '555 Sardines', 'Canned Goods', 28, 22, 'can', true, 20, 5],
            ['CG-ARGENTINA', 'Argentina Corned Beef', 'Canned Goods', 45, 36, 'can', true, 15, 4],
            ['OP-PANCIT', 'Lucky Me Pancit Canton', 'Other Products', 20, 15, 'pack', true, 40, 10],
        ];

        foreach ($products as [$sku, $name, $categoryName, $price, $cost, $unit, $tracked, $stock, $threshold]) {
            $category = Category::query()->where('name', $categoryName)->first();

            if (! $category) {
                continue;
            }

            Product::query()->updateOrCreate(
                ['sku' => $sku],
                [
                    'category_id' => $category->id,
                    'name' => $name,
                    'selling_price' => $price,
                    'cost_price' => $cost,
                    'unit' => $unit,
                    'track_inventory' => $tracked,
                    'stock_quantity' => $stock,
                    'low_stock_threshold' => $threshold,
                    'is_available' => true,
                    'description' => null,
                ]
            );
        }
    }
}
