<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('####'),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####')),
            'category_id' => Category::factory(),
            'unit_id' => Unit::factory(),
            'purchase_price' => 10,
            'selling_price' => 15,
            'retail_price' => 15,
            'wholesale_price' => 12,
            'stock_quantity' => 0,
            'low_stock_threshold' => 5,
            'selling_type' => 'retail',
            'product_type' => 'single',
            'is_active' => true,
        ];
    }
}
