<?php

namespace Database\Seeders;

use App\Models\RoleHasPermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NewPermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        try {
            DB::beginTransaction();

            // write your permission where
            // and remember do not use same permission slug

            $newPermissionArray = ["provider_dashboard.dashboard"];

            $this->newPermissions($newPermissionArray);

            DB::commit();

            $this->command->info('New Permission successfully seeded');
        } catch (\Throwable $e) {

            DB::rollBack();
            $this->command->info($e->getMessage());
        }
    }

    public function newPermissions($newPermissionArray)
    {
        $permissionsLists = RoleHasPermission::query()
            ->where('role_id', 2)
            ->first();

        $existingPermissions = json_decode($permissionsLists->permissions, true) ?? [];

        $updatedPermissions = array_unique(array_merge($existingPermissions, $newPermissionArray));

        $permissionsLists->permissions = json_encode($updatedPermissions);

        $permissionsLists->save();

        foreach ($permissionsLists as $permit) {

            $updatePermit = RoleHasPermission::find($permit->id);

            $existingPermissions = json_decode($updatePermit->permissions, true) ?? [];

            $updatedPermissions = array_unique(array_merge($existingPermissions, $newPermissionArray));

            $updatePermit->permissions = json_encode($updatedPermissions);

            $updatePermit->save();
        }
    }
}
