<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class PlanTableSeeder extends Seeder
{
    public function run(): void
    {

        try {
            DB::beginTransaction();
            $this->planCreate();
            DB::commit();
            $this->command->info('Plan Successfully Created');
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->command->info($th->getMessage());
        }
    }

    public function planCreate()
    {
        for ($i = 1; $i < 4; $i++) {
            $faker = Faker::create();
            $plan = new Plan();
            $plan->name = $faker->word . $i;
            $plan->save();
        }
    }
}
