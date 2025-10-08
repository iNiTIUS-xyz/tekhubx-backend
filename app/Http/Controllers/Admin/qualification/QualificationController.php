<?php

namespace App\Http\Controllers\Admin\qualification;

use Illuminate\Http\Request;
use App\Utils\ServerErrorMask;
use App\Models\QualificationType;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\QualificationTypeResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class QualificationController extends Controller
{

    public function index()
    {
        try {

            $qualification_type = QualificationType::all();

            return response()->json([
                'status' => 'success',
                'qualification_type' => QualificationTypeResource::collection($qualification_type),
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
            'name' => 'required|string|max:200',
            'note' => 'nullable|string',
            'status' => 'nullable|in:Active,Inactive',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }

        $check = QualificationType::query()
            ->where('name', trim($request->name))
            ->first();

        if ($check) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['name' => 'Qualification Type already exists']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        try {
            DB::beginTransaction();
            $qualification_type = new QualificationType();
            $qualification_type->name = trim($request->name);
            $qualification_type->note = trim($request->note);
            $qualification_type->status = $request->status;
            $qualification_type->save();
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Qualification Type Successfully Created',
                'qualification_type' => new QualificationTypeResource($qualification_type),
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

    public function edit(string $id)
    {
        try {

            $qualification_type = QualificationType::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'qualification_type' => new QualificationTypeResource($qualification_type),
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

    public function update(Request $request, string $id)
    {
        $rules = [
            'name' => 'required|string|max:200',
            'note' => 'nullable|string',
            'status' => 'nullable|in:Active,Inactive',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
        $check = QualificationType::query()
            ->where('name', trim($request->name))
            ->where('id', '!=', $id)
            ->first();

        if ($check) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, ['name' => 'Qualification Type already exists']);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }
        try {
            DB::beginTransaction();
            $qualification_type = QualificationType::query()
                ->findOrFail($id);

            $qualification_type->name = trim($request->name);
            $qualification_type->note = trim($request->note);
            $qualification_type->status = $request->status;
            $qualification_type->save();
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Qualification Type Successfully Updated',
                'qualification_type' => new QualificationTypeResource($qualification_type),
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

    public function destroy(string $id)
    {
        try {
            DB::beginTransaction();
            $qualificationType = QualificationType::query()
                ->findOrFail($id);

            $qualificationType->delete();

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Qualification Type Successfully Deleted',
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
}
