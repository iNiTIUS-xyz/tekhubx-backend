<?php

namespace App\Http\Controllers\Admin\frontend\Page;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Classes\FileUploadClass;
use App\Helpers\ApiResponseHelper;
use App\Http\Resources\Admin\PageResource;
use App\Models\Page;
use App\Utils\ServerErrorMask;
use Illuminate\Support\Facades\DB;

class PageController extends Controller
{
    protected $fileUpload;

    public function __construct(FileUploadClass $fileUpload)
    {
        $this->fileUpload = $fileUpload;
    }
    public function index()
    {
        try {
            $pages = Page::query()->get();

            return response()->json([
                'status' => 'success',
                'pages' => PageResource::collection($pages),
            ]);
        } catch (\Exception $e) {
            Log::error('Page query not found' . $e->getMessage());
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
            'page_title' => 'required',
            'short_description' => 'required',
            'description' => 'required',
            'banner_image' => 'required|mimes:png,jpg,jpeg|max:5120',
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

            $pageExist = Page::query()
                ->where('page_title', $request->page_title)
                ->get();

            if ($pageExist->count() > 0) {
                return response()->json([
                    'status' => 'success',
                    'message' => $request->page_title . ' already exist. You can not use same page for multiple times',
                ]);
            }

            $pages = new Page();

            $pages->page_title = $request->page_title;
            $pages->page_slug = strtolower(str_replace(' ', '-', $request->page_title));
            $pages->short_description = $request->short_description;

            if ($request->hasFile("banner_image")) {
                $image_url = $this->fileUpload->imageUploader($request->file('banner_image'), 'page');
                $pages->banner_image = $image_url;
            }

            $pages->description = $request->description;
            $pages->status = $request->status;

            $pages->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Page Successfully Save',
                'page' => new PageResource($pages),
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
            $pages = Page::query()
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'pages' => new PageResource($pages),
            ]);
        } catch (\Exception $e) {
            Log::error('Page query not found' . $e->getMessage());
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
            'page_title' => 'required',
            'short_description' => 'required',
            'description' => 'required',
            'banner_image' => 'nullable
            |mimes:png,jpg,jpeg|max:5120',
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

            $pageExist = Page::query()
                ->where('page_title', $request->page_title)
                ->where('id', '!=', $id)
                ->get();

            if ($pageExist->count() > 0) {
                return response()->json([
                    'status' => 'success',
                    'message' => $request->page_title . ' already exist. You can not use same page for multiple times',
                ]);
            }

            $pages = Page::query()
                ->findOrFail($id);

            $pages->page_title = $request->page_title;
            $pages->page_slug = strtolower(str_replace(' ', '-', $request->page_title));
            $pages->short_description = $request->short_description;
            if ($request->hasFile("banner_image")) {
                $this->fileUpload->fileUnlink($pages->banner_image);
                $image_url = $this->fileUpload->imageUploader($request->file('banner_image'), 'page');
                $pages->banner_image = $image_url;
            }
            $pages->description = $request->description;
            $pages->status = $request->status;
            $pages->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Page Successfully Update',
                'page' => new PageResource($pages->refresh()),
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

            $pages = Page::query()
                ->findOrFail($id);

            $this->fileUpload->fileUnlink($pages->banner_image);
            $pages->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Page Successfully Deleted',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delete filed' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }
}
