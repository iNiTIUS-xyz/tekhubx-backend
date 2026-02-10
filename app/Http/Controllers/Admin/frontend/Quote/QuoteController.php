<?php

namespace App\Http\Controllers\Admin\frontend\Quote;

use App\Classes\FileUploadClass;
use App\Helpers\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\QuoteResource;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Utils\ServerErrorMask;

class QuoteController extends Controller
{
    protected $fileUpload;

    public function __construct(FileUploadClass $fileUpload)
    {
        $this->fileUpload = $fileUpload;
    }

    public function index()
    {
        try {
            $quotes = Quote::query()->get();

            return response()->json([
                'status' => 'success',
                'quotes' => QuoteResource::collection($quotes),
            ]);
        } catch (\Exception $e) {
            Log::error('Quote query not found' . $e->getMessage());
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
            'quote' => 'required',
            'image' => 'required|mimes:png,jpg,jpeg|max:10240',
            'quote_author_name' => 'required',
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
            $quotes = new Quote();
            $quotes->quote = $request->quote;
            if ($request->hasFile("image")) {
                $image_url = $this->fileUpload->imageUploader($request->file('image'), 'quote');
                $quotes->image = $image_url;
            }
            $quotes->quote_author_name = $request->quote_author_name;
            $quotes->status = $request->status;
            $quotes->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Quote Successfully Save',
                'quote' => new QuoteResource($quotes),
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
            $quotes = Quote::query()
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'quotes' => new QuoteResource($quotes),
            ]);
        } catch (\Exception $e) {
            Log::error('Quote query not found' . $e->getMessage());
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
            'quote' => 'required',
            'image' => 'nullable|mimes:png,jpg,jpeg|max:10240',
            'quote_author_name' => 'required',
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
            $quotes = Quote::query()
                ->findOrFail($id);
            $quotes->quote = $request->quote;
            if ($request->hasFile("image")) {
                $this->fileUpload->fileUnlink($quotes->image);
                $image_url = $this->fileUpload->imageUploader($request->file('image'), 'quote');
                $quotes->image = $image_url;
            }
            $quotes->quote_author_name = $request->quote_author_name;
            $quotes->status = $request->status;
            $quotes->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Quote Successfully Update',
                'quote' => new QuoteResource($quotes->refresh()),
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

            $quotes = Quote::query()
                ->findOrFail($id);

            $this->fileUpload->fileUnlink($quotes->image);

            $quotes->delete();

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Quote Successfully Delete',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Quote query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }
}
