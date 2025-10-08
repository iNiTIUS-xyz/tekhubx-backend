<?php

namespace App\Http\Controllers\Admin\Country;

use App\Http\Controllers\Controller;
use App\Models\State;
use Illuminate\Http\Request;
use App\Utils\ServerErrorMask;
use App\Helpers\ApiResponseHelper;
use App\Http\Resources\Admin\StateResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class StateController extends Controller
{
    public function __construct()
    {
        // $this->middleware('permission:state,state.list')->only(['index']);
        $this->middleware('permission:state.create_store')->only(['store']);
        $this->middleware('permission:state.edit')->only(['edit']);
        $this->middleware('permission:state.update')->only(['update']);
        $this->middleware('permission:state.delete')->only(['destroy']);
    }

    public function index()
    {
        try {

            $state = State::query()
                ->with(['country'])
                ->get();

            return response()->json([
                'status' => 'success',
                'state' => StateResource::collection($state),
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
            'country_id' => 'required|exists:countries,id',
            'name' => 'required|string',
            'tax' => 'nullable',
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
            $check = State::where('name', $request->name)->first();
            if ($check) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'State name already exists',
                ]);
            }
            $state = new State();
            $state->country_id = $request->country_id;
            $state->name = $request->name;
            $state->tax = $request->tax;
            $state->status = $request->status;
            $state->short_name = $request->short_name;
            $state->save();

            return response()->json([
                'status' => 'success',
                'message' => 'New data inserted',
                'state' => new StateResource($state),
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

            $state = State::query()
                ->with(['country'])
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'state' => new StateResource($state),
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
            'country_id' => 'nullable|exists:countries,id',
            'name' => 'nullable|string',
            'tax' => 'nullable',
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

            $state =  State::query()
                ->with(['country'])
                ->findOrFail($id);
            $state->country_id = $request->country_id ?? $state->country_id;
            $state->name = $request->name ?? $state->name;
            $state->tax = $request->tax ?? $state->tax;
            $state->status = $request->status ?? $state->status;
            $state->short_name = $request->short_name ?? $state->short_name;
            $state->save();

            return response()->json([
                'status' => 'success',
                'message' => 'data updated successfully',
                'state' => new StateResource($state),
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

            State::findOrFail($id)->delete();

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
