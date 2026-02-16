<?php

namespace App\Http\Controllers\Admin\category;

use App\Models\WorkCategory;
use Illuminate\Http\Request;
use App\Utils\ServerErrorMask;
use App\Models\WorkSubCategory;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Client\WorkCategoryResource;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function ViewWorkCategory()
    {

        try {

            $workCategories = WorkCategory::with('workSubCategoryData')->get();

            return response()->json([
                'status' => 'success',
                'work_categories' => WorkCategoryResource::collection($workCategories),
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

    public function WorkCategory(Request $request)
    {

        $rules = [
            'name' => 'required|min:3|max:30',
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
            $exitsWorkCategory = WorkCategory::query()
                ->where('name', $request->name)
                ->get();

            if ($exitsWorkCategory->count() > 0) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Already Work Category has with this name',
                ]);
            }

            $workCategory = new WorkCategory();
            $workCategory->name = $request->name;
            $workCategory->status = $request->status;
            $workCategory->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'New category inserted',
                'work_category' => $workCategory,
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

    public function EditWorkCategory($id)
    {

        try {

            $workCategories = WorkCategory::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'workCategories' => $workCategories,
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

    public function UpdateWorkCategory(Request $request, $id)
    {

        $rules = [
            'name' => 'required|min:3|max:30',
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


            $exitsWorkCategory = WorkCategory::query()
                ->where('name', $request->name)
                ->where('id', '!=', $id)
                ->get();

            if ($exitsWorkCategory->count() > 0) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Already Work Category has with this name',
                ]);
            }

            $workCategory = WorkCategory::findOrFail($id);
            $workCategory->name = $request->name;
            $workCategory->status = $request->status;
            $workCategory->save();
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Data updated',
                'work_category' => $workCategory,
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

    public function WorkSubCategory(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:200',
            'cat_id' => 'required|exists:work_categories,id',
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
            $existWorkSubCat = WorkSubCategory::query()
                ->where('name', $request->name)
                ->get();
            if ($existWorkSubCat->count() > 0) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Already has a Work sub category with this name',
                ]);
            }

            DB::beginTransaction();
            $workSubCategory = new WorkSubCategory();
            $workSubCategory->name = $request->name;
            $workSubCategory->cat_id = $request->cat_id;
            $workSubCategory->status = $request->status;
            $workSubCategory->save();
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'New data inserted',
                'work_sub_category' => $workSubCategory,
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

    public function ViewWorkSubCategory()
    {

        try {

            $workSubCategories = WorkSubCategory::with('category')
                ->get();

            return response()->json([
                'status' => 'success',
                'work_sub_categories' => $workSubCategories,
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

    public function EditWorkSubCategory($id)
    {

        try {

            $workSubCategories = WorkSubCategory::findOrFail($id);

            if (!$workSubCategories) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Data not found.',
                ]);
            }

            return response()->json([
                'status' => 'success',
                'workCategories' => $workSubCategories,
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

    public function UpdateWorkSubCategory(Request $request, $id)
    {
        $rules = [
            'name' => 'required|string|max:200',
            'cat_id' => 'required|exists:work_categories,id',
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

            $existWorkSubCat = WorkSubCategory::query()
                ->where('id', '!=', $id)
                ->where('name', $request->name)
                ->get();

            if ($existWorkSubCat->count() > 0) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Already has a Work sub category with this name',
                ]);
            }

            $workSubCategory = WorkSubCategory::findOrFail($id);

            $workSubCategory->name = $request->name;
            $workSubCategory->cat_id = $request->cat_id;
            $workSubCategory->status = $request->status;
            $workSubCategory->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Data updated',
                'work_sub_category' => $workSubCategory,
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



    public function deleteCategory($id)
    {
        try {
            DB::beginTransaction();

            $category = WorkCategory::findOrFail($id);

            $exist_work_order = WorkOrder::query()
                ->where('work_category_id', $category->id)
                ->get();
            if ($exist_work_order->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'This category is associated with existing work orders and cannot be deleted.'
                ], 400);
            }
            // Delete related services through subcategories
            $category->workSubCategoryData()->each(function ($subCategory) {
                $subCategory->services()->delete();
            });

            // Delete related subcategories
            $category->workSubCategoryData()->delete();

            // Delete the category
            $category->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Category and related data deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }

    public function deleteSubCategory($id)
    {
        try {
            DB::beginTransaction();

            $subCategory = WorkSubCategory::findOrFail($id);

            // Delete related services
            $subCategory->services()->delete();

            // Delete the subcategory
            $subCategory->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Subcategory and related services deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }
}
