<?php

namespace App\Http\Controllers\Client\location;

use App\Models\State;
use App\Models\Country;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use App\Utils\ServerErrorMask;
use App\Imports\LocationImport;
use App\Services\CommonService;
use App\Helpers\ApiResponseHelper;
use App\Models\AdditionalLocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\LocationResource;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{
    protected $CommonService;
    public function __construct(CommonService $CommonService)
    {
        $this->CommonService = $CommonService;
    }

    public function index()
    {

        try {

            $location = AdditionalLocation::query()
                ->where('uuid', Auth::user()->uuid)
                ->get();

            if ($location->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Location not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'location' => LocationResource::collection($location),
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
            'name' => 'required|string|max:255',
            'display_name' => 'required|string|max:255',
            'default_client_id' => 'nullable',
            'location_group_id' => 'nullable',
            'country_id' => 'required|exists:countries,id',
            'location_type' => 'required|string|max:255',
            'address_1' => 'required|string|max:255',
            'address_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state_id' => 'required',
            'zip_code' => 'required|string|max:20',
            'name_description' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'phone_ext' => 'nullable|string|max:10',
            'email' => 'nullable|email|max:255',
            'note' => 'nullable|string',
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

            $country_name = Country::where('id', $request->country_id)->first();
            $state_name = State::where('id', $request->state_id)->first();

            // Construct the full address
            $full_address = "{$request->address_1}, {$request->city}, {$state_name->name}, {$request->zip_code}, {$country_name->name}";

            // Get latitude and longitude using the geocoding function
            $location = $this->CommonService->geocodeAddressOSM($full_address);

            if ($location) {
                $latitude = $location['latitude'];
                $longitude = $location['longitude'];
            } else {
                $latitude = null;
                $longitude = null;
            }

            $location = new AdditionalLocation();
            $location->uuid = Auth::user()->uuid;
            $location->name = $request->name;
            $location->display_name = $request->display_name;
            $location->default_client_id = $request->default_client_id;
            $location->location_group_id = $request->location_group_id;
            $location->country_id = $request->country_id;
            $location->location_type = $request->location_type;
            $location->address_line_1 = $request->address_1;
            $location->address_line_2 = $request->address_2;
            $location->city = $request->city;
            $location->state_id = $request->state_id;
            $location->zip_code = $request->zip_code;
            $location->name_description = $request->name_description;
            $location->phone = $request->phone;
            $location->phone_ext = $request->phone_ext;
            $location->email = $request->email;
            $location->note = $request->note;
            $location->latitude = $latitude;
            $location->longitude = $longitude;
            $location->save();

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Location Successfully Created',
                'location' => new LocationResource($location),
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

            $location = AdditionalLocation::query()
                ->where('uuid', Auth::user()->uuid)
                ->findOrFail($id);

            if (!$location) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Location not found',
                ], 404);
            }
            return response()->json([
                'status' => 'success',
                'location' => new LocationResource($location),
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
            'name' => 'nullable|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'default_client_id' => 'nullable',
            'location_group_id' => 'nullable',
            'country_id' => 'nullable|exists:countries,id',
            'location_type' => 'nullable|string|max:255',
            'address_1' => 'nullable|string|max:255',
            'address_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state_id' => 'nullable',
            'zip_code' => 'nullable|string|max:20',
            'name_description' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'phone_ext' => 'nullable|string|max:10',
            'email' => 'nullable|email|max:255',
            'note' => 'nullable|string',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        $location = AdditionalLocation::find($id);

        if (!$location) {
            return response()->json([
                'status' => 'error',
                'message' => 'Location not found',
            ], 404);
        }

        $country_name = Country::where('id', $request->country_id ?? $location->country_id)->first();
        $state_name = State::where('id', $request->state_id ?? $location->state_id)->first();

        // Construct the full address
        $address_1 = $request->address_1 ?? $location->address_line_1;
        $city = $request->city ?? $location->city;
        $zip_code = $request->zip_code ?? $location->zip_code;
        $full_address = "{$address_1}, {$city}, {$state_name->name}, {$zip_code}, {$country_name->name}";

        // Get latitude and longitude using the geocoding function
        $geoLocation = $this->CommonService->geocodeAddressOSM($full_address);

        if ($geoLocation) {
            $latitude = $geoLocation['latitude'];
            $longitude = $geoLocation['longitude'];
        } else {
            $latitude = null;
            $longitude = null;
        }

        try {
            DB::beginTransaction();

            // Update only the fields present in the request
            $location->name = $request->name ?? $location->name;
            $location->display_name = $request->display_name ?? $location->display_name;
            $location->default_client_id = $request->default_client_id ?? $location->default_client_id;
            $location->location_group_id = $request->location_group_id ?? $location->location_group_id;
            $location->country_id = $request->country_id ?? $location->country_id;
            $location->location_type = $request->location_type ?? $location->location_type;
            $location->address_line_1 = $request->address_1 ?? $location->address_line_1;
            $location->address_line_2 = $request->address_2 ?? $location->address_line_2;
            $location->city = $request->city ?? $location->city;
            $location->state_id = $request->state_id ?? $location->state_id;
            $location->zip_code = $request->zip_code ?? $location->zip_code;
            $location->name_description = $request->name_description ?? $location->name_description;
            $location->phone = $request->phone ?? $location->phone;
            $location->phone_ext = $request->phone_ext ?? $location->phone_ext;
            $location->email = $request->email ?? $location->email;
            $location->note = $request->note ?? $location->note;
            $location->latitude = $latitude;
            $location->longitude = $longitude;

            $location->save();

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Location Successfully Updated',
                'location' => new LocationResource($location),
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


    public function locationImport(Request $request)
    {

        $rules = [
            'location_excel_file' => 'required|file|mimes:xlsx,csv',  // Correct way to specify file types
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
            $file = $request->file('location_excel_file');
            Excel::import(new LocationImport($this->CommonService), $file->getRealPath());
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Location successfully imported',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Default client excel import has problem' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function download(Request $request)
    {

        try {

            $filePath = Storage::path('public/template/template.csv');

            if (!file_exists($filePath)) {
                return response()->json(['error' => 'File not found'], 404);
            }

            return response()->json([
                'status' => 'success',
                'filePath' => $filePath,
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

    public function destroy($id)
    {
        try {
            $location = AdditionalLocation::findOrFail($id);

            if (!$location) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Location not found',
                ], 404);
            }

            $work_orderExists = WorkOrder::query()
                ->where('uuid', Auth::user()->uuid)
                ->where('default_client_id', $location->default_client_id)
                ->exists();
            if ($work_orderExists) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Location is in use by work orders and cannot be deleted',
                ], 400);
            }

            $location->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Location successfully deleted',
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
