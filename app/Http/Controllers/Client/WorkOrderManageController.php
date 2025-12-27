<?php

namespace App\Http\Controllers\Client;

use App\Models\Role;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ClientManager;
use App\Services\UserService;
use App\Utils\ServerErrorMask;
use Illuminate\Validation\Rule;
use App\Mail\WelcomeManagerMail;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ClientManagerResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class WorkOrderManageController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index()
    {
        try {
            $client_managers = ClientManager::where('client_id', Auth::user()->uuid)->get(); // Use get() to retrieve all records

            return response()->json([
                'status' => 'success',
                'client_managers' => ClientManagerResource::collection($client_managers),
            ]);
        } catch (\Exception $e) {
            // Handle any errors that might occur
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve client managers.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            // 'user_name' => 'nullable|string|max:255|unique:client_managers|required_without:email',
            // 'email' => 'nullable|string|max:255|unique:client_managers|required_without:user_name|email',
            'user_name' => [
                'nullable',
                'string',
                'max:255',
                'required_without:email',
                Rule::unique('client_managers')
                    ->where(function ($query) {
                        $query->where('client_id', Auth::user()->uuid);
                    }),
            ],

            'email' => [
                'nullable',
                'email',
                'required_without:user_name',
                Rule::unique('client_managers')
                    ->where(function ($query) {
                        $query->where('client_id', Auth::user()->uuid);
                    }),
            ],
            'country_id' => 'required|exists:countries,id',
            'state' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:10',
            'address_one' => 'required|string|max:255',
            'address_two' => 'nullable|string|max:255',
            // 'role_id' => 'required|exists:roles,id',
        ];

        $messages = [
            'email.email' => 'Email must be a valid email address.',
            'email.unique' => 'This email is already taken.',
        ];
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        try {
            DB::beginTransaction();
            // $role = Role::find($request->role_id);
            $clientManager = ClientManager::create([
                'client_id' => Auth::user()->uuid,
                'name' => $request->name,
                'user_name' => $request->user_name,
                'email' => $request->email,
                'status' => 'active',
                'address_one' => $request->address_one,
                'address_two' => $request->address_two ?? null,
                'country_id' => $request->country_id,
                'state' => $request->state,
                'zip_code' => $request->zip_code,
                // 'role' => $role->name, // Default role
                'role' => 'Manager', // Default role
            ]);

            $user = $this->userService->createUser($request);

            $token = Str::random(60);

            $clientManager->update(
                [
                    'user_id' => $user->id,
                    'remember_token' => $token
                ]
            );
            // Send the welcome email with the password setup link
            Mail::to($clientManager->email)->send(new WelcomeManagerMail($clientManager, $token));

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Data insert successfully',
                'client_manager' => $clientManager,
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
            // Retrieve ClientManager by ID
            $clientManager = ClientManager::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'client_manager' => $clientManager,
            ]);
        } catch (ModelNotFoundException $e) {
            // Handle not found exception
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::NOT_FOUND, ['Client Manager not found']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 404);
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
            'name' => 'nullable|string|max:255',
            'user_name' => "nullable|string|max:255|unique:client_managers,user_name,{$id}|required_without:email",
            'email' => "nullable|string|max:255|unique:client_managers,email,{$id}|required_without:user_name|email",
            'country_id' => 'nullable|exists:countries,id',
            'state' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:10',
            'address_one' => 'nullable|string|max:255',
            'address_two' => 'nullable|string|max:255',
            // 'role_id' => 'nullable|exists:roles,id'
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

            $clientManager = ClientManager::findOrFail($id);
            $role = Role::find($request->role_id);
            $clientManager->update([
                'name' => $request->name ?? $clientManager->name,
                'user_name' => $request->user_name ?? $clientManager->user_name,
                'email' => $request->email ?? $clientManager->email,
                'country_id' => $request->country_id ?? $clientManager->country_id,
                'state' => $request->state ?? $clientManager->state,
                'zip_code' => $request->zip_code ?? $clientManager->zip_code,
                'address_one' => $request->address_one ?? $clientManager->address_one,
                'address_two' => $request->address_two ?? $clientManager->address_two,
                // 'role' => $role->name ?? $clientManager->role
                'role' => $clientManager->role
            ]);

            // if(!empty($request->role_id))
            // {

            //     $user = User::find($clientManager->user_id);
            //     $user->update([
            //         'role_id' => $request->role_id,
            //     ]);
            // }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Data updated successfully',
                'client_manager' => $clientManager,
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::NOT_FOUND, ['Client Manager not found']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 404);
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

            $clientManager = ClientManager::where('client_id', Auth::user()->uuid)->findOrFail($id);

            // Prevent deletion if associated with any Work Orders
            $work_order_exists = WorkOrder::where('work_order_manager_id', $clientManager->id)->exists();
            if ($work_order_exists) {
                DB::rollBack();
                $formattedErrors = ApiResponseHelper::formatErrors(
                    ApiResponseHelper::VALIDATION_ERROR,
                    ['Work Order exists for this Client Manager. Please delete the Work Order first.']
                );
                return response()->json([
                    'errors' => $formattedErrors,
                    'payload' => null,
                ], 422);
            }

            // Delete associated user if exists
            if (!empty($clientManager->user_id)) {
                $user = User::find($clientManager->user_id);
                if ($user) {
                    $user->delete();
                }
            }

            $clientManager->delete();

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Client Manager deleted successfully.',
            ]);
        } catch (\Exception $error) {
            DB::rollBack();
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(
                ApiResponseHelper::SYSTEM_ERROR,
                [ServerErrorMask::SERVER_ERROR]
            );
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }
}
