<?php

namespace App\Http\Controllers\Client;

use App\Models\Payment;
use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Utils\ServerErrorMask;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\UniqueIdentifierService;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{

    public function index()
    {
        $subscriptions = Subscription::with('plan')->where('uuid', Auth::user()->uuid)->where('status', 'Active')->orderBy('created_at', 'desc')->first();
        return response()->json([
            'status' => 'success',
            'subscription' => $subscriptions,
        ], 200);
    }

    public function getSubscriptionDataForClient()
    {
        $transactions = Payment::with('client', 'client.profile')->where('client_id', Auth::user()->uuid)->where('transaction_type', 'Subscription')->get();
        return response()->json([
            'status' => 'success',
            'transactions' => $transactions,
        ]);
    }
    public function sendRequestForPoint(Request $request)
    {
        $rules = [
            'subscription_id' => 'required|integer|exists:subscriptions,id',
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
            $subscription = Subscription::find($request->subscription_id);

            $existingTransaction = Payment::where('client_id', Auth::user()->uuid)
                ->where('status', 'Completed')
                ->orderBy('created_at', 'desc')
                ->first();
            $transaction = new Payment();
            $transaction->payment_unique_id = UniqueIdentifierService::generateUniqueIdentifier(new Payment(), 'payment_unique_id', 'uuid');
            $transaction->client_id = Auth::user()->uuid;
            $transaction->transaction_type = 'Point';
            $transaction->point_credit = $subscription->point;
            $transaction->point_balance = ($existingTransaction->point_balance ?? 0) + $subscription->point;
            $transaction->description = 'Monthly subscription points request';
            $transaction->status = 'Under Review';
            $transaction->save();

            return response()->json([
                'status' => 'success',
                'transaction' => $transaction,
            ], 200);
        } catch (\Exception $error) {
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'status' => 'error',
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }

    public function cancelSubscription(Request $request)
    {
        try {
            $subscription = Subscription::where('uuid', Auth::user()->uuid)->first();
            $subscription->status = 'Cancelled';
            $subscription->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Subscription cancelled successfully',
                'subscription' => $subscription,
            ], 200);
        } catch (\Exception $error) {
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'status' => 'error',
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }
}
