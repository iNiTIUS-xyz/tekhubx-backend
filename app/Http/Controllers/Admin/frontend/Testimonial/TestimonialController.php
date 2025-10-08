<?php

namespace App\Http\Controllers\Admin\frontend\Testimonial;

use App\Helpers\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Classes\FileUploadClass;
use App\Http\Resources\Admin\TestimonialResource;
use App\Utils\ServerErrorMask;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TestimonialController extends Controller
{
    protected $fileUpload;

    public function __construct(FileUploadClass $fileUpload)
    {
        $this->fileUpload = $fileUpload;

        // $this->middleware('permission:testimonial,testimonial.list')->only(['index']);
        $this->middleware('permission:testimonial.create_store')->only(['store']);
        $this->middleware('permission:testimonial.edit')->only(['edit']);
        $this->middleware('permission:testimonial.update')->only(['update']);
        $this->middleware('permission:testimonial.delete')->only(['destroy']);
    }

    public function index()
    {
        try {
            $testimonials = Testimonial::query()->get();

            return response()->json([
                'status' => 'success',
                'testimonials' => TestimonialResource::collection($testimonials),
            ]);
        } catch (\Exception $e) {
            Log::error('Testimonial query not found' . $e->getMessage());
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
            'author_name' => 'required',
            'designation' => 'required',
            'quote' => 'required',
            'image' => 'required|mimes:png,jpg,jpeg|max:5120',
            'review_star' => 'nullable|in:1,2,3,4,5',
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
            $testimonials = new Testimonial();
            $testimonials->author_name = $request->author_name;
            $testimonials->designation = $request->designation;
            $testimonials->quote = $request->quote;
            $testimonials->review_star = $request->review_star;


            if ($request->hasFile("image")) {
                $image_url = $this->fileUpload->imageUploader($request->file('image'), 'testimonials', 640, 623);
                $testimonials->image = $image_url;
            }

            $testimonials->status = $request->status;


            $testimonials->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Testimonial Successfully Save',
                'testimonials' => new TestimonialResource($testimonials),
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
            $testimonials = Testimonial::query()
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'testimonials' => new TestimonialResource($testimonials),
            ]);
        } catch (\Exception $e) {
            Log::error('Testimonial query not found' . $e->getMessage());
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
            'author_name' => 'required',
            'designation' => 'required',
            'quote' => 'required',
            'image' => 'nullable|mimes:png,jpg,jpeg|max:5120',
            'review_star' => 'nullable|in:1,2,3,4,5',
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
            $testimonials = Testimonial::query()
                ->findOrFail($id);

            $testimonials->author_name = $request->author_name;
            $testimonials->designation = $request->designation;
            $testimonials->quote = $request->quote;
            $testimonials->review_star = $request->review_star;

            if ($request->hasFile("image")) {
                $this->fileUpload->fileUnlink($testimonials->image);
                $image_url = $this->fileUpload->imageUploader($request->file('image'), 'testimonials', 640, 623);
                $testimonials->image = $image_url;
            }

            $testimonials->status = $request->status;


            $testimonials->save();
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Testimonial Successfully Update',
                'testimonials' => new TestimonialResource($testimonials->refresh()),
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

            $testimonials = Testimonial::query()
                ->findOrFail($id);

            $this->fileUpload->fileUnlink($testimonials->image);

            $testimonials->delete();

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Testimonial Successfully Delete',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Testimonial query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }
}
