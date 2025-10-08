<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AccountManageController extends Controller
{
    public function changeEmail(Request $request)
    {
        $rules = [
            'email' => 'required|email',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        try {

            $guard = Auth::getDefaultDriver();

            $user = Auth::user();

            if($user->email === $request->email) {

                return response()->json([
                    'success' => false,
                    'message' => 'You can not entered the same email address. Please enter a different email address.'
                ]);

            }

            $infoUser = User::findOrFail($user->id);
            $infoUser->email = $request->email;
            $infoUser->save();

            Auth::guard($guard)->logout();

            return response()->json([
                'success' => true,
                'message' => 'Your email has been updated successfully. You have been logged out for security reasons.'
            ]);

        } catch (\Exception $e) {
            Log::error('Change email has error ' . $e->getMessage());

            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['User Login failed!']);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
                'payload' => null,
            ], 500);
        }

    }

    public function accountDeletion(Request $request)
    {
        $rules = [
            'password' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        try {

            $guard = Auth::getDefaultDriver();

            $user = Auth::user();

            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The password you entered is incorrect. Please try again.'
                ]);
            }

            $userInfo = User::query()
                    ->with([
                        'profiles',
                        'companies'
                    ])
                    ->findOrFail($user->id);

            // profile delete
            if($userInfo->profiles) {
                $userInfo->profiles->delete();
            }
            // companies delete
            if($userInfo->companies) {
                $userInfo->companies->delete();
            }

            // Perform account deletion
            $userInfo->delete();

            // Log the user out
            Auth::guard($guard)->logout();

            return response()->json([
                'success' => true,
                'message' => 'Your account has been deleted successfully. You have been logged out.'
            ]);

        } catch (\Exception $e) {
            Log::error('Account deletion has error ' . $e->getMessage());

            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['User Login failed!']);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
                'payload' => null,
            ], 500);
        }
    }
}
