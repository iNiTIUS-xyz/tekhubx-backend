<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\ClientManager;
use App\Utils\ServerErrorMask;
use App\Models\EmployeeProvider;
use App\Helpers\ApiResponseHelper;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\AuthenticateUserResource;

class DefaultAuthController extends Controller
{
    public function setupPassword(Request $request)
    {
        $rules = [
            'password' => 'required|min:8|confirmed',
            'token' => 'required|string',
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
            DB::beginTransaction();

            // Try finding the token in ClientManager
            $clientManager = ClientManager::where('remember_token', $request->token)->first();

            if ($clientManager) {
                $user = User::find($clientManager->user_id);
                $clientManager->update(['remember_token' => null]); // Clear the token
                $userRole = 'client';
            } else {
                // If not found, try finding the token in EmployeeProvider
                $provider = EmployeeProvider::where('token', $request->token)->first();

                if (!$provider) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid token. Please contact support.',
                    ], 404);
                }

                $user = User::find($provider->provider_id);
                $provider->update(['token' => null]); // Clear the token
                $userRole = 'provider';
            }

            // Update password
            $user->update(['password' => Hash::make($request->password)]);

            DB::commit();

            // Authenticate the user
            $credentials = $user->email
                ? ['email' => $user->email, 'password' => $request->password]
                : ['username' => $user->username, 'password' => $request->password];

            if (!$token = auth()->attempt($credentials)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized credentials',
                    'payload' => null,
                ], 401);
            }

            // Generate JWT refresh token
            $refresh_token = JWTAuth::fromUser($user, ['exp' => 60 * 24 * 30]);

            return response()->json([
                'status' => 'success',
                'message' => 'Password set successfully. You can now log in.',
                'is_user' => $userRole,
                'user' => new AuthenticateUserResource($user),
                'authorization' => [
                    'access_token' => $token,
                    'refresh_token' => $refresh_token,
                    'type' => 'bearer',
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error during password setup: ' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }


    public function setupProviderPassword(Request $request)
    {
        $rules = [
            'password' => 'required|confirmed',
            'token' => 'required',
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

            $provider = EmployeeProvider::query()
                ->where('token', $request->token)
                ->first();

            if (!$provider) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Password has already been set, or the provider was not found. Please verify the token or contact support if the issue persists.',
                ]);
            }

            $user = User::query()
                ->where('id', $provider->provider_id)
                ->first();

            $user->password = Hash::make($request->password);

            $user->save();

            $provider->token = null;

            $provider->save();

            DB::commit();

            $credentials = $user->email ? ['email' => $user->email, 'password' => $request->password] : ['username' => $user->username, 'password' => $request->password];

            if (!$token = auth()->guard('provider')->attempt($credentials)) {
                $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['Unauthorized Credentials']);
                return response()->json([
                    'status' => 'error',
                    'message' => $systemError,
                    'payload' => null,
                ], 401);
            }

            $refresh_token = JWTAuth::fromUser($user, ['exp' => 60 * 24 * 30]);

            return response()->json([
                'status' => 'success',
                'message' => 'Login Successfully',
                'user' => new AuthenticateUserResource($user),
                'authorization' => [
                    'access_token' => $token,
                    'refresh_token' => $refresh_token,
                    'type' => 'bearer',
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Provider query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function verifyEmail($token)
    {
        $record = DB::table('verification_tokens')->where('token', $token)->first();

        if (!$record) {
            return response()->json(['message' => 'Invalid or expired verification link'], 400);
        }

        $user = User::find($record->user_id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->email_verified_at = now();
        $user->save();

        // Delete token so it can't be reused
        DB::table('verification_tokens')->where('token', $token)->delete();

        return redirect(config('app.frontend_url') . '/login');
    }
}
