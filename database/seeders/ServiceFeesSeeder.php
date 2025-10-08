<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceFeesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('service_fees')->insert([
            [
                'tekhubx_fee' => 5.00,
                'insurance' => 2.50,
                'tax' => 1.50,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tekhubx_fee' => 7.00,
                'insurance' => 3.00,
                'tax' => 2.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Add more entries as needed
        ]);
    }
}
