<?php

namespace App\Http\Controllers\Admin\frontend;

use Illuminate\Http\Request;
use App\Utils\ServerErrorMask;
use App\Models\SocialMediaLink;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class SocialMediaController extends Controller
{
    public function index()
    {
        $link = SocialMediaLink::all();

        return response()->json([
            'status' => 'success',
            'data' => $link
        ]);
    }

    public function store(Request $request)
    {
        $rules = [
            // 'platform' => 'required|in:facebook,twitter,linkedin,youtube',
            'platform' => 'required',
            'platform_icon' => 'required',
            'url' => 'required|url'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
            ], 422);
        }

        $link = new SocialMediaLink();
        $link->platform = $request->platform;
        $link->platform_icon = $request->platform_icon;
        $link->url = $request->url;
        $link->save();

        return response()->json([
            'status' => 'success',
            'message' => ucfirst($request->platform) . ' link saved successfully.',
            'data' => $link
        ]);
    }

    public function show(string $id)
    {
        $link = SocialMediaLink::findOrFail($id);
        return response()->json([
            'status' => 'success',
            'data' => $link
        ]);
    }

    public function update(Request $request, $id)
    {
        $rules = [
            'platform' => 'required',
            'platform_icon' => 'required',
            'url' => 'required|url'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
            ], 422);
        }

        $link = SocialMediaLink::findOrFail($id);
        $link->platform = $request->platform;
        $link->platform_icon = $request->platform_icon;
        $link->url = $request->url;
        $link->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Social media link updated successfully.',
            'data' => $link
        ]);
    }

    public function destroy(string $id)
    {

        try {
            DB::beginTransaction();

            $link = SocialMediaLink::find($id);

            if (!$link) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Social media link not found.'
                ]);
            }

            $link->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Social media link deleted successfully.'
            ]);
        } catch (\Throwable $e) {
            DB::rollback();

            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);

            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }
}
