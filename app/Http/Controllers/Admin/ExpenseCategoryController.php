<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Utils\ServerErrorMask;
use App\Models\ExpenseCategory;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\ExpenseCategoryResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        try {

            $categories = ExpenseCategory::all();

            return response()->json([
                'status' => 'success',
                'data' => ExpenseCategoryResource::collection($categories),
            ]);
        } catch (\Throwable $error) {
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
            'name' => 'required|string|max:255',
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

        try {
            DB::beginTransaction();

            $category = ExpenseCategory::create([
                'name' => $request->name,
                'status' => $request->status
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Expense category created successfully',
                'data' => new ExpenseCategoryResource($category),
            ], 201);
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
            $category = ExpenseCategory::find($id);

            if (!$category) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Expense category not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => new ExpenseCategoryResource($category)
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

    public function update(Request $request, $id)
    {
        $rules = [
            'name' => 'required|string|max:255',
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

        try {
            DB::beginTransaction();
            // Find the expense category
            $category = ExpenseCategory::find($id);

            if (!$category) {
                $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::NOT_FOUND, ['Expense category not found']);
                return response()->json([
                    'errors' => $formattedErrors,
                    'payload' => null,
                ], 404);
            }

            $category->update([
                'name' => $request->name,
                'status' => $request->status,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Expense category updated successfully',
                'data' => new ExpenseCategoryResource($category)
            ], 200);
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
            $category = ExpenseCategory::find($id);

            if (!$category) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Expense category not found'
                ], 404);
            }

            $category->delete();
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Expense category deleted successfully'
            ]);
        } catch (\Throwable $error) {
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
