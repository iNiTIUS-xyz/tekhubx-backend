<?php

namespace App\Http\Controllers\Common;

use App\Models\User;
use App\Models\BankAccount;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\BankAccountResource;

class BankAccountController extends Controller
{
    public function index()
    {
        try {
            $authUser = Auth::user();
            if (!$authUser) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated',
                ], 401);
            }

            $user = User::where('uuid', $authUser->uuid)->first();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found',
                ], 404);
            }

            $bankAccount = BankAccount::where('uuid', $authUser->uuid)->first();
            $deposit = false;
            if ($user->stripe_payment_method_id == null && !empty($user->setup_intent_id)) {
                $verification = 'Micro-deposits have been sent to your bank account. Please verify the amounts to complete the setup.';
                $deposit = true;
            } elseif ($user->stripe_payment_method_id) {
                $verification = 'Bank account setup completed.';
                $deposit = true;
            } else {
                $verification = 'Please, Setup your bank account.';
            }

            if ($user->stripe_account_id == null) {
                return response()->json([
                    'status' => 'error',
                    'verification' => 'Account not found, Please, Setup your bank account.',
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Account found',
                'stripe_setup' => $user->stripe_account_id,
                'verification' => $verification,
                'deposit' => $deposit,
                'bank_account' => $bankAccount ? new BankAccountResource($bankAccount) : null,
            ]);
        } catch (\Exception $e) {
            Log::error('BankAccount query not found: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred. Please try again later.',
            ], 500);
        }
    }

    // public function store(Request $request)
    // {
    //     $rules = [
    //         'payment_method' => 'required',
    //         'account_number' => 'required',
    //         'account_name' => 'required',
    //         'routing_number' => 'required',
    //         'account_type' => 'required',
    //     ];

    //     $validator = Validator::make($request->all(), $rules);

    //     if ($validator->fails()) {
    //         $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
    //         return response()->json([
    //             'errors' => $formattedErrors,
    //         ], 422);
    //     }

    //     try {
    //         DB::beginTransaction();
    //         $bankAccounts = new BankAccount();
    //         $bankAccounts->uuid = Auth::user()->uuid;
    //         $bankAccounts->account_type = $request->account_type;
    //         $bankAccounts->payment_method = $request->payment_method;
    //         $bankAccounts->account_number = $request->account_number;
    //         $bankAccounts->account_name = $request->account_name;
    //         $bankAccounts->routing_number = $request->routing_number;
    //         $bankAccounts->save();

    //         DB::commit();

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Bank Account Successfully Save',
    //             'bank_account' => new BankAccountResource($bankAccounts),
    //         ]);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Store filed' . $e->getMessage());
    //         $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $systemError,
    //         ], 500);
    //     }
    // }

    // public function edit($id)
    // {
    //     try {
    //         $bankAccounts = BankAccount::find($id);

    //         return response()->json([
    //             'status' => 'success',
    //             'bank_account' => new BankAccountResource($bankAccounts),
    //         ]);
    //     } catch (\Exception $e) {
    //         Log::error('BankAccount query not found' . $e->getMessage());
    //         $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $systemError,
    //         ], 500);
    //     }
    // }

    // public function update(Request $request, $id)
    // {
    //     $rules = [
    //         'payment_method' => 'nullable',
    //         'account_number' => 'nullable',
    //         'account_name' => 'nullable',
    //         'routing_number' => 'nullable',
    //         'account_type' => 'nullable',
    //         'status' => 'nullable|in:Active,Inactive',
    //     ];

    //     $validator = Validator::make($request->all(), $rules);

    //     if ($validator->fails()) {
    //         $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
    //         return response()->json([
    //             'errors' => $formattedErrors,
    //         ], 422);
    //     }

    //     try {
    //         DB::beginTransaction();
    //         $bankAccounts = BankAccount::find($id);

    //         $bankAccounts->account_type = $request->account_type ?? $bankAccounts->account_type;
    //         $bankAccounts->payment_method = $request->payment_method ?? $bankAccounts->payment_method;
    //         $bankAccounts->account_number = $request->account_number ?? $bankAccounts->account_number;
    //         $bankAccounts->account_name = $request->account_name ?? $bankAccounts->account_name;
    //         $bankAccounts->routing_number = $request->routing_number ?? $bankAccounts->routing_number;
    //         $bankAccounts->status = $request->status ?? $bankAccounts->status;
    //         $bankAccounts->save();

    //         DB::commit();

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'BankAccount Successfully Update',
    //             'bank_account' => new BankAccountResource($bankAccounts->refresh()),
    //         ]);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Update filed' . $e->getMessage());
    //         $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $systemError,
    //         ], 500);
    //     }
    // }

    // public function destroy($id)
    // {
    //     try {
    //         DB::beginTransaction();

    //         $bankAccounts = BankAccount::query()
    //             ->findOrFail($id);

    //         $bankAccounts->delete();

    //         DB::commit();

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Bank Account Successfully Delete',
    //         ]);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Bank Account query not found' . $e->getMessage());
    //         $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $systemError,
    //         ], 500);
    //     }
    // }
}
