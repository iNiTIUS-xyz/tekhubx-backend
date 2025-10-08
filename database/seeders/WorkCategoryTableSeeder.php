<?php

namespace Database\Seeders;

use App\Models\WorkCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class WorkCategoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            DB::beginTransaction();
            $this->workCategoryCreate();
            DB::commit();
            $this->command->info('Work Category Successfully Created');
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->command->info($th->getMessage());
        }
    }

    public function workCategoryCreate()
    {
        for ($i = 1; $i < 10; $i++) {
            $faker = Faker::create();
            $workCategory = new WorkCategory();
            $workCategory->name = $faker->word . $i;
            $workCategory->save();
        }
    }
}
