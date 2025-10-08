<?php

namespace Database\Seeders;

use App\Models\EmployeeProvider;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeProviderTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            DB::beginTransaction();
            $this->employeeProviderCreate();
            DB::commit();
            $this->command->info('Work Order Successfully Created');
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->command->info($th->getMessage());
        }
    }

    public function employeeProviderCreate()
    {
        for ($i = 1; $i < 5; $i++) {
            $workOrder = new EmployeeProvider();
            $workOrder->provider_id = date('ymd'). $i;
            $workOrder->save();

        }
    }
}
