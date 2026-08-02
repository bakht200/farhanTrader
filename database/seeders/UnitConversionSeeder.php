<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Unit;
use App\Models\UnitConversion;

class UnitConversionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Example: Set up Bag and Kilogram conversion
        // This is just an example - adjust based on your actual units
        
        $bag = Unit::where('short_name', 'bag')->first();
        $kg = Unit::where('short_name', 'kg')->orWhere('short_name', 'kilogram')->first();
        
        if ($bag && $kg) {
            // Create conversion: 1 bag = 50 kg
            UnitConversion::firstOrCreate(
                [
                    'from_unit_id' => $bag->id,
                    'to_unit_id' => $kg->id,
                ],
                [
                    'conversion_factor' => 50.0,
                    'is_active' => true,
                ]
            );
            
            $this->command->info("Created conversion: 1 {$bag->short_name} = 50 {$kg->short_name}");
        } else {
            $this->command->warn("Bag or Kilogram units not found. Please create units first.");
        }
        
        // Add more conversions as needed
        // Example: 1 dozen = 12 pieces
        // Example: 1 liter = 1000 milliliters
    }
}

