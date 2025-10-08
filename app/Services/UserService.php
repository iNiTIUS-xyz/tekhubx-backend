<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use App\Models\Profile;
use Stevebauman\Location\Facades\Location;

class UserService
{
    public function createUser($request)
    {
        $role = Role::find($request->role_id);
        $user = User::create([
            'organization_role' => 'Client',
            'username' => $request->user_name,
            'email' => $request->email,
            'status' => 'Active',
            'uuid' => $request->client_id,
            // 'role' => $role->name,
            'role' => 'Manager',
            // 'role_id' => $request->role_id,
            'created_at' => Carbon::now(),
        ]);

        return $user;
    }

    public function createProviderUser($request, $uuid)
    {
        $role = Role::find($request->role_id);
        $main_user = User::where('uuid', $uuid)->where('role', 'Super Admin')->first();
        $user = User::create([
            'organization_role' => $main_user->organization_role,
            // 'role' => $role->name,
            'role' => 'Manager',
            // 'role_id' => $request->role_id,
            'username' => $request->user_name,
            'email' => $request->email,
            'status' => 'Active',
            'created_at' => Carbon::now(),
            'uuid' => $uuid,
        ]);

        return $user;
    }

    public function createProviderUserProfile($request, $user)
    {
        request()->ip() == '127.0.0.1' ? $locationData = Location::get('8.8.4.4') : $locationData = Location::get(request()->ip());

        $profile = Profile::create([
            'user_id' => $user->id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
            'joining_ip' => $locationData->ip,
            'joining_ip_location' => $locationData->countryName,
            'joining_city' => $locationData->cityName,
            'login_date_time' => Carbon::now(),
            'created_at' => Carbon::now(),
            'profile_status' => 0,
        ]);

        return $profile;
    }

    public function geocodeAddressOSM($address)
    {
        $url = "https://nominatim.openstreetmap.org/search?q=" . urlencode($address) . "&format=json&limit=1";

        // Create context with User-Agent
        $opts = [
            "http" => [
                "header" => "User-Agent: MyLaravelApp/1.0 (https://example.com)\r\n"
            ]
        ];
        $context = stream_context_create($opts);

        // Use the context in file_get_contents
        $response = file_get_contents($url, false, $context);

        $data = json_decode($response);

        if (!empty($data)) {
            return [
                'latitude' => $data[0]->lat,
                'longitude' => $data[0]->lon
            ];
        }

        return null; // Handle error or invalid address
    }
}
