<?php

namespace Database\Seeders;

use App\Models\EmployeeProvider;
use App\Models\LicenseAndCertificate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LicenseAndCertificateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            DB::beginTransaction();
            $this->licenseAndCertificateCreate();
            DB::commit();
            $this->command->info('License And Certificate Successfully Created');
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->command->info($th->getMessage());
        }
    }

    public function licenseAndCertificateCreate()
    {
        $employeeProvider = EmployeeProvider::query()
                ->get();
        foreach ($employeeProvider as $employee) {
            $dataStore = new LicenseAndCertificate();
            $dataStore->employee_provider_id = $employee->id;
            $dataStore->license_id = 5;
            $dataStore->certificate_id = 5;
            $dataStore->save();
        }

    }
}
