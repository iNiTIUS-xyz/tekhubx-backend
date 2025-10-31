<?php

namespace App\Http\Controllers\Admin\Support;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ApiResponseHelper;
use App\Http\Resources\Admin\SupportResource;
use App\Models\Support;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Utils\ServerErrorMask;
use Illuminate\Support\Facades\DB;

class SupportController extends Controller
{
    public function index()
    {
        try {
            $supports = Support::query()->get();

            return response()->json([
                'status' => 'success',
                'supports' => SupportResource::collection($supports),
            ]);
        } catch (\Exception $e) {
            Log::error('Support query not found' . $e->getMessage());
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
            'title' => 'required',
            'description' => 'required',
            'support_for' => 'nullable|in:Provider,Client',
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
            $supports = new Support();
            $supports->title = $request->title;
            $supports->slug = strtolower(str_replace(' ', '-', $request->title));
            $supports->description = $request->description;
            $supports->support_for = $request->support_for;
            $supports->status = $request->status;
            $supports->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Support Successfully Save',
                'supports' => new SupportResource($supports),
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
            $supports = Support::query()
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'supports' => new SupportResource($supports),
            ]);
        } catch (\Exception $e) {
            Log::error('Support query not found' . $e->getMessage());
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
            'title' => 'nullable',
            'description' => 'nullable',
            'support_for' => 'nullable|in:Provider,Client',
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
            $supports = Support::query()
                ->findOrFail($id);
            $supports->title = $request->title ?? $supports->title;
            $supports->slug = strtolower(str_replace(' ', '-', $request->title ?? $supports->title));
            $supports->description = $request->description ?? $supports->description;
            $supports->support_for = $request->support_for ?? $supports->support_for;
            $supports->status = $request->status ?? $supports->status;
            $supports->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Support Successfully Update',
                'supports' => new SupportResource($supports->refresh()),
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

            $supports = Support::query()
                ->findOrFail($id);

            $supports->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Support Successfully Delete',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Support query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }
}
