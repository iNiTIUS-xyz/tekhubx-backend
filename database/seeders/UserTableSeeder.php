<?php

namespace Database\Seeders;

use App\Models\Profile;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserTableSeeder extends Seeder
{
    public function run(): void
    {

        try {
            DB::beginTransaction();
            $this->userCreate();
            DB::commit();
            $this->command->info('User Successfully Created');
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->command->info($th->getMessage());
        }
    }

    public function userCreate()
    {

        $admin = new User();
        $admin->organization_role = 'Main';
        $admin->username = 'adminuser';
        $admin->email = 'admin@gmail.com';
        $admin->password = Hash::make('12345678');
        $admin->role = 'admin';
        $admin->status = 'active';
        $admin->email_verified_at = now();
        $admin->remember_token = null;
        $admin->save();

        $profile = new Profile();
        $profile->user_id = $admin->id;
        $profile->first_name = "Supper";
        $profile->last_name = "Admin";
        $profile->phone = "01478523690";
        $profile->profile_image = null;
        $profile->save();
    }
}
