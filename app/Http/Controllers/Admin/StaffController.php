<?php

namespace App\Http\Controllers\Admin;

use App\Classes\FileUploadClass;
use App\Helpers\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\User;
use App\Utils\ServerErrorMask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class StaffController extends Controller
{
    protected $fileUpload;

    public function __construct(FileUploadClass $fileUpload)
    {
        $this->fileUpload = $fileUpload;
    }

    public function index()
    {
        try {
            $users = User::query()
                ->where('id', '!=', Auth::guard('admin')->user()->id)
                ->where('organization_role', 'Main')
                ->where('role', 'staff')
                ->with([
                    'profile'
                ])
                ->get();

            return response()->json([
                'status' => 'success',
                'users' => $users,
            ]);
        } catch (\Exception $error) {

            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
            'address_1' => 'required|string|max:500',
            'role_id' => 'required|exists:roles,id',
            'profile_image' => 'nullable|mimes:png,jpg,jpeg|max:10240',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:4',
            'status' => 'required|in:Active,Inactive',
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

            do {
                $uniqueMd5 = md5(uniqid($request->email . Str::random(), true));
            } while (User::where('uuid', $uniqueMd5)->exists());


            $user = new User;
            $user->organization_role = 'Main';
            $user->role = 'staff';
            $user->role_id = $request->role_id;
            $user->uuid = $uniqueMd5;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->status = $request->status;
            $user->save();


            $profile = new Profile();
            $profile->user_id = $user->id;
            $profile->first_name = $request->first_name;
            $profile->last_name = $request->last_name;
            $profile->phone = $request->phone;

            if ($request->hasFile("profile_image")) {
                $profile_image_url = $this->fileUpload->imageUploader($request->file('profile_image'), 'admin_staff');
                $profile->profile_image = $profile_image_url;
            }

            $profile->address_1 = $request->address_1;
            $profile->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Admin staff successfully created',
                'user' => $user,
            ]);
        } catch (\Exception $error) {
            DB::rollBack();
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }

    public function edit($id)
    {
        try {

            $user =  User::query()
                ->with([
                    'profile' => fn($q) => $q->select(['id', 'user_id', 'first_name', 'last_name', 'phone', 'profile_image', 'address_1'])
                ])
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'user' => $user,
            ]);
        } catch (\Exception $error) {
            DB::rollBack();
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }


    public function update(Request $request, $id)
    {
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
            'address_1' => 'required|string|max:500',
            'role_id' => 'required|exists:roles,id',
            'email' => 'required|email|unique:users,email,' . $id,
            'profile_image' => 'nullable|mimes:png,jpg,jpeg|max:10240',
            'password' => 'required|string|min:4',
            'status' => 'required|in:Active,Inactive',
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
                ->where('organization_role', 'Main')
                ->where('role', 'staff')
                ->findOrFail($id);

            $user->role_id = $request->role_id;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->status = $request->status;
            $user->save();

            $profile = Profile::query()
                ->where('user_id', $user->id)
                ->first();

            $profile->first_name = $request->first_name;
            $profile->last_name = $request->last_name;
            $profile->phone = $request->phone;

            if ($request->hasFile("profile_image")) {
                $this->fileUpload->fileUnlink($profile->profile_image);
                $profile_image_url = $this->fileUpload->imageUploader($request->file('profile_image'), 'admin_staff');
                $profile->profile_image = $profile_image_url;
            }

            $profile->address_1 = $request->address_1;
            $profile->save();

            DB::commit();


            $updateUser =  User::query()
                ->with([
                    'profile' => fn($q) => $q->select(['id', 'user_id', 'first_name', 'last_name', 'phone', 'profile_image', 'address_1'])
                ])
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Admin staff successfully updated',
                'user' => $updateUser,
            ]);
        } catch (\Exception $error) {
            DB::rollBack();
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $user =  User::query()
                ->where('role', 'staff')
                ->findOrFail($id);

            $profile = Profile::query()
                ->where('user_id', $user->id)
                ->first();

            $this->fileUpload->fileUnlink($profile->profile_image);

            $profile->delete();

            $user->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Admin staff successfully deleted',
            ]);
        } catch (\Exception $error) {
            DB::rollBack();
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }
}
