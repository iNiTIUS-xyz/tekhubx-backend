<?php

namespace App\Http\Controllers\Admin\frontend\FrontendService;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Classes\FileUploadClass;
use App\Helpers\ApiResponseHelper;
use App\Http\Resources\Admin\FrontendServiceResource;
use App\Models\FrontendService;
use App\Utils\ServerErrorMask;

class FrontendServiceController extends Controller
{
    protected $fileUpload;

    public function __construct(FileUploadClass $fileUpload)
    {
        $this->fileUpload = $fileUpload;

        // $this->middleware('permission:frontend_service,frontend_service.list')->only(['index']);
        $this->middleware('permission:frontend_service.create_store')->only(['store']);
        $this->middleware('permission:frontend_service.edit')->only(['edit']);
        $this->middleware('permission:frontend_service.update')->only(['update']);
        $this->middleware('permission:frontend_service.delete')->only(['destroy']);
    }

    public function index()
    {
        try {
            $frontendService = FrontendService::query()
                ->with(['frontendServiceCategory'])
                ->get();

            return response()->json([
                'status' => 'success',
                'frontendService' => FrontendServiceResource::collection($frontendService),
            ]);
        } catch (\Exception $e) {
            Log::error('Frontend Service query not found' . $e->getMessage());
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
            'title' => 'required',
            'frontend_service_category_id' => 'required|exists:frontend_service_categories,id',
            'banner_image' => 'required|mimes:png,jpg,jpeg',
            'short_description' => 'required|max:180',
            'description' => 'required|string',
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
            $frontendService = new FrontendService();
            $frontendService->title = $request->title;
            $frontendService->frontend_service_category_id = $request->frontend_service_category_id;
            $frontendService->slug = strtolower(str_replace(' ', '-', $request->title)) . '-' . date('YmdHis');
            $frontendService->short_description = $request->short_description;
            if ($request->hasFile("banner_image")) {
                $image_url = $this->fileUpload->imageUploader($request->file('banner_image'), 'frontend_service', 800, 600);
                $frontendService->banner_image = $image_url;
            }
            $frontendService->description = $request->description;
            $frontendService->status = $request->status;
            $frontendService->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Frontend Service Successfully Save',
                'frontendService' => new FrontendServiceResource($frontendService),
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
            $frontendService = FrontendService::query()
                ->with('frontendServiceCategory')
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Frontend Service Successfully Update',
                'frontendService' => new FrontendServiceResource($frontendService),
            ]);
        } catch (\Exception $e) {
            Log::error('Frontend Service query not found' . $e->getMessage());
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
            'title' => 'nullable',
            'frontend_service_category_id' => 'nullable|exists:frontend_service_categories,id',
            'banner_image' => 'nullable|mimes:png,jpg,jpeg|max:5120',
            'short_description' => 'nullable|max:180',
            'description' => 'nullable|string',
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

            $frontendService = FrontendService::query()->findOrFail($id);

            // Update fields only if they are present in the request
            if ($request->filled('title')) {
                $frontendService->title = $request->title;
                $frontendService->slug = strtolower(str_replace(' ', '-', $request->title)) . '-' . date('YmdHis');
            }
            if ($request->filled('frontend_service_category_id')) {
                $frontendService->frontend_service_category_id = $request->frontend_service_category_id;
            }
            if ($request->filled('short_description')) {
                $frontendService->short_description = $request->short_description;
            }
            if ($request->filled('description')) {
                $frontendService->description = $request->description;
            }
            if ($request->filled('status')) {
                $frontendService->status = $request->status;
            }

            // Handle banner image if provided
            if ($request->hasFile('banner_image')) {
                $this->fileUpload->fileUnlink($frontendService->banner_image); // Remove old image
                $image_url = $this->fileUpload->imageUploader($request->file('banner_image'), 'frontend_service', 800, 600);
                $frontendService->banner_image = $image_url;
            }

            $frontendService->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Frontend Service Successfully Updated',
                'frontendService' => new FrontendServiceResource($frontendService->refresh()),
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

            $frontendService = FrontendService::query()
                ->findOrFail($id);

            $this->fileUpload->fileUnlink($frontendService->banner_image);

            $frontendService->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Frontend Service Successfully Delete',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Frontend Service query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }
}
