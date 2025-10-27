<?php

namespace App\Http\Controllers\Auth;

use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use App\Models\Company;
use App\Models\Profile;
use App\Models\WorkSummery;
use Illuminate\Support\Str;
use App\Models\Subscription;
use Illuminate\Http\Request;
use App\Mail\VerifyEmailMail;
use App\Utils\ServerErrorMask;
use App\Mail\ResetPasswordMail;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\ProfileResource;
use Illuminate\Support\Facades\Validator;
use Stevebauman\Location\Facades\Location;
use App\Http\Resources\AuthenticateUserResource;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class ClientAuthController extends Controller
{

    public function login(Request $request)
    {
        $rules = [
            'emailOrUsername' => 'required',
            'password' => 'required|min:8',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        try {
            $user = User::where(function ($query) use ($request) {
                $query->where('email', $request->emailOrUsername)
                    ->orWhere('username', $request->emailOrUsername)
                    ->where('status', 'Active');
            })
                // ->where('status', 'Active')
                ->first();
            if ($user) {
                if ($user->status != 'Active') {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'User is not active, please contact support.',
                        'payload' => null,
                    ], 403);
                }
                if (!$user->email_verified_at) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Please verify your email before login.'
                    ], 403);
                }
                if (Hash::check($request->password, $user->password)) {

                    $credentials = $user->email
                        ? ['email' => $user->email, 'password' => $request->password]
                        : ['username' => $user->username, 'password' => $request->password];

                    if ($user->organization_role == "Client") {
                        if (!$token = auth()->guard('client')->attempt($credentials)) {
                            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['Unauthorized Credentials']);
                            return response()->json([
                                'status' => 'error',
                                'message' => $systemError,
                                'payload' => null,
                            ], 401);
                        }
                        // Update updated_at column
                        $user->touch();
                        $refresh_token = JWTAuth::fromUser($user, ['exp' => 120 * 24 * 30]);
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Login Successfully',
                            'user' => new AuthenticateUserResource($user),
                            'authorization' => [
                                'access_token' => $token,
                                'refresh_token' => $refresh_token,
                                'type' => 'bearer',
                            ]
                        ]);
                    } elseif ($user->organization_role == "Provider" || $user->organization_role == "Provider Company") {
                        if (!$token = auth()->guard('provider')->attempt($credentials)) {
                            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['Unauthorized Credentials']);
                            return response()->json([
                                'status' => 'error',
                                'message' => $systemError,
                                'payload' => null,
                            ], 401);
                        }
                        $work_summery = WorkSummery::where('user_id', $user->id)->orWhere('uuid', $user->uuid)->first();
                        if (empty($work_summery)) {
                            $summery_flag = false;
                        } else {
                            $summery_flag = true;
                        }
                        // Update updated_at column
                        $user->touch();
                        $refresh_token = JWTAuth::fromUser($user, ['exp' => 120 * 24 * 30]);
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Login Successfully',
                            'summery_flag' => $summery_flag,
                            'user' => new AuthenticateUserResource($user),
                            'authorization' => [
                                'access_token' => $token,
                                'refresh_token' => $refresh_token,
                                'type' => 'bearer',
                            ]
                        ]);
                    } else {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Invalid Credential',
                            'payload' => null,
                        ], 200);
                    }
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid credential',
                        'payload' => null,
                    ], 200);
                }
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid Credential',
                    'payload' => null,
                ], 200);
            }
        } catch (\Exception $e) {

            Log::error('User Login failed: ' . $e->getMessage());

            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['User Login failed!']);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
                'payload' => null,
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        // Get the guard that is currently being used
        $guard = Auth::getDefaultDriver();
        // Logout the user from the current guard
        Auth::guard($guard)->logout();

        return response()->json(['success' => true, 'message' => 'Logged out successfully.']);
    }

    public function register(Request $request)
    {
        $rules = [
            'email' => 'required|email|unique:users',
            'first_name' => 'required|max:20',
            'last_name' => 'required|max:20',
            'company_name' => 'required',
            'phone' => 'required|max:15',
            'password' => 'required|confirmed',
            'why_chosen_us' => 'required',
            'employee_counter' => 'required',
            'technicians_hire' => 'required',
            'annual_revenue' => 'required',
            'need_technicians' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

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
            // crate user profile
            $profile = $this->createUserProfile($request, $user);
            $company = $this->createCompany($request, $user);
            $profile->company_id = $company->id;
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

            if (!$token = auth()->guard('client')->attempt($request->only('email', 'password'))) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            $refresh_token = JWTAuth::fromUser($user, ['exp' => 60 * 24 * 30]);
            return response()->json([
                'status' => 'success',
                'message' => 'Client Created Successfully. Please verify your email.',
                'client' => new AuthenticateUserResource($user),
                'authorization' => [
                    'access_token' => $token,
                    'refresh_token' => $refresh_token,
                    'type' => 'bearer',
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User creation failed: ' . $e->getMessage());

            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['User creation failed!']);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
                'payload' => null,
            ], 500);
        }
    }

    public function createUser($request)
    {

        $clientRole = Role::query()
            ->where('user_id', null)
            ->where('tag', 'client')
            ->where('name', 'Super Admin')
            ->first();

        $email = $request->email;
        $isEmail = filter_var($email, FILTER_VALIDATE_EMAIL);

        do {
            $uniqueMd5 = md5(uniqid($email . Str::random(), true));
        } while (User::where('uuid', $uniqueMd5)->exists());

        $user = User::create([
            'organization_role' => 'Client',
            'password' => Hash::make($request->password),
            'role' => 'Super Admin',
            'username' => $isEmail ? null : $email,
            'email' => $isEmail ? $email : null,
            'status' => 'Active',
            'uuid' => $uniqueMd5,
            'role_id' => $clientRole->id,
            'created_at' => Carbon::now(),
        ]);

        $subscription = new Subscription();
        $subscription->uuid = $uniqueMd5;
        $subscription->status = 'Inactive';
        $subscription->save();

        return $user;
    }

    public function createUserProfile($request, $user)
    {
        // request()->ip() == '127.0.0.1' ? $locationData = Location::get('8.8.4.4') : $locationData = Location::get(request()->ip());

        $ip = request()->ip();
        $locationData = ($ip == '127.0.0.1') ?
            Location::get('8.8.4.4') :
            Location::get($ip);

        // Provide fallback if location lookup fails
        $ipLocation = [
            'ip' => $ip,
            'countryName' => null,
            'cityName' => null
        ];

        if ($locationData) {
            $ipLocation = [
                'ip' => $locationData->ip ?? $ip,
                'countryName' => $locationData->countryName ?? null,
                'cityName' => $locationData->cityName ?? null
            ];
        }

        $profile = Profile::create([
            'user_id' => $user->id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
            'why_chosen_us' => $request->why_chosen_us,
            'joining_ip' => $ipLocation['ip'],
            'joining_ip_location' => $ipLocation['countryName'],
            'joining_city' => $ipLocation['cityName'],
            'login_date_time' => Carbon::now(),
            'created_at' => Carbon::now(),
            'profile_status' => 0,
        ]);

        return $profile;
    }

    public function createCompany($request, $user)
    {
        $company = Company::create([
            'user_id' => $user->id,
            'company_name' => $request->company_name,
            'employee_counter' => $request->employee_counter,
            'technicians_hire' => $request->technicians_hire,
            'annual_revenue' => $request->annual_revenue,
            'need_technicians' => $request->need_technicians,
            'created_at' => Carbon::now(),
        ]);

        return $company;
    }
    public function refreshToken(Request $request)
    {
        try {
            $token = $request->bearerToken() ?? $request->input('token');

            if (!$token) {
                return response()->json(['error' => 'Token is required'], 400);
            }

            $newToken = JWTAuth::setToken($token)->refresh();
            $user = JWTAuth::setToken($newToken)->toUser();

            if (!$user) {
                throw new \Exception('User not found for the provided token');
            }

            // Compute summery_flag
            $workSummary = WorkSummery::where('user_id', $user->id)
                ->when($user->uuid, function ($query) use ($user) {
                    return $query->orWhere('uuid', $user->uuid);
                })
                ->first();
            $summery_flag = !!$workSummary;

            Log::channel('auth')->info('Token refreshed successfully', [
                'user_id' => $user->id,
                'uuid' => $user->uuid,
                // 'summery_flag' => $summery_flag,
            ]);

            return response()->json([
                'status' => 'success',
                'authorization' => [
                    'access_token' => $newToken,
                    // 'summery_flag' => $summery_flag,
                    'type' => 'bearer',
                    'expires_in' => JWTAuth::factory()->getTTL() * 60,
                ],
                'user' => new AuthenticateUserResource($user),
            ]);
        } catch (TokenInvalidException $e) {
            Log::channel('auth')->error('Token invalid', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid token'], 401);
        } catch (TokenExpiredException $e) {
            Log::channel('auth')->error('Token expired', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Token has expired'], 401);
        } catch (\Exception $e) {
            Log::channel('auth')->error('Token refresh failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Could not refresh token: ' . $e->getMessage()], 500);
        }
    }

    //password reset methods

    public function forgotPassword(Request $request)
    {
        $rules = [
            'email' => 'required|email|exists:users,email',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }
        try {
            $token = Str::random(60);

            // Store token
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                ['token' => $token, 'created_at' => now()]
            );

            // Send email
            Mail::to($request->email)->send(new ResetPasswordMail($request->email, $token));

            return response()->json([
                'status' => 'success',
                'message' => 'Reset token sent to your email.',
            ]);
        } catch (\Exception $e) {
            Log::error('Forgot password failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong.',
            ], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        $rules = [
            'token' => 'required',
            'password' => 'required|min:8|confirmed', // expects password_confirmation field
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        // Find reset record by token
        $reset = DB::table('password_reset_tokens')
            ->where('token', $request->token)
            ->first();

        if (!$reset) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired token.',
            ], 400);
        }

        // Now you have the email from token
        $user = User::where('email', $reset->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.',
            ], 404);
        }

        // Update password
        $user->password = Hash::make($request->password);
        $user->save();

        // Delete token after use
        DB::table('password_reset_tokens')->where('email', $reset->email)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Password has been reset successfully.',
        ]);
    }


    public function userProfile()
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated.',
            ], 401);
        }

        $userId = Auth::id();

        if (Auth::user()->organization_role == 'Provider' || Auth::user()->organization_role == 'Provider Company') {

            $user = User::with([
                'profile',
                'companies',
                'about',
                'workSummery',
                'skillSet',
                'equipment',
                'employmentHistory',
                'education',
                'licenseAndCertificates'
            ])->find($userId);
            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'organization_role' => $user->organization_role,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status,
                    'profile' => $user->profiles->isNotEmpty() ? new ProfileResource($user->profiles->first()) : null,
                    'company' => $user->companies->isNotEmpty() ? new CompanyResource($user->companies->first()) : null,
                    'about' => $user->about,
                    'workSummery' => $user->workSummery,
                    'skillSet' => $user->skillSet,
                    'equipment' => $user->equipment,
                    'employmentHistory' => $user->employmentHistory,
                    'education' => $user->education,
                    'licenseAndCertificates' => $user->licenseAndCertificates,
                ],
            ]);
        } else {
            $user = User::with([
                'profile',
                'companies',
                'about',
                'workSummery',
                'skillSet',
                'equipment',
                'employmentHistory',
                'education',
                'clientLicenseAndCertificates'
            ])->find($userId);
            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'organization_role' => $user->organization_role,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status,
                    'profile' => $user->profiles->isNotEmpty() ? new ProfileResource($user->profiles->first()) : null,
                    'company' => $user->companies->isNotEmpty() ? new CompanyResource($user->companies->first()) : null,
                    'about' => $user->about,
                    'workSummery' => $user->workSummery,
                    'skillSet' => $user->skillSet,
                    'equipment' => $user->equipment,
                    'employmentHistory' => $user->employmentHistory,
                    'education' => $user->education,
                    'licenseAndCertificates' => $user->clientLicenseAndCertificates,
                ],
            ]);
        }
    }


    public function storeProvider(Request $request)
    {
        try {
        } catch (\Exception $e) {
            Log::error($e);
            $formattedMessage = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ServerErrorMask::UNKNOWN_ERROR);
            return response()->json([
                'errors' => $formattedMessage,
                'payload' => null,
            ], 500);
        }
    }

    public function allProviders()
    {
        $users = User::with('profiles', 'companies')->whereIn('organization_role', ['Provider', 'Provider Company'])->get();

        $userDetails = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'organization_role' => $user->organization_role,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'profile' => $user->profiles->isNotEmpty() ? new ProfileResource($user->profiles->first()) : null,
                'company' => $user->companies->isNotEmpty() ? new CompanyResource($user->companies->first()) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'users' => $userDetails,
        ]);
    }
}
