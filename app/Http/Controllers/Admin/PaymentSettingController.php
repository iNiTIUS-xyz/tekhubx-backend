<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\PaymentSetting;
use App\Utils\ServerErrorMask;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class PaymentSettingController extends Controller
{
    public function index()
    {
        try {

            $settings = PaymentSetting::all();
            return response()->json([
                'status' => 'success',
                'data' => $settings
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


    // GET /admin/payment-settings/{id}
    public function show($id)
    {
        try {

            $setting = PaymentSetting::find($id);

            if (!$setting) {
                return response()->json(['error' => 'Setting not found'], 404);
            }


            return response()->json([
                'status' => 'success',
                'data' => $setting
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

    // POST /admin/payment-settings
    public function store(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'stripe_secret' => 'required|string',
                'stripe_public' => 'required|string',
                'stripe_webhook_secret' => 'nullable|string',
                'stripe_account_id' => 'nullable|string',
                'stripe_mode' => 'required|in:test,live',
            ]);

            if ($validator->fails()) {
                $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
                return response()->json([
                    'errors' => $formattedErrors,
                    'payload' => null,
                ], 500);
            }

            $setting = PaymentSetting::create(array_merge(
                ['gateway_name' => 'stripe'],
                $validator->validated()
            ));

            return response()->json([
                'status' => 'success',
                'message' => 'Payment setting created successfully',
                'data' => $setting
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

    // PUT /admin/payment-settings/{id}
    public function update(Request $request, $id)
    {

        try {

            $setting = PaymentSetting::find($id);

            if (!$setting) {
                return response()->json(['error' => 'Setting not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'stripe_secret' => 'nullable|string',
                'stripe_public' => 'nullable|string',
                'stripe_webhook_secret' => 'nullable|string',
                'stripe_account_id' => 'nullable|string',
                'stripe_mode' => 'nullable|in:test,live',
            ]);

            if ($validator->fails()) {
                $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
                return response()->json([
                    'errors' => $formattedErrors,
                    'payload' => null,
                ], 500);
            }

            $setting->update($validator->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Payment setting updated successfully',
                'data' => $setting
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
