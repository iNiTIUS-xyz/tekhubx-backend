<?php

namespace Database\Seeders;

use App\Models\QualificationType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class QualificationTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            DB::beginTransaction();
            $this->qualificationTypeCreate();
            DB::commit();
            $this->command->info('Qualification Type Successfully Created');
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->command->info($th->getMessage());
        }
    }


    public function qualificationTypeCreate()
    {

        QualificationType::insert([
            ['id' => 1, 'name' => 'certifications', 'note' => NULL, 'created_at' => '2024-08-27 00:18:00', 'updated_at' => '2024-08-27 00:18:00'],
            ['id' => 2, 'name' => 'licenses', 'note' => '', 'created_at' => '2024-08-27 00:19:36', 'updated_at' => '2024-08-27 00:19:36'],
            ['id' => 3, 'name' => 'equipment', 'note' => NULL, 'created_at' => '2024-08-27 00:20:22', 'updated_at' => '2024-08-27 00:20:22'],
            ['id' => 4, 'name' => 'insurance', 'note' => NULL, 'created_at' => '2024-08-27 00:20:32', 'updated_at' => '2024-08-27 00:20:32'],
            ['id' => 5, 'name' => 'background check', 'note' => NULL, 'created_at' => '2024-08-27 00:40:52', 'updated_at' => '2024-08-27 00:40:52'],
            ['id' => 6, 'name' => 'drug test', 'note' => NULL, 'created_at' => '2024-08-27 00:41:05', 'updated_at' => '2024-08-27 00:41:05'],
            ['id' => 7, 'name' => 'covid-19 vaccination proof', 'note' => NULL, 'created_at' => '2024-08-27 00:43:12', 'updated_at' => '2024-08-27 00:43:12'],
        ]);

    }
}
