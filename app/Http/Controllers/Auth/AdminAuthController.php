<?php

namespace App\Http\Controllers\Auth;

use App\Classes\FileUploadClass;
use App\Http\Resources\Admin\AdminProfileInfoResource;
use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\ApiResponseHelper;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\ProfileResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\AuthenticateUserResource;
use App\Models\Profile;
use App\Utils\ServerErrorMask;
use Illuminate\Support\Facades\DB;

class AdminAuthController extends Controller
{
    protected $fileUpload;

    public function __construct(FileUploadClass $fileUpload)
    {
        $this->fileUpload = $fileUpload;
    }
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

            $admin = User::where('organization_role', 'Main')
                ->where(function ($query) use ($request) {
                    $query->where('email', $request->emailOrUsername)
                        ->orWhere('username', $request->emailOrUsername);
                })
                ->first();

            if ($admin) {
                if (Hash::check($request->password, $admin->password)) {

                    $credentials = $admin->email
                        ? ['email' => $admin->email, 'password' => $request->password]
                        : ['username' => $admin->username, 'password' => $request->password];

                    if (!$token = auth()->guard('admin')->attempt($credentials)) {
                        $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['Unauthorized Credentials']);
                        return response()->json([
                            'status' => 'error',
                            'message' => $systemError,
                            'payload' => null,
                        ], 401);
                    }
                    $refresh_token = JWTAuth::fromUser($admin, ['exp' => 60 * 24 * 30]);
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Admin Login Successfully',
                        'Admin' => new AuthenticateUserResource($admin),
                        'authorization' => [
                            'access_token' => $token,
                            'refresh_token' => $refresh_token,
                            'type' => 'bearer',
                        ]
                    ]);
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
                    'message' => 'Admin not found',
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

    public function allUserDetails()
    {
        $users = User::with('profiles', 'companies', 'about', 'workSummery', 'skillSet', 'equipment', 'employmentHistory', 'education', 'licenseAndCertificates')
            ->whereIn('organization_role', ['Client', 'Provider', 'Provider Company'])
            ->get();

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
                'about' => $user->about,
                'workSummery' => $user->workSummery,
                'skillSet' => $user->skillSet,
                'equipment' => $user->equipment,
                'employmentHistory' => $user->employmentHistory,
                'education' => $user->education,
                'licenseAndCertificates' => $user->licenseAndCertificates
            ];
        });

        return response()->json([
            'success' => true,
            'users' => $userDetails,
        ]);
    }
    public function individualUserDetails($id)
    {
        $user = User::find($id);

        if ($user->organization_role == 'Provider' || $user->organization_role == 'Provider Company') {

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
            ])->find($id);
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
            ])->find($id);
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

    public function user_status($id)
    {
        $user = User::find($id);
        $user->status = $user->status === 'Active' ? 'Inactive' : 'Active';
        $user->save();
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'organization_role' => $user->organization_role,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function profileInfo()
    {
        try {
            $profileInfo = Profile::query()
                ->where('user_id', Auth::user()->id)
                ->first();

            if (!$profileInfo) {
                $profileInfo = new Profile();
                $profileInfo->user_id = Auth::user()->id;
                $profileInfo->save();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Admin Profile Successfully Updated',
                'admin' => new AdminProfileInfoResource($profileInfo->refresh()),
            ]);
        } catch (\Throwable $e) {
            Log::error($e);
            $formattedMessage = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ServerErrorMask::UNKNOWN_ERROR);
            return response()->json([
                'errors' => $formattedMessage,
                'payload' => null,
            ], 500);
        }
    }

    public function profileUpdate(Request $request)
    {
        $rules = [
            'first_name' => 'required',
            'last_name' => 'required',
            'phone' => 'required',
            'about' => 'required',
            'address' => 'required',
            'profile_image' => 'nullable|mimes:png,jpg,jpeg|max:10240',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }

        try {
            DB::beginTransaction();

            $existProfile = Profile::query()
                ->where('user_id', Auth::user()->id)
                ->first();

            if ($existProfile) {
                $profile = $existProfile;
            } else {
                $profile = new Profile();
            }

            $profile->user_id = Auth::user()->id;
            $profile->first_name = $request->first_name;
            $profile->last_name = $request->last_name;
            $profile->phone = $request->phone;
            $profile->about = $request->about;
            $profile->address_1 = $request->address;

            if ($request->hasFile("profile_image")) {
                $this->fileUpload->fileUnlink($profile->profile_image);
                $image_url = $this->fileUpload->imageUploader($request->file('profile_image'), 'profile_image');
                $profile->profile_image = $image_url;
            }

            $profile->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Admin Profile Successfully Updated',
                'admin' => new AdminProfileInfoResource($profile->refresh()),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);
            $formattedMessage = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ServerErrorMask::UNKNOWN_ERROR);
            return response()->json([
                'errors' => $formattedMessage,
                'payload' => null,
            ], 500);
        }
    }

    public function passwordChange(Request $request)
    {
        $rules = [
            'oldpassword' => 'required',
            'password' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }

        try {
            DB::beginTransaction();

            $user = User::query()
                ->findOrFail(Auth::user()->id);

            if (!Hash::check($request->oldpassword, $user->password)) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Old Password Dose Not Match',
                ]);
            }

            $user->password = Hash::make($request->password);

            $user->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Admin Password Successfully Updated',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Password not change' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }
}
