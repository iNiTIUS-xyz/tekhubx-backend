<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Legal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Utils\ServerErrorMask;
use App\Helpers\ApiResponseHelper;

class LegalController extends Controller
{

    public function __construct()
    {
        // $this->middleware('permission:legal,legal.list')->only(['index']);
        $this->middleware('permission:legal.create_store')->only(['store']);
        $this->middleware('permission:legal.edit')->only(['edit']);
        $this->middleware('permission:legal.update')->only(['update']);
        $this->middleware('permission:legal.delete')->only(['destroy']);
    }

    public function index()
    {
        try {

            $legal = Legal::all();

            return response()->json([
                'status' => 'success',
                'legal' => $legal,
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
            'title' => 'required|string|max:255',
            'description' => 'required|string',
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
            // Add authenticated user's ID to validated data
            $validatedData = $validator->validated();
            $legal = Legal::create($validatedData);

            return response()->json([
                'status' => 'success',
                'message' => 'New data inserted',
                'legal' => $legal,
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

    public function edit(string $id)
    {
        try {

            $legal = Legal::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'legal' => $legal,
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
            'title' => 'required|string|max:255',
            'description' => 'required|string',
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


            $validatedData = $validator->validated();
            $legal = Legal::findOrFail($id);
            $legal->update($validatedData);

            return response()->json([
                'status' => 'success',
                'message' => 'data updated successfully',
                'legal' => $legal,
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

    public function destroy(string $id)
    {
        try {

            Legal::findOrFail($id)->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Data deleted successfully',
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
