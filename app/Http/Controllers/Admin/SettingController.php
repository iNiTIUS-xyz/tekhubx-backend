<?php

namespace App\Http\Controllers\Admin;

use App\Models\Setting;
use Illuminate\Http\Request;
use App\Utils\ServerErrorMask;
use App\Classes\FileUploadClass;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Admin\SettingResource;

class SettingController extends Controller
{

    protected $fileUpload;

    public function __construct(FileUploadClass $fileUpload)
    {
        $this->fileUpload = $fileUpload;
    }

    public function index()
    {
        try {
            $settings = Setting::query()
                ->firstOrCreate();

            return response()->json([
                'status' => 'success',
                'settings' => new SettingResource($settings),
            ]);
        } catch (\Exception $e) {
            Log::error('Page query not found' . $e->getMessage());
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
            $settings = Setting::query()
                ->findOrFail($id);

            if (!$settings) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Setting not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'settings' => new SettingResource($settings),
            ]);
        } catch (\Exception $e) {
            Log::error('Page query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function update(Request $request)
    {
        $rules = [
            'website_name' => 'required',
            'website_logo' => 'nullable|mimes:png,jpg,jpeg|max:10240',
            'website_favicon' => 'nullable|mimes:png,jpg,jpeg|max:10240',
            'phone_numbers' => 'nullable|array|min:1',
            'email_addresses' => 'nullable|array|min:1',
            'address' => 'nullable|string',
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
            $setting = Setting::query()
                ->firstOrCreate();

            $setting->website_name = $request->website_name;
            if ($request->hasFile("website_logo")) {
                $this->fileUpload->fileUnlink($setting->website_logo);
                $image_url = $this->fileUpload->imageUploader($request->file('website_logo'), 'settings');
                $setting->website_logo = $image_url;
            }

            if ($request->hasFile("website_favicon")) {
                $this->fileUpload->fileUnlink($setting->website_favicon);
                $image_url = $this->fileUpload->imageUploader($request->file('website_favicon'), 'settings');
                $setting->website_favicon = $image_url;
            }

            $setting->phone_numbers = json_encode($request->phone_numbers);
            $setting->email_addresses = json_encode($request->email_addresses);
            $setting->address = $request->address;
            $setting->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Setting Successfully Update',
                'settings' => new SettingResource($setting->refresh()),
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
}
