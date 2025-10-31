<?php

namespace App\Http\Controllers\Admin\frontend\Page;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Classes\FileUploadClass;
use App\Helpers\ApiResponseHelper;
use App\Models\PageHeader;
use App\Utils\ServerErrorMask;
use Illuminate\Support\Facades\DB;

class PageHeaderController extends Controller
{
    protected $fileUpload;

    public function __construct(FileUploadClass $fileUpload)
    {
        $this->fileUpload = $fileUpload;
    }

    public function index()
    {
        try {
            $pageHeader = PageHeader::query()->first(); // Expecting only one row

            if (!$pageHeader) {
                return response()->json([
                    'status' => 'success',
                    'pageHeaders' => [],
                ]);
            }

            $pageKeys = [
                'home_page' => 'Home Page',
                'how_it_works_client' => 'How It Works (Client)',
                'how_it_works_provider' => 'How It Works (Provider)',
                'client_sign_up' => 'Client Sign Up',
                'provider_sign_up' => 'Provider Sign Up',
                'client_pricing' => 'Client Pricing',
                'contact' => 'Contact',
                'about' => 'About',
                'mission_vision' => 'Mission & Vision',
                'faq' => 'FAQ',
                'career' => 'Career',
                'teams' => 'Teams'
            ];

            $result = [];

            foreach ($pageKeys as $key => $displayName) {
                $result[] = [
                    'id' => $pageHeader->id, // assuming all keys refer to the same page header
                    'name' => $displayName, // user-friendly
                    'key' => $key, // actual DB key (optional, if frontend still needs it)
                    'title' => $pageHeader->{"{$key}_title"},
                    'short_description' => $pageHeader->{"{$key}_short_description"},
                    'description' => $pageHeader->{"{$key}_description"},
                    'image' => $pageHeader->{"{$key}_image"},
                    'meta_keywords' => $pageHeader->{"{$key}_meta_keywords"},
                ];
            }

            return response()->json([
                'status' => 'success',
                'pageHeaders' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Page query not found: ' . $e->getMessage());
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
            'page_key' => 'required|in:home_page,how_it_works_client,how_it_works_provider,client_sign_up,provider_sign_up,client_pricing,contact,about,mission_vision,faq,career,teams',
            'title' => 'nullable|string|max:255',
            'short_description' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'image' => 'nullable|mimes:png,jpg,jpeg|max:5120',
            'meta_keywords' => 'nullable|string|max:500',
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

            $pageKey = $request->page_key;
            $pageHeader = PageHeader::firstOrNew([]);

            // Check if the page exists (for response message)
            $pageExists = PageHeader::query()
                ->whereNotNull("{$pageKey}_title")
                ->orWhereNotNull("{$pageKey}_short_description")
                ->orWhereNotNull("{$pageKey}_description")
                ->orWhereNotNull("{$pageKey}_image")
                ->orWhereNotNull("{$pageKey}_meta_keywords")
                ->exists();

            // Update only provided fields
            if ($request->has('title')) {
                $pageHeader->{"{$pageKey}_title"} = $request->title;
            }
            if ($request->has('short_description')) {
                $pageHeader->{"{$pageKey}_short_description"} = $request->short_description;
            }
            if ($request->has('description')) {
                $pageHeader->{"{$pageKey}_description"} = $request->description;
            }
            if ($request->hasFile('image')) {
                // Delete old image if it exists
                if ($pageHeader->{"{$pageKey}_image"}) {
                    $this->fileUpload->fileUnlink($pageHeader->{"{$pageKey}_image"});
                }
                $image_url = $this->fileUpload->imageUploader($request->file('image'), 'page_headers');
                $pageHeader->{"{$pageKey}_image"} = $image_url;
            }
            if ($request->has('meta_keywords')) {
                $pageHeader->{"{$pageKey}_meta_keywords"} = $request->meta_keywords;
            }

            $pageHeader->save();

            DB::commit();

            $message = $pageExists
                ? ucfirst(str_replace('_', ' ', $pageKey)) . ' successfully updated.'
                : ucfirst(str_replace('_', ' ', $pageKey)) . ' successfully created.';

            Log::info('PageHeader upsert successful', [
                'page_key' => $pageKey,
                'action' => $pageExists ? 'updated' : 'created',
            ]);

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'page' => $pageHeader,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PageHeader upsert failed: ' . $e->getMessage(), [
                'page_key' => $request->page_key,
                'trace' => $e->getTraceAsString(),
            ]);
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['Unknown error occurred']);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }
    // public function store(Request $request)
    // {
    //     $rules = [
    //         'page_key' => 'required|in:home_page,how_it_works_client,how_it_works_provider,client_sign_up,provider_sign_up,client_pricing,contact,about,mission_vision,faq,career,teams',
    //         'title' => 'required|string|max:255',
    //         'short_description' => 'nullable|string|max:500',
    //         'description' => 'nullable|string',
    //         'image' => 'nullable|mimes:png,jpg,jpeg|max:5120',
    //         'meta_keywords' => 'nullable|string|max:500',
    //     ];

