<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Unit;
use App\Models\ProductUnit;

class ProductUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Example: Set up Sugar product with multiple selling units
        // This is just an example - adjust based on your actual products
        
        $sugar = Product::where('name', 'like', '%Sugar%')->first();
        $bag = Unit::where('short_name', 'bag')->first();
        $kg = Unit::where('short_name', 'kg')->orWhere('short_name', 'kilogram')->first();
        
        if ($sugar && $bag && $kg) {
            // Set base unit (bag)
            ProductUnit::firstOrCreate(
                [
                    'product_id' => $sugar->id,
                    'unit_id' => $bag->id,
                ],
                [
                    'is_base_unit' => true,
                    'selling_price' => $sugar->selling_price ?? 2500.00,
                    'is_active' => true,
                ]
            );
            
            // Add selling unit (kg)
            ProductUnit::firstOrCreate(
                [
                    'product_id' => $sugar->id,
                    'unit_id' => $kg->id,
                ],
                [
                    'is_base_unit' => false,
                    'selling_price' => 50.00, // Price per kg
                    'is_active' => true,
                ]
            );
            
            // Update product's base_unit_id if not set
            if (!$sugar->base_unit_id) {
                $sugar->update(['base_unit_id' => $bag->id]);
            }
            
            $this->command->info("Configured {$sugar->name} with base unit: {$bag->short_name} and selling unit: {$kg->short_name}");
        } else {
            $this->command->warn("Sugar product or required units not found. Please create them first.");
        }
    }
}

