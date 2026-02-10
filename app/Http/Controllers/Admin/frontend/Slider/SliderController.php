<?php

namespace App\Http\Controllers\Admin\frontend\Slider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Classes\FileUploadClass;
use App\Helpers\ApiResponseHelper;
use App\Http\Resources\Admin\SliderResource;
use App\Models\Slider;
use Illuminate\Support\Facades\DB;
use App\Utils\ServerErrorMask;

class SliderController extends Controller
{
    protected $fileUpload;

    public function __construct(FileUploadClass $fileUpload)
    {
        $this->fileUpload = $fileUpload;
    }

    public function index()
    {
        try {
            $sliders = Slider::query()->get();

            return response()->json([
                'status' => 'success',
                'sliders' => SliderResource::collection($sliders),
            ]);
        } catch (\Exception $e) {
            Log::error('Slider query not found' . $e->getMessage());
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
            'image' => 'required|mimes:png,jpg,jpeg|max:10240',
            'short_description' => 'required',
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
            $sliders = new Slider();
            $sliders->title = $request->title;
            if ($request->hasFile("image")) {
                $image_url = $this->fileUpload->imageUploader($request->file('image'), 'slider');
                $sliders->image = $image_url;
            }

            $sliders->short_description = $request->short_description;
            $sliders->status = $request->status;

            $sliders->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Slider Successfully Save',
                'slider' => new SliderResource($sliders),
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
            $sliders = Slider::query()
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'sliders' => new SliderResource($sliders),
            ]);
        } catch (\Exception $e) {
            Log::error('Slider query not found' . $e->getMessage());
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
            'image' => 'nullable|mimes:png,jpg,jpeg|max:10240',
            'short_description' => 'required',
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
            $sliders = Slider::query()
                ->findOrFail($id);

            $sliders->title = $request->title;
            if ($request->hasFile("image")) {
                $this->fileUpload->fileUnlink($sliders->image);
                $image_url = $this->fileUpload->imageUploader($request->file('image'), 'slider');
                $sliders->image = $image_url;
            }

            $sliders->short_description = $request->short_description;
            $sliders->status = $request->status;
            $sliders->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Slider Successfully Update',
                'slider' => new SliderResource($sliders->refresh()),
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

            $sliders = Slider::query()
                ->findOrFail($id);

            $this->fileUpload->fileUnlink($sliders->image);

            $sliders->delete();

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Slider Successfully Delete',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Slider query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }
}
