<?php

namespace App\Http\Controllers\Admin\frontend\Blog;

use App\Helpers\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\BlogResource;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Classes\FileUploadClass;
use App\Utils\ServerErrorMask;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BlogController extends Controller
{
    protected $fileUpload;

    public function __construct(FileUploadClass $fileUpload)
    {
        $this->fileUpload = $fileUpload;
    }

    public function index()
    {
        try {
            $blogs = Blog::query()->get();

            return response()->json([
                'status' => 'success',
                'blogs' => BlogResource::collection($blogs),
            ]);
        } catch (\Exception $e) {
            Log::error('Blog query not found' . $e->getMessage());
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
            'description' => 'required',
            'tags' => 'required|array|min:1',
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
            $blogs = new Blog();
            $blogs->title = $request->title;
            $blogs->slug = strtolower(str_replace(' ', '-', $request->title));
            $blogs->description = $request->description;
            $blogs->admin_id = Auth::user()->id;
            $blogs->tags = json_encode($request->tags);

            if ($request->hasFile("image")) {
                $image_url = $this->fileUpload->imageUploader($request->file('image'), 'blog', 800, 600);
                $blogs->image = $image_url;
            }

            $blogs->status = $request->status;
            $blogs->total_view = 0;

            $blogs->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Blog Successfully Save',
                'blog' => new BlogResource($blogs),
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
            $blogs = Blog::query()
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'blog' => new BlogResource($blogs),
            ]);
        } catch (\Exception $e) {
            Log::error('Blog query not found' . $e->getMessage());
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
            'description' => 'required',
            'tags' => 'required|array|min:1',
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
            $blogs = Blog::query()
                ->findOrFail($id);
            $blogs->title = $request->title;
            $blogs->slug = strtolower(str_replace(' ', '-', $request->title));
            $blogs->description = $request->description;
            $blogs->admin_id = Auth::user()->id;
            $blogs->tags = json_encode($request->tags);

            if ($request->hasFile("image")) {
                $this->fileUpload->fileUnlink($blogs->image);
                $image_url = $this->fileUpload->imageUploader($request->file('image'), 'blog', 800, 600);
                $blogs->image = $image_url;
            }

            $blogs->status = $request->status;
            $blogs->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Blog Successfully Update',
                'blog' => new BlogResource($blogs->refresh()),
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

            $blogs = Blog::query()
                ->findOrFail($id);

            $this->fileUpload->fileUnlink($blogs->image);

            $blogs->delete();

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Blog Successfully Delete',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Blog query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }
}
