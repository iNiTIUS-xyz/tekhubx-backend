<?php

namespace App\Http\Controllers\Admin\frontend\Brand;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Classes\FileUploadClass;
use App\Helpers\ApiResponseHelper;
use App\Http\Resources\Admin\BrandResource;
use App\Models\Brand;
use App\Utils\ServerErrorMask;
use Illuminate\Support\Facades\DB;

class BrandController extends Controller
{
    protected $fileUpload;

    public function __construct(FileUploadClass $fileUpload)
    {
        $this->fileUpload = $fileUpload;

        // $this->middleware('permission:brand,brand.list')->only(['index']);
        $this->middleware('permission:brand.create_store')->only(['store']);
        $this->middleware('permission:brand.edit')->only(['edit']);
        $this->middleware('permission:brand.update')->only(['update']);
        $this->middleware('permission:brand.delete')->only(['destroy']);
    }

    public function index()
    {
        try {
            $brands = Brand::query()->get();

            return response()->json([
                'status' => 'success',
                'brand' => BrandResource::collection($brands),
            ]);
        } catch (\Exception $e) {
            Log::error('Brand query not found' . $e->getMessage());
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
            'image' => 'required|mimes:png,jpg,jpeg|max:5120',
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
            $brands = new Brand();
            $brands->title = $request->title;
            if ($request->hasFile("image")) {
                $image_url = $this->fileUpload->imageUploader($request->file('image'), 'brand', 200, 200);
                $brands->image = $image_url;
            }

            $brands->status = $request->status;

            $brands->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Brand Successfully Save',
                'brand' => new BrandResource($brands),
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
            $brands = Brand::query()
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'brand' => new BrandResource($brands),
            ]);
        } catch (\Exception $e) {
            Log::error('Brand query not found' . $e->getMessage());
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
            'title' => 'required',
            'image' => 'nullable|mimes:png,jpg,jpeg|max:5120',
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
            $brands = Brand::query()
                ->findOrFail($id);

            $brands->title = $request->title;
            if ($request->hasFile("image")) {
                $this->fileUpload->fileUnlink($brands->image);
                $image_url = $this->fileUpload->imageUploader($request->file('image'), 'brand', 200, 200);
                $brands->image = $image_url;
            }
            $brands->status = $request->status;
            $brands->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Brand Successfully Update',
                'brand' => new BrandResource($brands->refresh()),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update filed' . $e->getMessage());
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

            $brands = Brand::query()
                ->findOrFail($id);

            $this->fileUpload->fileUnlink($brands->image);

            $brands->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Brand Successfully Delete',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Brand query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }
}
