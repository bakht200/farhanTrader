<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->lexify('unit-????');

        return [
            'name' => $name,
            'short_name' => strtoupper(fake()->unique()->lexify('??')),
            'is_active' => true,
        ];
    }
}
