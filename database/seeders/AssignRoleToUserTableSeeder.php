<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Helpers\ApiResponseHelper;
use App\Models\RoleHasPermission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class AssignRoleToUserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        try {
            DB::beginTransaction();

            // Fetch roles only once
            $adminRole = Role::query()
                ->where('name', 'Admin')
                ->Where('tag', 'admin')
                ->first();

            $clientRole = Role::query()
                ->where('name', 'Super Admin')
                ->Where('tag', 'client')
                ->first();

            $providerRole = Role::query()
                ->where('name', 'Super Admin')
                ->Where('tag', 'provider')
                ->first();

            if (!$adminRole || !$clientRole || !$providerRole) {
                throw new \Exception('Required roles are missing in the roles table.');
            }
            // Fetch unassigned users
            $unRolledUsers = User::query()
                ->whereNull('role_id')
                ->get();

            foreach ($unRolledUsers as $user) {
                if ($user->organization_role === 'Main' && $user->role === 'admin') {
                    $user->role_id = $adminRole->id;
                } elseif ($user->organization_role === 'Client' && $user->role === 'Super Admin') {
                    $user->role_id = $clientRole->id;
                } elseif (
                    ($user->organization_role === 'Provider' || $user->organization_role === 'Provider Company') &&
                    $user->role === 'Super Admin'
                ) {
                    $user->role_id = $providerRole->id;
                }

                // Save the user only if role_id was updated
                if (isset($user->role_id)) {
                    $user->save();
                }
            }

            DB::commit();

            $this->command->info('Role assignment successfully completed.');
        } catch (\Throwable $e) {
            DB::rollBack();

            $this->command->error('Error: ' . $e->getMessage());
        }
    }
}
