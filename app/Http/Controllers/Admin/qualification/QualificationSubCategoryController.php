<?php

namespace App\Http\Controllers\Admin\qualification;

use Illuminate\Http\Request;
use App\Utils\ServerErrorMask;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\QualificationSubCategory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class QualificationSubCategoryController extends Controller
{

    public function index()
    {
        try {
            $qualification_sub_cats = QualificationSubCategory::with('qualification')->get();
            $grouped = $qualification_sub_cats->groupBy(function ($item) {
                return $item->qualification->name;
            });
            $result = $grouped->map(function ($group) {
                return [
                    'id' => $group->first()->qualification->id,
                    'name' => $group->first()->qualification->name,
                    'qualification_type_id' => $group->first()->qualification_type_id,
                    'qualification_sub_cats' => $group->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'name' => $item->name,
                        ];
                    })->values()->all(),
                ];
            })
                ->values()
                ->all();

            return response()->json([
                'status' => 'success',
                'qualifications' => $result,
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
            'qualification_type_id' => 'required|exists:qualification_types,id|integer',
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
            $qualification_sub_category = new QualificationSubCategory();
            $qualification_sub_category->qualification_type_id = $request->qualification_type_id;
            $qualification_sub_category->name = $request->name;
            $qualification_sub_category->status = $request->status ?? 'Active';
            $qualification_sub_category->save();
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Qualification SubCategory Successfully Created',
                'qualification_sub_category' => $qualification_sub_category,
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

            $qualification_sub_category = QualificationSubCategory::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'qualification_sub_category' => $qualification_sub_category,
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
            'qualification_type_id' => 'required|exists:qualification_types,id|integer',
            'name' => 'required|string|max:255',
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
            $qualification_sub_category = QualificationSubCategory::find($id);
            $qualification_sub_category->qualification_type_id = $request->qualification_type_id;
            $qualification_sub_category->name = $request->name;
            $qualification_sub_category->status = $request->status;
            $qualification_sub_category->save();
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Qualification SubCategory Successfully Updated',
                'qualification_sub_category' => $qualification_sub_category,
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
            $qualificationSubCategory = QualificationSubCategory::query()
                ->findOrFail($id);
            $qualificationSubCategory->delete();
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Qualification SubCategory Successfully Deleted',
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
