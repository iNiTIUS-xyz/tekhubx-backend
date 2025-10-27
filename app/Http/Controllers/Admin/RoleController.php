<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\RoleResource;
use App\Models\RoleHasPermission;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\User;
use App\Utils\ServerErrorMask;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{

    public function index(Request $request)
    {
        try {

            if(auth()->guard('client')->check() || auth()->guard('provider')->check()) {
                $data = Role::query()
                ->where('uuid', Auth::user()->uuid)
                ->get();
            }else {

                $data = Role::query()
                    ->get();
            }


            return response()->json([
                'status' => 'success',
                'roles' => RoleResource::collection($data),
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

    public function permissions(Request $request)
    {
        try {

            $adminPermissions = (new Role)->texhubxAdminPermissions(); // if guard is admin then pass this variable

            $commonPermission = (new Role)->texhubxCommonPermissions()->toArray();

            $clientPermissions = (new Role)->texhubxClientPermissions()->toArray();
            $providerPermissions = (new Role)->texhubxProviderPermissions()->toArray();

            // $combinedClientPermissions = array_merge($clientPermissions, $commonPermission); // if guard is client then pass this variable
            // $combinedProviderPermissions = array_merge($providerPermissions, $commonPermission); // if guard is provider then pass this variable
            $client = Auth::guard('client')->user();
            $provider = Auth::guard('provider')->user();
            $admin = Auth::guard('admin')->user();

            if($client->organization_role == "Client")
            {
                return response()->json([
                    'status' => 'success',
                    'permissions' => $clientPermissions,
                ]);
            }

            if($provider->organization_role == "Provider" || $provider->organization_role == "Provider Company")
            {
                return response()->json([
                    'status' => 'success',
                    'permissions' => $providerPermissions,
                ]);
            }

            if($admin->organization_role == "Main")
            {
                return response()->json([
                    'status' => 'success',
                    'permissions' => $adminPermissions,
                ]);
            }

        } catch (\Exception $error) {

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
            'name' => 'required|string|max:255',
            'permissions' => 'required|array|min:1',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }

        $existRole = Role::query()
            ->where('uuid', Auth::user()->uuid)
            ->where('name', $request->name)
            ->get();

        if ($existRole->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have already one role has with this name',
            ]);
        }

        try {
            DB::beginTransaction();

            $role = new Role();
            $role->uuid = Auth::user()->uuid;
            $role->user_id = Auth::user()->id;
            $role->name = $request->name;
            $role->save();

            $roleHasPermission = new RoleHasPermission();
            $roleHasPermission->role_id = $role->id;
            $roleHasPermission->permissions = $request->permissions ? json_encode($request->permissions) : [];
            $roleHasPermission->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Role Successfully Created",
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
            $role = Role::query()
                ->where('user_id', Auth::user()->id)
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'message' => 'New data inserted',
                'plan' => new RoleResource($role),
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

    public function update(Request $request, $id)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'permissions' => 'required|array|min:1',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }

        $existRole = Role::query()
            ->where('user_id', Auth::user()->id)
            ->where('name', $request->name)
            ->where('id', '!=', $id)
            ->get();

        if ($existRole->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have already one role has with this name',
            ]);
        }

        try {
            DB::beginTransaction();


            $role = Role::query()
                ->where('user_id', Auth::user()->id)
                ->findOrFail($id);

            $role->name = $request->name;
            $role->save();

            $roleHasPermission = RoleHasPermission::query()
                ->where('role_id', $role->id)
                ->first();

            $roleHasPermission->permissions = $request->permissions ? json_encode($request->permissions) : [];
            $roleHasPermission->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "Role Successfully Created",
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $role = Role::query()
                ->where('user_id', Auth::user()->id)
                ->with('roleAccess')
                ->findOrFail($id);

            if ($role->roleAccess->count() > 0) {
                return false;
            }

            $permission = RoleHasPermission::query()
                ->where('role_id', $id)
                ->first();

            if ($permission) {
                $permission->delete();
            }

            $role->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "Role Successfully Deleted",
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

    public function myPermission()
    {
        try {
            $user = User::find(Auth::user()->id);
            $permission = RoleHasPermission::query()
                ->where('role_id', $user->role_id)
                ->first();

            return response()->json([
                'status' => true,
                'myPermissions' => json_decode($permission->permissions),
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
}
