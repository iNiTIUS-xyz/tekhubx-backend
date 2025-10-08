<?php

namespace App\Http\Controllers\Client\project;

use App\Models\Project;
use Illuminate\Http\Request;
use App\Utils\ServerErrorMask;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\SecondayAccountOwner;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\ProjectResource;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
{
    public function index()
    {
        try {

            $project = Project::where('uuid', Auth::user()->uuid)->get();

            return response()->json([
                'status' => 'success',
                'project' => ProjectResource::collection($project),
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
            'title' => 'required|string',
            'default_client_id' => 'nullable',
            'project_manager_id' => 'nullable',
            'bank_account_id' => 'nullable',
            'provider_penalty' => 'nullable',
            'name' => 'nullable|string',
            'phone' => 'nullable|string',
            'phone_ext' => 'nullable|string',
            'email' => 'nullable|email',
            'secondary_phone' => 'nullable|string',
            'secondary_ext' => 'nullable|string',
            'auto_dispatch' => 'nullable|in:Yes,No',
            'notification_enabled' => 'nullable|in:Yes,No',
            'other' => 'nullable|string',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Create Secondary Account Owner if necessary
            $account = new SecondayAccountOwner();
            $account->name = $request->name;
            $account->phone = $request->phone;
            $account->phone_ext = $request->phone_ext;
            $account->email = $request->email;
            $account->secondary_phone = $request->secondary_phone;
            $account->secondary_ext = $request->secondary_ext;
            $account->save();

            // Create Project
            $project = new Project();
            $project->uuid = Auth::user()->uuid;
            $project->title = $request->title;
            $project->default_client_id = $request->default_client_id;
            $project->project_manager_id = $request->project_manager_id;
            $project->bank_account_id = $request->bank_account_id;
            $project->provider_penalty = $request->provider_penalty;
            $project->secondary_account_owner_id = $account->id;
            $project->auto_dispatch = $request->auto_dispatch;
            $project->notification_enabled = $request->notification_enabled;
            $project->other = $request->other;

            $project->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'New project inserted',
                'project' => new ProjectResource($project),
            ]);
        } catch (\Exception $error) {
            DB::rollBack();
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['An error occurred while creating the project.']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }

    public function edit($id)
    {
        $project = Project::with('secondary_acc')->find($id);

        if (!$project) {
            return response()->json([
                'status' => 'error',
                'message' => 'Project not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Project retrieved successfully',
            'data' => new ProjectResource($project), // Assuming ProjectResource exists
        ]);
    }


    // public function update(Request $request, $id)
    // {
    //     $rules = [
    //         'title' => 'required|string',
    //         'default_client_id' => 'nullable',
    //         'project_manager_id' => 'nullable',
    //         'bank_account_id' => 'nullable',
    //         'provider_penalty' => 'nullable',
    //         'name' => 'nullable|string',
    //         'phone' => 'nullable|string',
    //         'phone_ext' => 'nullable|string|max:10',
    //         'email' => 'nullable|email',
    //         'secondary_account_owner_id' => 'nullable',
    //         'secondary_phone' => 'nullable|string',
    //         'secondary_ext' => 'nullable|string|max:10',
    //         'auto_dispatch' => 'nullable|in:Yes,No',
    //         'notification_enabled' => 'nullable|in:Yes,No',
    //         'other' => 'nullable|string',
    //     ];

    //     $validator = Validator::make($request->all(), $rules);

    //     if ($validator->fails()) {
    //         $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
    //         return response()->json([
    //             'errors' => $formattedErrors,
    //             'payload' => null,
    //         ], 500);
    //     }

    //     try {
    //         // update Secondary Account Owner
    //         $account = SecondayAccountOwner::query()
    //             ->findOrFail($request->secondary_account_owner_id);

    //         $account->name = $request->name;
    //         $account->phone = $request->phone;
    //         $account->phone_ext = $request->phone_ext;
    //         $account->email = $request->email;
    //         $account->secondary_phone = $request->secondary_phone;
    //         $account->secondary_ext = $request->secondary_ext;
    //         $account->save();

    //         $project = Project::query()
    //             ->where('uuid', Auth::user()->uuid)
    //             ->findOrFail($id);

    //         $project->title = $request->title;
    //         $project->default_client_id = $request->default_client_id;
    //         $project->project_manager_id = $request->project_manager_id;
    //         $project->bank_account_id = $request->bank_account_id;
    //         $project->provider_penalty = $request->provider_penalty;
    //         $project->secondary_account_owner_id = $account->id;
    //         $project->auto_dispatch = $request->auto_dispatch;
    //         $project->notification_enabled = $request->notification_enabled;
    //         $project->other = $request->other;

    //         $project->save();

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Project Successfully Updated',
    //             'project' => new ProjectResource($project->refresh()),
    //         ]);
    //     } catch (\Exception $error) {
    //         Log::error($error);
    //         $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
    //         return response()->json([
    //             'errors' => $formattedErrors,
    //             'payload' => null,
    //         ], 500);
    //     }
    // }
    public function update(Request $request, $id)
    {
        $rules = [
            'title' => 'nullable|string',
            'default_client_id' => 'nullable',
            'project_manager_id' => 'nullable',
            'bank_account_id' => 'nullable',
            'provider_penalty' => 'nullable',
            'name' => 'nullable|string',
            'phone' => 'nullable|string',
            'phone_ext' => 'nullable|string',
            'email' => 'nullable|email',
            'secondary_phone' => 'nullable|string',
            'secondary_ext' => 'nullable|string',
            'auto_dispatch' => 'nullable|in:Yes,No',
            'notification_enabled' => 'nullable|in:Yes,No',
            'other' => 'nullable|string',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 400);
        }

        $project = Project::with('secondary_acc')->find($id);

        if (!$project) {
            return response()->json([
                'status' => 'error',
                'message' => 'Project not found',
            ], 404);
        }

        try {
            DB::beginTransaction();

            // Update Secondary Account Owner if exists
            $account = $project->secondary_acc;
            if ($account) {
                $account->name = $request->name ?? $account->name;
                $account->phone = $request->phone ?? $account->phone;
                $account->phone_ext = $request->phone_ext ?? $account->phone_ext;
                $account->email = $request->email ?? $account->email;
                $account->secondary_phone = $request->secondary_phone ?? $account->secondary_phone;
                $account->secondary_ext = $request->secondary_ext ?? $account->secondary_ext;
                $account->save();
            }

            // Update Project fields
            $project->title = $request->title ?? $project->title;
            $project->default_client_id = $request->default_client_id ?? $project->default_client_id;
            $project->project_manager_id = $request->project_manager_id ?? $project->project_manager_id;
            $project->bank_account_id = $request->bank_account_id ?? $project->bank_account_id;
            $project->provider_penalty = $request->provider_penalty ?? $project->provider_penalty;
            $project->auto_dispatch = $request->auto_dispatch ?? $project->auto_dispatch;
            $project->notification_enabled = $request->notification_enabled ?? $project->notification_enabled;
            $project->other = $request->other ?? $project->other;

            $project->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Project updated successfully',
            ]);
        } catch (\Exception $error) {
            DB::rollBack();
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['An error occurred while updating the project.']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }


    public function findProject(Request $request)
    {
        $rules = [
            'project_id' => 'required|integer',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 400);
        }

        try {

            $project = Project::query()
                ->where('uuid', Auth::user()->uuid)
                ->findOrFail($request->project_id);

            return response()->json([
                'status' => 'success',
                'project' => new ProjectResource($project),
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

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            // Find the project for the authenticated user
            $project = Project::withCount('workOrders')
                ->where('uuid', Auth::user()->uuid)
                ->findOrFail($id);

            // Prevent deletion if work orders exist
            if ($project->work_orders_count > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete project, it is associated with existing work orders.',
                ], 400);
            }

            // Delete the project
            $project->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Project deleted successfully.',
            ]);
        } catch (\Exception $error) {
            DB::rollBack();

            Log::error($error);

            return response()->json([
                'status' => 'error',
                'message' => 'Server error. Please try again later.',
            ], 500);
        }
    }
}
