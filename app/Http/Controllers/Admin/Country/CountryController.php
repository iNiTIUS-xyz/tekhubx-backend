<?php

namespace App\Http\Controllers\Admin\Country;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;
use App\Utils\ServerErrorMask;
use App\Helpers\ApiResponseHelper;
use App\Http\Resources\Admin\CountryResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CountryController extends Controller
{
    public function __construct()
    {
        // $this->middleware('permission:country,country.list')->only(['index']);
        $this->middleware('permission:country.create_store')->only(['store']);
        $this->middleware('permission:country.edit')->only(['edit']);
        $this->middleware('permission:country.update')->only(['update']);
        $this->middleware('permission:country.delete')->only(['destroy']);
    }

    public function index()
    {
        try {

            $country = Country::with('states')->get();

            return response()->json([
                'status' => 'success',
                'country' => CountryResource::collection($country),
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
            'name' => 'required|string',
            'status' => 'nullable|in:Active,Inactive',
            'short_name' => 'required|string',
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
            $check = Country::where('name', $request->name)->first();
            if ($check) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Short name already exists',
                ]);
            }
            $country = new Country();
            $country->name = $request->name;
            $country->status = $request->status;
            $country->short_name = $request->short_name;
            $country->save();

            return response()->json([
                'status' => 'success',
                'message' => 'New data inserted',
                'country' => $country,
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
            $country = Country::findOrFail($id);
            return response()->json([
                'status' => 'success',
                'country' => $country,
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
            'name' => 'nullable|string',
            'status' => 'nullable|in:Active,Inactive',
            'short_name' => 'nullable|string',
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

            $country =  Country::query()
                ->findOrFail($id);
            $country->name = $request->name ?? $country->name;
            $country->status = $request->status ?? $country->status;
            $country->short_name = $request->short_name ?? $country->short_name;
            $country->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Data updated successfully',
                'country' => $country,
            ]);
        } catch (\Exception $error) {

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

            Country::findOrFail($id)->delete();

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