    //     $validator = Validator::make($request->all(), $rules);

    //     if ($validator->fails()) {
    //         $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
    //         return response()->json([
    //             'errors' => $formattedErrors,
    //         ], 422);
    //     }

    //     try {
    //         DB::beginTransaction();

    //         $pageKey = $request->page_key;
    //         // Check if any column for the page_key is not null
    //         $pageHeaderExist = PageHeader::query()
    //             ->whereNotNull("{$pageKey}_title")
    //             ->orWhereNotNull("{$pageKey}_short_description")
    //             ->orWhereNotNull("{$pageKey}_description")
    //             ->orWhereNotNull("{$pageKey}_image")
    //             ->orWhereNotNull("{$pageKey}_meta_keywords")
    //             ->first();

    //         if ($pageHeaderExist) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => ucfirst(str_replace('_', ' ', $pageKey)) . ' already exists. Please update the existing page instead.',
    //             ], 409);
    //         }

    //         $pageHeader = PageHeader::firstOrNew([]);

    //         $pageHeader->{"{$pageKey}_title"} = $request->title;
    //         $pageHeader->{"{$pageKey}_short_description"} = $request->short_description;
    //         $pageHeader->{"{$pageKey}_description"} = $request->description;

    //         if ($request->hasFile("image")) {
    //             $image_url = $this->fileUpload->imageUploader($request->file('image'), 'page_headers', 200, 200);
    //             $pageHeader->{"{$pageKey}_image"} = $image_url;
    //         }

    //         $pageHeader->{"{$pageKey}_meta_keywords"} = $request->meta_keywords;

    //         $pageHeader->save();

    //         DB::commit();

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => ucfirst(str_replace('_', ' ', $pageKey)) . ' successfully saved.',
    //             'page' => $pageHeader,
    //         ]);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('PageHeader store failed: ' . $e->getMessage(), [
    //             'page_key' => $request->page_key,
    //             'trace' => $e->getTraceAsString(),
    //         ]);
    //         $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['Unknown error occurred']);
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $systemError,
    //         ], 500);
    //     }
    // }

    public function edit($id)
    {
        try {
            $pageHeader = PageHeader::query()
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'pageHeader' => $pageHeader,
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

    public function update(Request $request)
    {
        $rules = [
            'page_key' => 'required|in:home_page,how_it_works_client,how_it_works_provider,client_sign_up,provider_sign_up,client_pricing,contact,about,mission_vision,faq,career,teams',
            'title' => 'nullable|string|max:255',
            'short_description' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'image' => 'nullable|mimes:png,jpg,jpeg|max:5120',
            'meta_keywords' => 'nullable|string|max:500',
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

            $pageKey = $request->page_key;
            $pageHeader = PageHeader::first();

            if (!$pageHeader) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No page header found. Please create a page first.',
                ], 404);
            }

            // Check if the page exists (at least one column is not null)
            $pageExists = PageHeader::query()
                ->whereNotNull("{$pageKey}_title")
                ->orWhereNotNull("{$pageKey}_short_description")
                ->orWhereNotNull("{$pageKey}_description")
                ->orWhereNotNull("{$pageKey}_image")
                ->orWhereNotNull("{$pageKey}_meta_keywords")
                ->exists();

            if (!$pageExists) {
                return response()->json([
                    'status' => 'error',
                    'message' => ucfirst(str_replace('_', ' ', $pageKey)) . ' does not exist. Please create it first.',
                ], 404);
            }

            // Update only provided fields
            if ($request->has('title')) {
                $pageHeader->{"{$pageKey}_title"} = $request->title;
            }
            if ($request->has('short_description')) {
                $pageHeader->{"{$pageKey}_short_description"} = $request->short_description;
            }
            if ($request->has('description')) {
                $pageHeader->{"{$pageKey}_description"} = $request->description;
            }
            if ($request->hasFile('image')) {
                // Delete old image if it exists
                if ($pageHeader->{"{$pageKey}_image"}) {
                    $this->fileUpload->fileUnlink($pageHeader->{"{$pageKey}_image"});
                }
                $image_url = $this->fileUpload->imageUploader($request->file('image'), 'page_headers');
                $pageHeader->{"{$pageKey}_image"} = $image_url;
            }
            if ($request->has('meta_keywords')) {
                $pageHeader->{"{$pageKey}_meta_keywords"} = $request->meta_keywords;
            }

            $pageHeader->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => ucfirst(str_replace('_', ' ', $pageKey)) . ' successfully updated.',
                'page' => $pageHeader,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PageHeader update failed: ' . $e->getMessage(), [
                'page_key' => $request->page_key,
                'trace' => $e->getTraceAsString(),
            ]);
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['Unknown error occurred']);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    // public function destroy($id)
    // {
    //     try {
    //         DB::beginTransaction();

    //         $pageHeader = PageHeader::query()->findOrFail($id);

    //         $pageHeader->delete();

    //         DB::commit();

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Page Header successfully deleted.',
    //         ]);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Delete failed: ' . $e->getMessage());
    //         $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $systemError,
    //         ], 500);
    //     }
    // }
}
