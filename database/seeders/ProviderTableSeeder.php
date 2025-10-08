<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProviderTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            DB::beginTransaction();
            $this->providerCreate();
            DB::commit();
            $this->command->info('Provider Successfully Created');
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->command->info($th->getMessage());
        }
    }

    public function providerCreate()
    {
        for ($i = 1; $i < 5; $i++) {
            $workOrder = new User();
            $workOrder->uuid = date('ymd'). $i;
            $workOrder->organization_role = 'Provider';
            $workOrder->username = 'provider_username'. $i;
            $workOrder->email = 'provider.email'. $i . '@gmail.com';
            $workOrder->password = Hash::make('12345678');
            $workOrder->role = 'provider';
            $workOrder->save();

        }
    }
}
