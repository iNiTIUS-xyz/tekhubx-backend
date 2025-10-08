<?php

namespace App\Http\Controllers\Client\defaultClient;

use App\Http\Resources\Client\ClientResource;
use App\Models\Client;
use Illuminate\Http\Request;
use App\Utils\ServerErrorMask;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Classes\FileUploadClass;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    protected $fileUpload;

    public function __construct(FileUploadClass $fileUpload)
    {
        $this->fileUpload = $fileUpload;
    }


    public function index()
    {
        try {

            $client = Client::query()
                ->where("user_id", Auth::user()->id)
                ->orWhere('uuid', Auth::user()->uuid)
                ->get();

            return response()->json([
                'status' => 'success',
                'client' => ClientResource::collection($client),
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
            'client_title' => 'required|string|max:255',
            'client_manager_id' => 'nullable|integer|exists:users,id',
            'website' => 'nullable|string|max:255|url',
            'notes' => 'nullable|string|max:1000',
            'default_policies' => 'nullable|string|max:1000',
            'default_standard_instruction' => 'nullable|string|max:1000',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state_id' => 'nullable|integer|exists:states,id',
            'zip_code' => 'nullable|integer',
            'country_id' => 'nullable|integer|exists:countries,id',
            'location_type' => 'nullable|string|max:255',
            'company_name_with_logo' => 'nullable',
            'client_name_with_logo' => 'nullable',
            'logo' => 'nullable|mimes:jpeg,png,jpg,gif,avif,svg,webp|max:2048',
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

            $client = new Client();
            $client->uuid = Auth::user()->uuid;
            $client->user_id = Auth::user()->id;
            $client->client_title = $request->client_title;
            $client->client_manager_id = $request->client_manager_id;
            $client->website = $request->website;
            $client->notes = $request->notes;
            $client->default_policies = $request->default_policies;
            $client->default_standard_instruction = $request->default_standard_instruction;
            $client->address_line_1 = $request->address_line_1;
            $client->address_line_2 = $request->address_line_2;
            $client->city = $request->city;
            $client->state_id = $request->state_id;
            $client->zip_code = $request->zip_code;
            $client->country_id = $request->country_id;
            $client->location_type = $request->location_type;
            $client->company_name_with_logo = $request->company_name_with_logo ? true : false;
            $client->client_name_with_logo = $request->client_name_with_logo ? true : false;

            if ($request->hasFile("logo")) {
                $image_url = $this->fileUpload->imageUploader($request->file('logo'), 'client_logo', 800, 600);
                $client->logo = $image_url;
            }

            $client->save();


            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Client Successfully Created',
                'client' => new ClientResource($client),
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
            $client = Client::query()
                ->findOrFail($id);

            if (!$client) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Client not found',
                ], 404);
            }
            return response()->json([
                'status' => 'success',
                'client' => new ClientResource($client),
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
            'client_title' => 'nullable|string|max:255',
            'client_manager_id' => 'nullable|integer|exists:users,id',
            'website' => 'nullable|string|max:255|url',
            'notes' => 'nullable|string|max:1000',
            'default_policies' => 'nullable|string|max:1000',
            'default_standard_instruction' => 'nullable|string|max:1000',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state_id' => 'nullable|integer|exists:states,id',
            'zip_code' => 'nullable|integer',
            'country_id' => 'nullable|integer|exists:countries,id',
            'location_type' => 'nullable|string|max:255',
            'company_name_with_logo' => 'nullable',
            'client_name_with_logo' => 'nullable',
            'logo' => 'nullable|mimes:jpeg,png,jpg,gif,avif,svg,webp|max:2048',
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

            $client = Client::query()
                ->findOrFail($id);

            $client->client_title = $request->client_title ?? $client->client_title;
            $client->client_manager_id = $request->client_manager_id ?? $client->client_manager_id;
            $client->website = $request->website ?? $client->website;
            $client->notes = $request->notes ?? $client->notes;
            $client->default_policies = $request->default_policies ?? $client->default_policies;
            $client->default_standard_instruction = $request->default_standard_instruction ?? $client->default_standard_instruction;
            $client->address_line_1 = $request->address_line_1 ?? $client->address_line_1;
            $client->address_line_2 = $request->address_line_2 ?? $client->address_line_2;
            $client->city = $request->city ?? $client->city;
            $client->state_id = $request->state_id ?? $client->state_id;
            $client->zip_code = $request->zip_code ?? $client->zip_code;
            $client->country_id = $request->country_id ?? $client->country_id;
            $client->location_type = $request->location_type ?? $client->location_type;

            if ($request->hasFile("logo")) {
                $image_url = $this->fileUpload->imageUploader($request->file('logo'), 'client_logo', 800, 600, $client->logo);
                $client->logo = $image_url;
            }

            $client->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Client Successfully Updated',
                'client' => new ClientResource($client),
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

            $client = Client::query()
                ->where('user_id', Auth::user()->id)
                ->where('uuid', Auth::user()->uuid)
                ->findOrFail($id);

            $this->fileUpload->fileUnlink($client->image);

            $client->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Client deleted successfully',
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
