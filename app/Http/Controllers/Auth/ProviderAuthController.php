<?php

namespace App\Http\Controllers\Auth;

use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use App\Models\State;
use App\Models\Company;
use App\Models\Country;
use App\Models\Profile;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\VerifyEmailMail;
use App\Services\UserService;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Stevebauman\Location\Facades\Location;
use App\Http\Resources\AuthenticateUserResource;
use App\Http\Requests\Auth\ProviderRegisterRequest;

class ProviderAuthController extends Controller
{

    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function register(Request $request)
    {
        $rules = [
            'organization_role' => 'required|in:Provider,Provider Company',
            'email' => 'required|unique:users',
            'password' => 'required|confirmed',
            'first_name' => 'required|max:30',
            'last_name' => 'required|max:30',
            'phone' => 'required|max:15',
            'why_chosen_us' => 'required',
            'terms_of_service' => 'required',
            'country_id' => 'required',
            'state_id' => 'required',
            'city' => 'required',
            'address' => 'required',
            'zip_code' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        $validator->sometimes('company_name', 'required', function ($input) {
            return $input->organization_role === 'Provider Company';
        });

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }


        try {
            DB::beginTransaction();
            // create user
            $user = $this->createUser($request);
            // create company
            if ($request->organization_role == 'Provider Company') {
                $company = $this->createCompany($request, $user);
            }
            // create user profile
            $profile = $this->createUserProfile($request, $user);
            // set company id
            $profile->company_id = $company->id ?? null;
            $profile->save();

            DB::commit();

            // Generate a token
            $token = Str::random(64);

            DB::table('verification_tokens')->insert([
                'user_id' => $user->id,
                'token' => $token,
                'created_at' => now(),
            ]);

            // Send mail
            Mail::to($user->email)->send(new VerifyEmailMail($token));

            if ($user) {
                $token = Auth::login($user);
                $refresh_token = JWTAuth::fromUser($user, ['exp' => 60 * 24 * 30]);
                return response()->json([
                    'status' => 'success',
                    'message' => 'Provider created successfully. Please verify your email.',
                    'provider' => new AuthenticateUserResource($user),
                    'authorization' => [
                        'access_token' => $token,
                        'refresh_token' => $refresh_token,
                        'type' => 'bearer',
                    ]
                ]);
            }
        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('User creation failed: ' . $e->getMessage());

            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
                'payload' => null,
            ], 500);
        }
    }

    // public function createUser($request)
    // {
    //     $providerRole = Role::query()
    //         ->where('user_id', null)
    //         ->where('tag', 'provider')
    //         ->where('name', 'Super Admin')
    //         ->first();
    //     Log::info('Provider Role found: ' . $providerRole);
    //     $email = $request->email;

    //     $isEmail = filter_var($email, FILTER_VALIDATE_EMAIL);

    //     do {
    //         $uniqueMd5 = md5(uniqid($email . Str::random(), true));
    //     } while (User::where('uuid', $uniqueMd5)->exists());

    //     $user = User::create([
    //         'organization_role' => $request->organization_role,
    //         'password' => Hash::make($request->password),
    //         'role' => 'Super Admin',
    //         'username' => $isEmail ? null : $email,
    //         'email' => $isEmail ? $email : null,
    //         'status' => 'Active',
    //         'uuid' => $uniqueMd5,
    //         'role_id' => $providerRole->id,
    //         'created_at' => Carbon::now(),
    //     ]);

    //     return $user;
    // }
    private function createUser(Request $request)
    {
        Log::info('Creating new user', ['request' => $request->all()]);

        $providerRole = Role::query()
            ->whereNull('user_id')
            ->where('tag', 'provider')
            ->where('name', 'Super Admin')
            ->first();

        // Generate unique UUID
        do {
            $uuid = md5(uniqid($request->email . Str::random(), true));
        } while (User::where('uuid', $uuid)->exists());

        $user = User::create([
            'organization_role' => $request->organization_role,
            'password' => Hash::make($request->password),
            'role' => 'Super Admin',
            'username' => filter_var($request->email, FILTER_VALIDATE_EMAIL) ? null : $request->email,
            'email' => filter_var($request->email, FILTER_VALIDATE_EMAIL) ? $request->email : null,
            'status' => 'Active',
            'uuid' => $uuid,
            'role_id' => $providerRole->id ?? null,
            'created_at' => Carbon::now(),
        ]);

        Log::info('User created', ['user' => $user]);

        return $user;
    }

    private function createUserProfile(Request $request, User $user, $company = null)
    {
        Log::info('Creating user profile', ['request' => $request->all()]);

        // Geolocation (optional)
        $ip = $request->ip();
        $locationData = ($ip === '127.0.0.1') ? Location::get('8.8.8.8') : Location::get($ip);

        $ipLocation = [
            'ip' => $ip,
            'countryName' => $locationData->countryName ?? null,
            'cityName' => $locationData->cityName ?? null,
        ];

        $profile = Profile::create([
            'user_id' => $user->id,
            'company_id' => $company->id ?? null,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
            'why_chosen_us' => is_array($request->why_chosen_us) ? json_encode($request->why_chosen_us) : $request->why_chosen_us,
            'terms_of_service' => $request->terms_of_service ? 1 : 0,
            'joining_ip' => $ipLocation['ip'],
            'joining_ip_location' => $ipLocation['countryName'],
            'joining_city' => $ipLocation['cityName'],
            'country_id' => $request->country_id,
            'state_id' => $request->state_id,
            'city' => $request->city,
            'address_1' => $request->address,
            'zip_code' => $request->zip_code,
            'profile_status' => 0,
        ]);

        Log::info('User profile created', ['profile' => $profile]);

        return $profile;
    }
    // public function createUserProfile($request, $user)
    // {
    //     $ip = request()->ip();
    //     $locationData = ($ip == '127.0.0.1') ?
    //         Location::get('8.8.4.4') :
    //         Location::get($ip);

    //     // Provide fallback if location lookup fails
    //     $ipLocation = [
    //         'ip' => $ip,
    //         'countryName' => null,
    //         'cityName' => null
    //     ];

    //     if ($locationData) {
    //         $ipLocation = [
    //             'ip' => $locationData->ip ?? $ip,
    //             'countryName' => $locationData->countryName ?? null,
    //             'cityName' => $locationData->cityName ?? null
    //         ];
    //     }
    //     $country_name = Country::where('id', $request->country_id)->first();
    //     $state_name = State::where('id', $request->state_id)->first();

    //     $full_address = "{$request->address}, {$request->city}, {$state_name->name}, {$request->zip_code}, {$country_name->name}";

    //     $location = $this->userService->geocodeAddressOSM($full_address);

    //     Log::info('Geocoded location: ' . $location);
    //     if ($location) {
    //         $latitude = $location['latitude'];
    //         $longitude = $location['longitude'];
    //     } else {
    //         $latitude = null;
    //         $longitude = null;
    //     }
    //     $profile = Profile::create([
    //         'user_id' => $user->id,
    //         'first_name' => $request->first_name,
    //         'last_name' => $request->last_name,
    //         'phone' => $request->phone,
    //         'why_chosen_us' => $request->why_chosen_us,
    //         'terms_of_service' => $request->terms_of_service ? 1 : 0,
    //         'joining_ip' => $ipLocation['ip'],
    //         'joining_ip_location' => $ipLocation['countryName'],
    //         'joining_city' => $ipLocation['cityName'],
    //         'login_date_time' => Carbon::now(),
    //         'created_at' => Carbon::now(),
    //         'profile_status' => 0,
    //         'country_id' => $request->country_id,
    //         'state_id' => $request->state_id,
    //         'city' => $request->city,
    //         'address_1' => $request->address,
    //         'zip_code' => $request->zip_code,
    //         'latitude' => $latitude,
    //         'longitude' => $longitude,
    //         'social_security_number' => $request->security_code,
    //     ]);
    //     return $profile;
    // }

    // public function createCompany($request, $user)
    // {
    //     $company = Company::create([
    //         'user_id' => $user->id,
    //         'company_name' => $request->company_name,
    //         'created_at' => Carbon::now(),
    //     ]);

    //     return $company;
    // }
    private function createCompany(Request $request, User $user)
    {
        Log::info('Creating company', ['request' => $request->all()]);

        $company = Company::create([
            'user_id' => $user->id,
            'company_name' => $request->company_name,
            'types_of_work' => $request->types_of_work ?? [],
            'skill_sets' => $request->skill_sets ?? [],
            'equipments' => $request->equipments ?? [],
            'licenses' => $request->licenses ?? [],
            'certifications' => $request->certifications ?? [],
        ]);

        Log::info('Company created', ['company' => $company]);

        return $company;
    }
}
