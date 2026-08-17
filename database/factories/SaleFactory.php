<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'sale_number' => 'SALE-'.fake()->unique()->numerify('######'),
            'user_id' => User::factory(),
            'sale_date' => now()->toDateString(),
            'subtotal' => 100,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 100,
            'paid_amount' => 100,
            'payment_status' => 'paid',
            'status' => 'completed',
        ];
    }
}
