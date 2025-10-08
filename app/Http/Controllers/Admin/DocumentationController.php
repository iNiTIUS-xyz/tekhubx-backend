<?php

namespace App\Http\Controllers\Admin;

use App\Classes\FileUploadClass;
use App\Helpers\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Documentation;
use App\Utils\ServerErrorMask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DocumentationController extends Controller
{

    protected $fileUpload;

    public function __construct(FileUploadClass $fileUpload)
    {
        $this->fileUpload = $fileUpload;

        // $this->middleware('permission:documentation,documentation.list')->only(['index']);
        // $this->middleware('permission:documentation.create_store')->only(['store']);
        // $this->middleware('permission:documentation.edit')->only(['edit']);
        // $this->middleware('permission:documentation.update')->only(['update']);
        // $this->middleware('permission:documentation.delete')->only(['destroy']);
    }

    public function index()
    {
        try {
            $documentations = Documentation::query()
                ->get();

            return response()->json([
                'status' => 'success',
                'documentations' => $documentations,
            ]);
        } catch (\Exception $error) {
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $rules = [
            'parent_id' => 'nullable|exists:documentations,id',
            'title' => 'required|string|max:255',
            'icon' => 'required',
            'description' => 'required',
            'image' => 'required|mimes:png,jpg,jpeg|max:5120',
            'status' => 'required|in:Active,Inactive',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }

        $existData = Documentation::query()
            ->where('title', $request->title)
            ->get();

        if ($existData->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Already exist documentation with this title',
            ]);
        }

        try {
            DB::beginTransaction();

            $documentations = new Documentation;
            $documentations->parent_id = $request->parent_id;
            $documentations->title = $request->title;
            $documentations->slug = strtolower(str_replace(' ', '-', $request->title));
            $documentations->icon = $request->icon;
            $documentations->description = $request->description;

            if ($request->hasFile("image")) {
                $image_url = $this->fileUpload->imageUploader($request->file('image'), 'documentation', 400, 400);
                $documentations->image = $image_url;
            }

            $documentations->status = $request->status;

            $documentations->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Documentation successfully created',
                'documentation' => $documentations,
            ]);
        } catch (\Exception $error) {
            DB::rollBack();
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }

    public function edit($id)
    {
        try {

            $documentations =  Documentation::query()
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'documentation' => $documentations,
            ]);
        } catch (\Exception $error) {
            DB::rollBack();
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }


    public function update(Request $request, $id)
    {
        $rules = [
            'parent_id' => 'nullable|exists:documentations,id',
            'title' => 'required|string|max:255',
            'icon' => 'required',
            'description' => 'required',
            'image' => 'nullable|mimes:png,jpg,jpeg|max:5120',
            'status' => 'required|in:Active,Inactive',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }

        $existData = Documentation::query()
            ->where('title', $request->title)
            ->where('id', '!=', $id)
            ->get();

        if ($existData->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Already exist documentation with this title',
            ]);
        }

        try {
            DB::beginTransaction();

            $documentations = Documentation::query()
                ->findOrFail($id);

            $documentations->parent_id = $request->parent_id;
            $documentations->title = $request->title;
            $documentations->slug = strtolower(str_replace(' ', '-', $request->title));
            $documentations->icon = $request->icon;
            $documentations->description = $request->description;

            if ($request->hasFile("image")) {
                $this->fileUpload->fileUnlink($documentations->image);
                $image_url = $this->fileUpload->imageUploader($request->file('image'), 'documentation', 400, 400);
                $documentations->image = $image_url;
            }

            $documentations->status = $request->status;

            $documentations->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Documentation successfully updated',
                'documentation' => $documentations,
            ]);
        } catch (\Exception $error) {
            DB::rollBack();
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $documentations =  Documentation::query()
                ->findOrFail($id);

            $this->fileUpload->fileUnlink($documentations->image);

            $documentations->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Documentation successfully deleted',
            ]);
        } catch (\Exception $error) {
            DB::rollBack();
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }

    public function parent()
    {
        try {
            $documentations =  Documentation::query()
                ->where('parent_id', null)
                ->get();

            return response()->json([
                'status' => 'success',
                'documentations' => $documentations,
            ]);
        } catch (\Exception $error) {
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }
}
