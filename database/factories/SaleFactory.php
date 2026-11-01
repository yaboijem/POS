<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    public function definition(): array
    {
        $total = fake()->randomElement([120, 250, 380, 540, 750]);

        return [
            'reference' => (string) Str::ulid(),
            'transaction_number' => 'TEST-'.Str::upper(Str::random(10)),
            'cashier_id' => User::factory()->cashier(),
            'status' => Sale::STATUS_COMPLETED,
            'subtotal' => $total,
            'discount_type' => null,
            'discount_value' => null,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => $total,
            'amount_tendered' => $total,
            'change_amount' => 0,
            'payment_method' => 'cash',
            'payment_reference' => null,
            'notes' => null,
            'idempotency_key' => (string) Str::uuid(),
            'sold_at' => now(),
        ];
    }
}
