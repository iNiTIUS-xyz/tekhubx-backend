<?php

namespace App\Http\Controllers\Admin;

use App\Models\Payment;
use Illuminate\Http\Request;
use App\Helpers\ApiResponseHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\LicenseAndCertificate;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ProviderPaymentResource;
use App\Http\Resources\TransactionAdminResource;
use App\Http\Resources\SubscriptionAdminResource;

class TransactionReportController extends Controller
{
    public function getSubscription()
    {
        $transactions = Payment::with('client', 'client.profile')->where('transaction_type', 'Subscription')->get();

        return response()->json([
            'status' => 'success',
            'transactions' => SubscriptionAdminResource::collection($transactions),
        ]);
    }

    public function getPayment()
    {
        $transactions = Payment::with('workOrder', 'provider', 'provider.profile', 'client', 'client.profile')->where('transaction_type', 'Payment')->get();

        return response()->json([
            'status' => 'success',
            'transactions' => TransactionAdminResource::collection($transactions),
        ]);
    }

    public function getProviderPaymentShowInProvider()
    {
        $payments = Payment::with('providerUuid', 'providerUuid.profile', 'workOrder')->where('provider_id', Auth::user()->uuid)->get();

        return response()->json([
            'status' => 'success',
            'payments' => ProviderPaymentResource::collection($payments),
        ]);
    }

    public function getProviderPaymentShowInAdmin()
    {
        $payments = Payment::with('providerUuid', 'providerUuid.profile', 'workOrder')->where('description', "Payment Completed By Client")->get();

        return response()->json([
            'status' => 'success',
            'payments' => ProviderPaymentResource::collection($payments),
        ]);
    }

    public function pointRequestByClient()
    {
        $transactions = Payment::with('client', 'client.profile')->where('transaction_type', 'Point')->where('status', 'Under Review')->get();
        return response()->json([
            'status' => 'success',
            'transactions' => $transactions,
        ]);
    }

    public function pointRequestUpdate(Request $request, $id)
    {
        $rules = [
            'status' => 'required|in:0,1',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
        $transaction = Payment::find($id);
        $transaction->status = $request->status == 0 ? 'Reject' : 'Completed';
        $transaction->save();
        return response()->json([
            'status' => 'success',
            'message' => 'Status Updated Successfully',
            'transaction' => $transaction
        ]);
    }

    public function licenseAndCertificateApproval(Request $request)
    {
        $rules = [
            'license_certificate_id' => 'required',
            'status' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
        $licenseCertificate = LicenseAndCertificate::where('license_number', $request->license_certificate_id)
            ->orWhere('certificate_number', $request->license_certificate_id)->first();
        $licenseCertificate->status = $request->status;
        $licenseCertificate->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Status Updated Successfully',
            'licenseCertificate' => $licenseCertificate
        ]);
    }
}
