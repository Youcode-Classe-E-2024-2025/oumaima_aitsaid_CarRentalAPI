<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CarsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('cars')->insert([
            [
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'license_plate' => 'ABC123',
            'year' => 2022,
            'color' => 'White',
            'transmission' => 'automatic',
            'fuel_type' => 'gasoline',
            'seats' => 5,
            'daily_rate' => 50.00,
            'is_available' => true,
            'description' => 'Comfortable sedan for daily use',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);
    }
}
