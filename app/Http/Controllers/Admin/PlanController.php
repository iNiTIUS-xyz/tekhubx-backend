<?php

namespace App\Http\Controllers\Admin;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use App\Utils\ServerErrorMask;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Admin\PlanResource;

class PlanController extends Controller
{
    public function index()
    {
        try {

            $subscription = Subscription::with('plan')->where('uuid', Auth::user()->uuid)->where('status', 'Active')->first();

            $plan = Plan::where('status', 'Active')->get();

            return response()->json([
                'status' => 'success',
                'subscription' => $subscription->status ?? "Inactive",
                'subscription_data' => $subscription,
                'plan' => PlanResource::collection($plan),
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
            'name' => 'required|string|max:255',
            'details' => 'required',
            'amount' => 'required',
            'point' => 'required',
            'work_order_fee' => 'required',
            'expired_month' => 'required',
            'status' => 'nullable|in:Active,Inactive',
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
            $plan = Plan::create($request->all());
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'New data inserted',
                'plan' => new PlanResource($plan),
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

    public function edit(string $id)
    {
        try {

            $plan = Plan::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'plan' => new PlanResource($plan),
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
            'name' => 'nullable|string|max:255',
            'details' => 'nullable',
            'amount' => 'nullable',
            'point' => 'nullable',
            'work_order_fee' => 'nullable',
            'expired_month' => 'nullable',
            'status' => 'nullable|in:Active,Inactive',
        ];

        // Validate request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(
                ApiResponseHelper::VALIDATION_ERROR,
                $validator->errors()->toArray()
            );
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422); // Use 422 Unprocessable Entity for validation errors
        }

        try {
            DB::beginTransaction();
            // Find the plan by ID
            $plan = Plan::findOrFail($id);

            // Filter out null values and only update fields that are present in the request
            $validatedData = array_filter($request->all(), function ($value) {
                return !is_null($value);
            });

            // Update the plan with the filtered data
            $plan->update($validatedData);

            DB::commit();
            // Return success response
            return response()->json([
                'status' => 'success',
                'message' => 'Data updated successfully',
                'plan' => new PlanResource($plan),
            ]);
        } catch (\Exception $error) {
            DB::rollBack();
            // Log the error and return system error response
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(
                ApiResponseHelper::SYSTEM_ERROR,
                [ServerErrorMask::SERVER_ERROR]
            );
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500); // Use 500 for system errors
        }
    }

    public function destroy(string $id)
    {
        try {
            DB::beginTransaction();

            Plan::findOrFail($id)->delete();

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Data deleted successfully',
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
