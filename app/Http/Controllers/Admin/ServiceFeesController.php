<?php

namespace App\Http\Controllers\Admin;

use App\Models\Plan;
use App\Models\ServiceFees;
use Illuminate\Http\Request;
use App\Utils\ServerErrorMask;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Admin\PlanResource;
use App\Http\Resources\Admin\ServiceFeesResource;

class ServiceFeesController extends Controller
{
    public function index()
    {
        try {

            $service_fees = ServiceFees::all();
            $plans = Plan::all();
            return response()->json([
                'status' => 'success',
                'service_fees' => ServiceFeesResource::collection($service_fees),
                'plans' => PlanResource::collection($plans),
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

            'plan_id' => 'required|exists:plans,id',
            'name' => 'required|string|max:255',
            'percentage' => 'required|numeric|min:0|max:100',
            'status' => 'nullable',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }

        DB::beginTransaction();

        try {
            $serviceFee = ServiceFees::create($request->all());

            DB::commit();

            return response()->json([
                'message' => 'Service fee created successfully',
                'data' => $serviceFee,
            ], 201);
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
            $service_fees = ServiceFees::findOrFail($id);
            return response()->json([
                'status' => 'success',
                'service_fees' => new ServiceFeesResource($service_fees),
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
            'plan_id' => 'nullable|exists:plans,id',
            'name' => 'nullable|string|max:255',
            'percentage' => 'nullable|numeric|min:0|max:100',
            'status' => 'nullable',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }

        DB::beginTransaction();

        try {
            $serviceFee = ServiceFees::findOrFail($id);
            $serviceFee->plan_id = $request->plan_id ?? $serviceFee->plan_id;
            $serviceFee->name = $request->name ?? $serviceFee->name;
            $serviceFee->percentage = $request->percentage ?? $serviceFee->percentage;
            $serviceFee->status = $request->status ?? $serviceFee->status;
            $serviceFee->save();

            DB::commit();
            return response()->json([
                'message' => 'Service fee updated successfully',
                'data' => $serviceFee,
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

    public function deleteServiceFee($id)
    {
        try {
            DB::beginTransaction();

            $serviceFee = ServiceFees::findOrFail($id);
            $serviceFee->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Service fee deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }
}
