<?php

namespace App\Http\Controllers\Admin\frontend\FrontendService;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Classes\FileUploadClass;
use App\Helpers\ApiResponseHelper;
use App\Http\Resources\Admin\FrontendServiceCategoryResource;
use App\Models\FrontendServiceCategory;
use App\Utils\ServerErrorMask;

class FrontendServiceCategoryController extends Controller
{
    protected $fileUpload;

    public function __construct(FileUploadClass $fileUpload)
    {
        $this->fileUpload = $fileUpload;
    }

    public function index()
    {
        try {
            $serviceCategory = FrontendServiceCategory::query()->get();

            return response()->json([
                'status' => 'success',
                'frontendServiceCategory' => FrontendServiceCategoryResource::collection($serviceCategory),
            ]);
        } catch (\Exception $e) {
            Log::error('Service Category query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|unique:frontend_service_categories,name',
            'image' => 'required|mimes:png,jpg,jpeg|max:10240',
            'header_title' => 'required',
            'sub_header' => 'required',
            'description' => 'required',
            'status' => 'nullable|in:Active,Inactive',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
            ], 422);
        }

        try {
            DB::beginTransaction();
            $serviceCategory = new FrontendServiceCategory();
            $serviceCategory->name = $request->name;
            $serviceCategory->header_title = $request->header_title;
            $serviceCategory->sub_header = $request->sub_header;
            $serviceCategory->description = $request->description;
            $serviceCategory->slug = strtolower(str_replace(' ', '-', $request->name));
            if ($request->hasFile("image")) {
                $image_url = $this->fileUpload->imageUploader($request->file('image'), 'service_category');
                $serviceCategory->image = $image_url;
            }
            $serviceCategory->status = $request->status;
            $serviceCategory->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Service Category Successfully Save',
                'service_categories' => new FrontendServiceCategoryResource($serviceCategory),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Store filed' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function edit($id)
    {
        try {
            $serviceCategory = FrontendServiceCategory::query()
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'frontendServiceCategory' => new FrontendServiceCategoryResource($serviceCategory),
            ]);
        } catch (\Exception $e) {
            Log::error('Service Category query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $rules = [
            'name' => 'nullable|unique:frontend_service_categories,name,' . $id,
            'image' => 'nullable|mimes:png,jpg,jpeg|max:10240',
            'header_title' => 'nullable',
            'sub_header' => 'nullable',
            'description' => 'nullable',
            'status' => 'nullable|in:Active,Inactive',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
            ], 422);
        }

        try {
            DB::beginTransaction();

            $serviceCategory = FrontendServiceCategory::query()->findOrFail($id);

            // Update fields only if they are present in the request
            if ($request->filled('name')) {
                $serviceCategory->name = $request->name;
                $serviceCategory->slug = strtolower(str_replace(' ', '-', $request->name));
            }
            if ($request->filled('header_title')) {
                $serviceCategory->header_title = $request->header_title;
            }
            if ($request->filled('sub_header')) {
                $serviceCategory->sub_header = $request->sub_header;
            }
            if ($request->filled('description')) {
                $serviceCategory->description = $request->description;
            }
            if ($request->filled('status')) {
                $serviceCategory->status = $request->status;
            }

            // Handle image update if provided
            if ($request->hasFile('image')) {
                $this->fileUpload->fileUnlink($serviceCategory->image); // Remove old image
                $image_url = $this->fileUpload->imageUploader($request->file('image'), 'service_category');
                $serviceCategory->image = $image_url;
            }

            $serviceCategory->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Service Category successfully updated',
                'service_categories' => new FrontendServiceCategoryResource($serviceCategory->refresh()),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update failed: ' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $serviceCategory = FrontendServiceCategory::query()
                ->with('frontendService')
                ->findOrFail($id);

            if ($serviceCategory->frontendService->count() > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Frontend Service Exist this data. You can not delete this category',
                ], 402);
            }

            $this->fileUpload->fileUnlink($serviceCategory->image);

            $serviceCategory->delete();

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Service Category Successfully Delete',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Service Category query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }
}
