<?php

namespace Database\Seeders;

use App\Models\WorkOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkOrderTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            DB::beginTransaction();
            $this->workOrderCreate();
            DB::commit();
            $this->command->info('Work Order Successfully Created');
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->command->info($th->getMessage());
        }
    }

    public function workOrderCreate()
    {
        for ($i = 1; $i < 10; $i++) {
            $workOrder = new WorkOrder();
            $workOrder->work_order_unique_id = date('ymd'). $i;
            $workOrder->save();

        }
    }
}
