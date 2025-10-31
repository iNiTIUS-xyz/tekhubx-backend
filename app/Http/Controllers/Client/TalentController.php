<?php

namespace App\Http\Controllers\Client;

use App\Models\Talent;
use Illuminate\Http\Request;
use App\Utils\ServerErrorMask;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\TalentResource;
use App\Models\PoolDetails;
use Illuminate\Support\Facades\Validator;

class TalentController extends Controller
{

    public function index()
    {
        try {

            $talent = Talent::where('uuid', Auth::user()->uuid)
                ->orWhere('client_id', Auth::user()->id)
                ->with(['poolDetails.provider', 'poolDetails.profile'])
                ->get();

            return response()->json([
                'status' => 'success',
                'talent' => TalentResource::collection($talent),
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
            'pool_name' => 'required|string|max:255',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422); // Use 422 for validation errors
        }

        try {
            // Check for duplicate entry
            $existingTalent = Talent::where('pool_name', trim($request->pool_name))
                ->where('uuid', Auth::user()->uuid) // Check for the authenticated user's scope
                ->first();

            if ($existingTalent) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Duplicate entries detected.',
                    'payload' => null,
                ], 409); // Use 409 for conflict
            }

            // Add authenticated user's ID to validated data
            $talent = new Talent();
            $talent->uuid = Auth::user()->uuid;
            $talent->client_id = Auth::user()->id;
            $talent->pool_name = trim($request->pool_name);
            $talent->save();

            return response()->json([
                'status' => 'success',
                'message' => 'New data inserted',
                'talent' => $talent,
            ], 201); // Use 201 for resource creation

        } catch (\Exception $error) {
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500); // Use 500 for server errors
        }
    }

    public function edit(string $id)
    {
        try {

            $talent = Talent::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'talent' => $talent,
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
            'pool_name' => 'required|string|max:255',
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

            $talent = Talent::findOrFail($id);

            $exists = Talent::where(function ($query) use ($request) {
                $query->where('client_id', Auth::user()->id)
                    ->orWhere('uuid', Auth::user()->uuid);
            })
                ->where('pool_name', $request->pool_name)
                ->where('id', '!=', $id) // Exclude the current record
                ->exists();

            if ($exists) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pool name already exists',
                ]);
            }
            $talent->update([
                'pool_name' => $request->pool_name
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'data updated successfully',
                'talent' => $talent,
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

            Talent::findOrFail($id)->delete();
            PoolDetails::query()->where('talent_id', $id)->delete();
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
