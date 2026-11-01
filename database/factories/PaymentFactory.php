<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory(),
            'method' => 'cash',
            'reference_number' => null,
            'amount' => 100,
            'status' => 'recorded',
            'paid_at' => now(),
        ];
    }
}
