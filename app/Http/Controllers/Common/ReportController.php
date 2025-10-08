<?php

namespace App\Http\Controllers\Common;

use App\Models\Payment;
use App\Models\WorkOrder;
use App\Utils\ServerErrorMask;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{

    public function providerWithdrawData()
    {
        try {
            $provider_can_withdraw = Payment::where('provider_id', Auth::user()->uuid)
                ->where('status', 'Deposited')->get();
            $provider_hold = Payment::where('provider_id', Auth::user()->uuid)
                ->where('status', 'Hold')->get();
            $provider_under_review = Payment::where('provider_id', Auth::user()->uuid)
                ->where('status', 'Under Review')->get();
            $provider_settlement = Payment::where('provider_id', Auth::user()->uuid)
                ->where('status', 'Settlement')->get();
            $provider_withdraw = Payment::where('provider_id', Auth::user()->uuid)
                ->where('status', 'Withdraw')->get();
            $total_payment = Payment::where('provider_id', Auth::user()->uuid)->where('status', 'Deposited')
                ->orderBy('created_at', 'desc')
                ->first();
            // Return the response
            return response()->json([
                'status' => 'success',
                'provider_deposited' => $provider_can_withdraw ?? [],
                'total_payment_ready_for_withdraw' => $total_payment->balance ?? 0,
                'provider_hold' => $provider_hold ?? [],
                'provider_under_review' => $provider_under_review ?? [],
                'provider_settlement' => $provider_settlement ?? [],
                'provider_withdraw' => $provider_withdraw ?? [],
            ]);
        } catch (\Throwable $e) {
            // Log the error and return a system error response
            Log::error('Chat message not showing not sent: ' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }
    public function providerWorkReport()
    {
        try {
            // Fetch all completed work orders for the authenticated provider
            $completedWorkOrders = WorkOrder::where('assigned_id', Auth::user()->id)
                ->where('provider_status', 'Completed')
                ->pluck('work_order_unique_id')
                ->toArray();

            // Count total completed work orders
            $total_work_order = count($completedWorkOrders);

            // Calculate total payment for completed work orders
            $total_payment = Payment::whereIn('work_order_unique_id', $completedWorkOrders)
                ->where('status', 'Payment Completed By Client')
                ->sum('total_labor');

            // Get a list of all completed work orders
            $all_completed_work_order_list = WorkOrder::where('assigned_id', Auth::user()->id)
                ->where('provider_status', 'Completed')
                ->get();

            // Return the response
            return response()->json([
                'status' => 'success',
                'total_work_order' => $total_work_order ?? 0,
                'total_payment' => $total_payment ?? 0,
                'all_completed_work_order_list' => $all_completed_work_order_list
            ]);
        } catch (\Throwable $e) {
            // Log the error and return a system error response
            Log::error('Chat message not showing not sent: ' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function providerPaymentDetails()
    {
        try {
            $providerUuid = Auth::user()->uuid;

            $payments = Payment::with([
                'workOrder',
            ])
                ->where('provider_id', $providerUuid)
                ->orderBy('payment_date_time', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'payments' => $payments,
            ]);
        } catch (\Throwable $e) {
            Log::error('Provider payment fetch error: ' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);

            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }


    //client
    public function fundDetails()
    {
        try {

            $payments = Payment::select('transaction_type', 'status', 'work_order_unique_id', 'total_labor', 'services_fee', 'tax', 'expense_fee', 'pay_change_fee', 'debit', 'credit', 'balance')
            ->where('client_id', Auth::user()->uuid)->where('status', 'Completed')->orderBy('created_at', 'desc')->get();

            return response()->json([
                'status' => 'success',
                'payments' => $payments,
            ]);
        } catch (\Throwable $e) {
            Log::error('Provider payment fetch error: ' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);

            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }
}
