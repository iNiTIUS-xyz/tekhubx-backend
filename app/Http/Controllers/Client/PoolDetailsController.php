<?php

namespace App\Http\Controllers\Client;

use App\Models\User;
use App\Models\PoolDetails;
use Illuminate\Http\Request;
use App\Utils\ServerErrorMask;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\PoolDetailsResource;

class PoolDetailsController extends Controller
{
    public function __construct()
    {
        // $this->middleware('permission:pool_details,pool_details.list')->only(['index']);
        $this->middleware('permission:pool_details.create_store')->only(['store']);
        $this->middleware('permission:pool_details.edit')->only(['edit']);
        $this->middleware('permission:pool_details.update')->only(['update']);
        $this->middleware('permission:pool_details.delete')->only(['destroy']);
    }

    public function index()
    {
        try {

            $pool_details = PoolDetails::with('talentData', 'provider')->where('uuid', Auth::user()->uuid)->get();

            return response()->json([
                'status' => 'success',
                'pool_details' => $pool_details,
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
            'talent_id' => 'required|integer',
            'provider_id' => 'required|array|min:1',
            'status' => 'required|in:Active,Hidden',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'status' => 'error',
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422); // 422 Unprocessable Entity
        }

        try {
            $existingEntries = PoolDetails::where('talent_id', $request->talent_id)
                ->whereIn('provider_id', $request->provider_id)
                ->where('uuid', Auth::user()->uuid)
                ->pluck('provider_id')
                ->toArray();

            $newEntries = array_diff($request->provider_id, $existingEntries);

            if (empty($newEntries)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Duplicate entries detected.',
                    'payload' => null,
                ], 409); // 409 Conflict
            }

            $poolDetailsData = [];
            foreach ($newEntries as $provider_id) {
                $poolDetailsData[] = [
                    'uuid' => Auth::user()->uuid,
                    'talent_id' => $request->talent_id,
                    'provider_id' => $provider_id,
                    'status' => $request->status,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Insert only new records
            PoolDetails::insert($poolDetailsData);

            return response()->json([
                'status' => 'success',
                'message' => 'New data inserted',
                'pool_details' => $poolDetailsData,
            ], 201);
        } catch (\Exception $error) {
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'status' => 'error',
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }

    public function edit(string $id)
    {
        try {

            $pool_details = PoolDetails::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'pool_details' => $pool_details,
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
            'talent_id' => 'required|integer|exists:talent,id',
            'provider_id' => 'required|integer|exists:users,id',
            'blocked' => 'required|in:yes,no',
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
            $provider_user = User::findOrFail($validatedData['provider_id']);

            if ($provider_user->organization === 'provider') {

                $pool_details = PoolDetails::findOrFail($id);
                $pool_details->update($validatedData);

                return response()->json([
                    'status' => 'success',
                    'message' => 'New data inserted',
                    'pool_details' => $pool_details,
                ], 201); // 201 Created is appropriate for a successful creation
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User is not a provider',
                ], 403); // 403 Forbidden is more appropriate for this kind of error
            }
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

            PoolDetails::findOrFail($id)->delete();

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

    public function talentNameWiseProvider(Request $request, $id)
    {

        $pool = PoolDetails::with('talentData', 'provider')->where('uuid', Auth::user()->uuid)->where('talent_id', $id)->get();

        if (!$pool) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pool not found',
            ]);
        }
        return response()->json([
            'status' => 'success',
            'pool' => PoolDetailsResource::collection($pool),
        ]);
    }
}
