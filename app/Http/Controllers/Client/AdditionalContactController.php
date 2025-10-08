<?php

namespace App\Http\Controllers\Client;

use Illuminate\Http\Request;
use App\Utils\ServerErrorMask;
use App\Models\WorkOrderManage;
use App\Models\AdditionalContact;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdditionalContactController extends Controller
{

    public function __construct()
    {
        // $this->middleware('permission:additional_contact,additional_contact.list')->only(['index']);
        $this->middleware('permission:additional_contact.create_store')->only(['store']);
        $this->middleware('permission:additional_contact.edit')->only(['edit']);
        $this->middleware('permission:additional_contact.update')->only(['update']);
        $this->middleware('permission:additional_contact.delete')->only(['destroy']);
    }

    public function index()
    {
        try {

            $additional_contact = AdditionalContact::all();

            return response()->json([
                'status' => 'success',
                'additional_contact' => $additional_contact,
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
            'template_id' => 'required|integer|exists:templates,id',
            'name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'phone_1' => 'required|string|max:20',
            'ext' => 'nullable|string|max:10',
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
            // Add authenticated user's ID to validated data
            $validatedData = $validator->validated();
            $validatedData['user_id'] = Auth::user()->id;

            $additional_contact = AdditionalContact::create($validatedData);

            return response()->json([
                'status' => 'success',
                'message' => 'New data inserted',
                'additional_contact' => $additional_contact,
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

            $additional_contact = AdditionalContact::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'additional_contact' => $additional_contact,
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

            'template_id' => 'required|integer|exists:templates,id',
            'name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'phone_1' => 'required|string|max:20',
            'ext' => 'nullable|string|max:10',
            'email' => 'nullable|email|max:255',
            'note' => 'nullable|string',

        ];

        $validatedData['user_id'] = Auth::user()->id;

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

            $additional_contact = AdditionalContact::findOrFail($id);
            $additional_contact->update($validatedData);


            return response()->json([
                'status' => 'success',
                'message' => 'data updated successfully',
                'addtional_contact' => $additional_contact,
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

            AdditionalContact::findOrFail($id)->delete();

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
