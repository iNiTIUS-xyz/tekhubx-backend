<?php

namespace App\Http\Controllers\Admin\service;

use App\Models\Service;
use Illuminate\Http\Request;
use App\Utils\ServerErrorMask;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\WorkServiceResource;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    public function Service(Request $request)
    {

        $rules = [
            'name' => 'required|string|max:100',
            'work_sub_category_id' => 'required|integer',
            'status' => 'nullable|in:Active,Inactive',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
            ], 500);
        }

        try {
            DB::beginTransaction();
            $service = new Service();
            $service->name = $request->name;
            $service->work_sub_category_id = $request->work_sub_category_id;
            $service->status = $request->status;
            $service->save();
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'New service inserted',
                'services' => $service,
            ]);
        } catch (\Exception $error) {
            DB::rollBack();
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
            ], 500);
        }
    }

    public function ViewService()
    {

        try {
            $services = Service::with('workSubCategory')->get();
            // $groupedServices = $services->groupBy(function ($service) {
            //     return $service?->workSubCategory?->name;
            // });
            // // Prepare the response data
            // $responseData = [];
            // foreach ($groupedServices as $workSubCategoryName => $servicesGroup) {
            //     $responseData[] = [
            //         'work_sub_category_data' => [
            //             'name' => $workSubCategoryName,
            //             'services' => WorkServiceResource::collection($servicesGroup),
            //         ],
            //     ];
            // }

            return response()->json([
                'status' => 'success',
                'services' => WorkServiceResource::collection($services),
            ]);
        } catch (\Exception $error) {
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
            ], 500);
        }
    }

    public function EditService($id)
    {

        try {

            $services = Service::query()
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'services' => new WorkServiceResource($services),
            ]);

        } catch (\Exception $error) {
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
            ], 500);
        }
    }

    public function UpdateService(Request $request, $id)
    {
        $rules = [
            'name' => 'required|string|max:100',
            'work_sub_category_id' => 'required|integer',
            'status' => 'nullable|in:Active,Inactive',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
            ], 500);
        }
        try {
            DB::beginTransaction();

            $service = Service::findOrFail($id);

            if (!empty($request->name)) {
                $service->name = $request->name;
            }
            if (!empty($request->work_sub_category_id)) {
                $service->work_sub_category_id = $request->work_sub_category_id;
            }

            $service->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Data updated',
                'services' => $service,
            ]);

        } catch (\Exception $error) {
            DB::rollBack();
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
            ], 500);
        }
    }

    public function deleteService($id)
    {
        try {
            DB::beginTransaction();

            $service = Service::findOrFail($id);
            $service->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Service deleted successfully'
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
