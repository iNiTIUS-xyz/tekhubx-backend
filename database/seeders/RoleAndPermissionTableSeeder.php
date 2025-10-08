<?php

namespace Database\Seeders;

use App\Helpers\ApiResponseHelper;
use App\Models\Role;
use App\Models\RoleHasPermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleAndPermissionTableSeeder extends Seeder
{
    public function __construct()
    {
        ini_set('memory_limit', '12900M');
    }

    public function run(): void
    {
        try {
            DB::beginTransaction();

            $allSlugs = $this->permissions();

            $role = new Role();
            $role->name = 'Supper Provider';
            $role->save();

            $permission = new RoleHasPermission();
            $permission->role_id = $role->id;
            $permission->permissions = json_encode($allSlugs);
            $permission->save();

            DB::commit();

            $this->command->info('Role & permission successfully seeded');
        } catch (\Throwable $e) {

            DB::rollBack();
            $this->command->info($e->getMessage());
        }
    }

    public function permissions()
    {
        // $adminPermissions = (new Role())->texhubxAdminPermissions();
        // $clientPermissions = (new Role())->texhubxClientPermissions();
        // $providerPermissions = (new Role())->texhubxProviderPermissions();
        $commonPermissions = (new Role())->texhubxCommonPermissions();

        $permitSlugs = [];

        foreach ($commonPermissions as $permit) {

            $permit = (object) $permit;

            if (isset($permit->slug)) {
                $permitSlugs[] = $permit->slug;
            }

            if (!empty($permit->permissions)) {
                foreach ($permit->permissions as $permitPermission) {
                    $permitPermission = (object) $permitPermission;
                    if (isset($permitPermission->slug)) {
                        $permitSlugs[] = $permitPermission->slug;
                    }
                }
            }
        }

        return $permitSlugs;
    }
}
