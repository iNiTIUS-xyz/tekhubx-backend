<?php

namespace App\Http\Controllers\Admin\frontend\FAQ;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Classes\FileUploadClass;
use App\Helpers\ApiResponseHelper;
use App\Models\FAQ;
use App\Utils\ServerErrorMask;
use Illuminate\Support\Facades\DB;

class FAQController extends Controller
{
    protected $fileUpload;

    public function __construct(FileUploadClass $fileUpload)
    {
        $this->fileUpload = $fileUpload;

        // $this->middleware('permission:faqs,faqs.list')->only(['index']);
        $this->middleware('permission:faqs.create_store')->only(['store']);
        $this->middleware('permission:faqs.edit')->only(['edit']);
        $this->middleware('permission:faqs.update')->only(['update']);
        $this->middleware('permission:faqs.delete')->only(['destroy']);
    }
    public function index()
    {
        try {
            $faqs = FAQ::query()
                ->get();

            return response()->json([
                'status' => 'success',
                'faqs' => $faqs,
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
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'category' => 'required|in:Provider,Client,General',
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

            $faq = new FAQ();
            $faq->question = $request->question;
            $faq->answer = $request->answer;
            $faq->category = $request->category;
            $faq->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'FAQ successfully created.',
                'faq' => $faq,
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
            $faq = FAQ::query()
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'step' => $faq,
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
            'question' => 'nullable|string|max:255',
            'answer' => 'nullable|string',
            'category' => 'required|in:Provider,Client,General',
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

            $faq = FAQ::find($id);

            $faq->question = $request->question;
            $faq->answer = $request->answer;
            $faq->category = $request->category;
            $faq->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'FAQ successfully updated.',
                'faq' => $faq,
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

            $faq = FAQ::query()->findOrFail($id);

            $faq->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'FAQ successfully deleted.',
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
