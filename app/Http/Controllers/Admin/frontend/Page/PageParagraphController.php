<?php

namespace App\Http\Controllers\Admin\frontend\Page;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Classes\FileUploadClass;
use App\Helpers\ApiResponseHelper;
use App\Models\PageParagraph;
use App\Utils\ServerErrorMask;
use Illuminate\Support\Facades\DB;

class PageParagraphController extends Controller
{
    protected $fileUpload;

    public function __construct(FileUploadClass $fileUpload)
    {
        $this->fileUpload = $fileUpload;
    }
    public function index()
    {
        try {
            $paras = PageParagraph::query()->get();

            return response()->json([
                'status' => 'success',
                'paras' => $paras,
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
            'paragraph_one_image' => 'nullable|mimes:png,jpg,jpeg|max:10240',
            'paragraph_one_title' => 'nullable|string|max:255',
            'paragraph_one_description' => 'nullable|string',

            'paragraph_two_image' => 'nullable|mimes:png,jpg,jpeg|max:10240',
            'paragraph_two_title' => 'nullable|string|max:255',
            'paragraph_two_description' => 'nullable|string',

            'paragraph_three_image' => 'nullable|mimes:png,jpg,jpeg|max:10240',
            'paragraph_three_title' => 'nullable|string|max:255',
            'paragraph_three_description' => 'nullable|string',
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

            $pageParagraph = new PageParagraph();

            // Paragraph 1
            $pageParagraph->paragraph_one_title = $request->paragraph_one_title;
            $pageParagraph->paragraph_one_description = $request->paragraph_one_description;
            if ($request->hasFile('paragraph_one_image')) {
                $imageUrl = $this->fileUpload->imageUploader($request->file('paragraph_one_image'), 'page_paragraphs');
                $pageParagraph->paragraph_one_image = $imageUrl;
            }

            // Paragraph 2
            $pageParagraph->paragraph_two_title = $request->paragraph_two_title;
            $pageParagraph->paragraph_two_description = $request->paragraph_two_description;
            if ($request->hasFile('paragraph_two_image')) {
                $imageUrl = $this->fileUpload->imageUploader($request->file('paragraph_two_image'), 'page_paragraphs');
                $pageParagraph->paragraph_two_image = $imageUrl;
            }

            // Paragraph 3
            $pageParagraph->paragraph_three_title = $request->paragraph_three_title;
            $pageParagraph->paragraph_three_description = $request->paragraph_three_description;
            if ($request->hasFile('paragraph_three_image')) {
                $imageUrl = $this->fileUpload->imageUploader($request->file('paragraph_three_image'), 'page_paragraphs');
                $pageParagraph->paragraph_three_image = $imageUrl;
            }

            $pageParagraph->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Page paragraphs successfully created.',
                'data' => $pageParagraph,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Store failed: ' . $e->getMessage());

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
            $para = PageParagraph::query()
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'para' => $para
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
            'paragraph_one_image' => 'nullable|mimes:png,jpg,jpeg|max:10240',
            'paragraph_one_title' => 'nullable|string|max:255',
            'paragraph_one_description' => 'nullable|string',

            'paragraph_two_image' => 'nullable|mimes:png,jpg,jpeg|max:10240',
            'paragraph_two_title' => 'nullable|string|max:255',
            'paragraph_two_description' => 'nullable|string',

            'paragraph_three_image' => 'nullable|mimes:png,jpg,jpeg|max:10240',
            'paragraph_three_title' => 'nullable|string|max:255',
            'paragraph_three_description' => 'nullable|string',
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

            // Fetch the page paragraph record
            $pageParagraph = PageParagraph::findOrFail($id);

            // Update paragraph 1
            if ($request->has('paragraph_one_title')) {
                $pageParagraph->paragraph_one_title = $request->paragraph_one_title;
            }
            if ($request->has('paragraph_one_description')) {
                $pageParagraph->paragraph_one_description = $request->paragraph_one_description;
            }
            if ($request->hasFile('paragraph_one_image')) {
                // Delete old image if exists
                if ($pageParagraph->paragraph_one_image) {
                    $this->fileUpload->fileUnlink($pageParagraph->paragraph_one_image);
                }
                // Upload new image
                $imageUrl = $this->fileUpload->imageUploader($request->file('paragraph_one_image'), 'page_paragraphs');
                $pageParagraph->paragraph_one_image = $imageUrl;
            }

            // Update paragraph 2
            if ($request->has('paragraph_two_title')) {
                $pageParagraph->paragraph_two_title = $request->paragraph_two_title;
            }
            if ($request->has('paragraph_two_description')) {
                $pageParagraph->paragraph_two_description = $request->paragraph_two_description;
            }
            if ($request->hasFile('paragraph_two_image')) {
                // Delete old image if exists
                if ($pageParagraph->paragraph_two_image) {
                    $this->fileUpload->fileUnlink($pageParagraph->paragraph_two_image);
                }
                // Upload new image
                $imageUrl = $this->fileUpload->imageUploader($request->file('paragraph_two_image'), 'page_paragraphs');
                $pageParagraph->paragraph_two_image = $imageUrl;
            }

            // Update paragraph 3
            if ($request->has('paragraph_three_title')) {
                $pageParagraph->paragraph_three_title = $request->paragraph_three_title;
            }
            if ($request->has('paragraph_three_description')) {
                $pageParagraph->paragraph_three_description = $request->paragraph_three_description;
            }
            if ($request->hasFile('paragraph_three_image')) {
                // Delete old image if exists
                if ($pageParagraph->paragraph_three_image) {
                    $this->fileUpload->fileUnlink($pageParagraph->paragraph_three_image);
                }
                // Upload new image
                $imageUrl = $this->fileUpload->imageUploader($request->file('paragraph_three_image'), 'page_paragraphs');
                $pageParagraph->paragraph_three_image = $imageUrl;
            }

            $pageParagraph->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Page paragraphs successfully updated.',
                'data' => $pageParagraph->refresh(),
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

            // Find the page paragraph by ID
            $pageParagraph = PageParagraph::findOrFail($id);

            // Delete associated images if they exist
            if ($pageParagraph->paragraph_one_image) {
                $this->fileUpload->fileUnlink($pageParagraph->paragraph_one_image);
            }
            if ($pageParagraph->paragraph_two_image) {
                $this->fileUpload->fileUnlink($pageParagraph->paragraph_two_image);
            }
            if ($pageParagraph->paragraph_three_image) {
                $this->fileUpload->fileUnlink($pageParagraph->paragraph_three_image);
            }

            // Delete the page paragraph
            $pageParagraph->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Page paragraph successfully deleted.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delete failed: ' . $e->getMessage());

            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }
}
