<?php

namespace App\Http\Controllers\Common;

use Carbon\Carbon;
use App\Models\Plan;
use App\Models\User;
use GuzzleHttp\Client;
use App\Models\Payment;
use App\Models\Profile;
use App\Models\WorkOrder;
use App\Models\BankAccount;
use App\Models\Transaction;
use App\Models\Subscription;
use Illuminate\Http\Request;
use App\Models\WebhookResponse;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Services\UniqueIdentifierService;
use Illuminate\Support\Facades\Validator;
use App\Mail\SubscriptionCompleteByClient;
use Stevebauman\Location\Facades\Location;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PaymentController extends Controller
{
    public function subscription_payment(Request $request)
    {
        $rules = [
            'plan_id' => 'required|integer',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }

        $plan = Plan::find($request->plan_id);
        $profile = Profile::where('user_id', Auth::user()->id)->first();

        $subscription = Subscription::firstOrNew(['uuid' => Auth::user()->uuid]);
        $subscription->plan_id = $request->plan_id;
        $subscription->status = $subscription->exists ? $subscription->status : 'Inactive';
        $subscription->save();

        $transaction = new Transaction();
        $transaction->client_id = Auth::user()->uuid;
        $transaction->transaction_type = "Subscription";
        $transaction->transaction_unique_id = UniqueIdentifierService::generateUniqueIdentifier(new Transaction(), 'transaction_unique_id', 'uuid');
        $transaction->gateway = 'PayPal';
        $transaction->save();

        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();

        $response = $provider->createOrder([
            "intent" => "CAPTURE",
            "application_context" => [
                "return_url" => route('paypal.success'),
                "cancel_url" => route('paypal.cancel'),
            ],
            "purchase_units" => [
                [
                    "amount" => [
                        "currency_code" => "USD",
                        "value" => floatval($plan->amount),
                    ],
                    "reference_id" => $transaction->transaction_unique_id,
                ]
            ]
        ]);

        if (isset($response['id']) && $response['id'] != null) {
            foreach ($response['links'] as $link) {
                if ($link['rel'] === "approve") {
                    $responseArray['paypal']['payment_link'] = $link['href'];
                    $responseArray['paypal']['total_amount'] = $plan->amount;
                    break;
                }
            }
        }

        $responseArray['client'] = $profile->first_name . ' ' . $profile->last_name;
        $responseArray['email'] = Auth::user()->email;
        $responseArray['amount'] = $plan->amount;
        $responseArray['currency'] = "USD";
        $responseArray['service_name'] = $plan->name;

        return response()->json([
            'data' => $responseArray,
        ]);
    }

    //paypal
    public function paypal_success(Request $request)
    {

        request()->ip() == '127.0.0.1' ? $locationData = Location::get('8.8.4.4') : $locationData = Location::get(request()->ip());
        $browserMetadata = [
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
        ];

        // 0SC398406H847125L

        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();
        $response = $provider->capturePaymentOrder($request->token);

        $payment_id = $response['purchase_units'][0]['reference_id'];

        if (isset($response['status']) && $response['status'] == "COMPLETED") {
            $transaction = Transaction::where('transaction_unique_id', $payment_id)->first();

            // Subscription
            if ($transaction->transaction_type == "Subscription") {
                $user = User::where('uuid', $transaction->client_id)->first();
                $subscription = Subscription::where('uuid', $user->uuid)->first();
                $plan = Plan::find($subscription->plan_id);

                $subscription->status = 'Under Review';
                $subscription->amount = $plan->amount;
                $subscription->point = $plan->point;
                $subscription->work_order_fee = $plan->work_order_fee;
                $subscription->start_date_time = Carbon::now();
                $subscription->end_date_time = Carbon::now()->addMonths($plan->expired_month);
                $subscription->save();

                $existingTransaction = Transaction::where('client_id', $payment_id)->where('status', 'Completed')->orderBy('created_at', 'desc')->first();

                if ($existingTransaction) {
                    $newBalance = $existingTransaction->balance + $plan->amount;
                    $newPointBalance = $existingTransaction->point_balance + $plan->point;
                } else {
                    $newBalance = $plan->amount;
                    $newPointBalance = $plan->point;
                }

                // $transaction = new Transaction();
                $transaction->credit = $plan->amount;
                $transaction->balance = $newBalance;
                $transaction->point_credit = $plan->point;
                $transaction->point_balance = $newPointBalance;
                $transaction->status = "Under Review";
                $transaction->meta_data = json_encode($browserMetadata);
                $transaction->save();
            }

            if ($transaction->transaction_type == "Payment") {
                $transaction->status = "Under Review";
                $transaction->meta_data = json_encode($browserMetadata);
                $transaction->save();

                $work_order = WorkOrder::where('work_order_unique_id', $transaction->work_order_unique_id)->first();

                $bank = BankAccount::where('uuid', $work_order->assigned_uuid)->first();

                $percantage = $transaction->debit * 14 / 100;
                $newLabor = $transaction->debit - $percantage;

                $payment = new Payment();
                $payment->client_id = $work_order->uuid;
                $payment->provider_id = $work_order->assigned_uuid;
                $payment->account_id = $work_order->bank_account_id;
                $payment->payment_unique_id = UniqueIdentifierService::generateUniqueIdentifier(new Payment(), 'payment_unique_id', 'uuid');
                $payment->work_order_unique_id = $work_order->work_order_unique_id;
                $payment->total_labor = $newLabor;
                $payment->services_fee = $percantage;
                $payment->transaction_table_id = $transaction->id;
                $payment->status = 'Pending';
                $payment->description = "Work Order Payment to Provider";
                $payment->save();
            }

            return redirect(env('FRONTEND_URL') . "/payment/success/$payment_id");
        } else {
            return redirect(env('FRONTEND_URL') . "/payment/failed/$payment_id");
        }
    }

    public function paypal_cancel($payment_id)
    {
        return redirect(env('FRONTEND_URL') . "/payment/failed/$payment_id");
    }

    public function handleWebhook(Request $request)
    {

        try {
            $webhookData = json_decode($request->getContent(), true);
            Log::info('Start:', $webhookData ? [$webhookData] : []);

            $event_type = $webhookData['event_type'];

            switch ($event_type) {

                case "PAYMENT.CAPTURE.COMPLETED":
                    $this->onCaptureCompleted($webhookData);
                    break;

                case "CHECKOUT.ORDER.APPROVED":
                    $this->onCheckoutApproved($webhookData);
                    break;
                default:
                    // Handle unknown event types or log them
                    Log::warning('Unhandled event type: ' . $event_type);
                    break;
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            // return response()->json(['error' => $e->getMessage()], 500);
            Log::error('Payment Request Failed: ' . $e->getMessage());

            // Return an error response
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['Payment Request Failed!']);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
                'payload' => null,
            ], 500);
        }
    }

    private function onCaptureCompleted($webhookData)
    {
        // Handle PAYMENT.CAPTURE.COMPLETED event
        Log::info('PAYMENT Capture Completed:', $webhookData);

        $payment_capture = [
            'paypal_payment_capture' => [
                'event_type' => $webhookData['event_type'],
                'order_id' => $webhookData['resource']['supplementary_data']['related_ids']['order_id'],
                'resource_create_time' => $webhookData['resource']['create_time'] ?? null,
                'seller_receivable_breakdown' => $webhookData['resource']['seller_receivable_breakdown'] ?? null,
                'status' => $webhookData['resource']['status'],
            ]
        ];

        //live
        // $clientId = env('PAYPAL_LIVE_CLIENT_ID');
        // $clientSecret = env('PAYPAL_LIVE_CLIENT_SECRET');

        //sandbox
        $clientId = env('PAYPAL_SANDBOX_CLIENT_ID');
        $clientSecret = env('PAYPAL_SANDBOX_CLIENT_SECRET');

        // Set the endpoint for obtaining access token
        // $tokenEndpoint = 'https://api-m.paypal.com/v1/oauth2/token';
        $tokenEndpoint = 'https://api-m.sandbox.paypal.com/v1/oauth2/token';

        // Set the data to be sent for obtaining the access token
        $data = [
            'grant_type' => 'client_credentials'
        ];

        // Prepare the authorization header
        $authorization = base64_encode($clientId . ':' . $clientSecret);

        // Create a new GuzzleHttp client instance
        $client = new Client();

        // Send a POST request to the token endpoint to obtain the access token
        $response = $client->request('POST', $tokenEndpoint, [
            'headers' => [
                'Authorization' => 'Basic ' . $authorization,
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'form_params' => $data
        ]);

        // Get the response body
        $token_body = $response->getBody();

        // Decode the JSON response
        $tokenData = json_decode($token_body, true);

        // Extract the access token
        $accessToken = $tokenData['access_token'];


        $checkoutOrderUrl = $webhookData['resource']['links'][2]['href'];

        // $accessToken = env('PAYPAL_ACCESS_TOKEN');

        $client = new Client([
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ],
        ]);
        $response = $client->get($checkoutOrderUrl);

        $body = $response->getBody();

        $orderData = json_decode($body, true);

        $payment_id = $orderData['purchase_units'][0]['reference_id'];

        if (isset($orderData['status']) && $orderData['status'] == "COMPLETED") {

            $transaction = Transaction::where('transaction_unique_id', $payment_id)->first();

            $payment = Payment::where('transaction_table_id', $transaction->id)->first();

            if ($transaction->transaction_type == "Subscription") {

                $subscription = Subscription::where('uuid', $transaction->client_id)->first();
                $subscription->update(
                    [
                        'status' => 'Active',
                    ]
                );

                $tag = 'Subscription';
                $transaction->status = 'Completed';
                if ($transaction->save()) {
                    Mail::to($transaction->client->email)->send(new SubscriptionCompleteByClient($transaction, 'Completed', $tag));
                }
            }

            if ($transaction->transaction_type == "Payment") {

                $tag = 'Payment';
                $transaction->status = 'Completed';
                if ($transaction->save()) {

                    $payment->status = 'Completed';
                    $payment->description = 'Payment Completed By Client';
                    $payment->payment_date_time = now()->addDays(14);
                    $payment->save();

                    $work_order = WorkOrder::where('work_order_unique_id', $transaction->work_order_unique_id)->first();
                    $work_order->status = 'Done';
                    $work_order->save();

                    Mail::to($transaction->client->email)->send(new SubscriptionCompleteByClient($transaction, 'Completed', $tag));
                }
            }
        }
        try {

            $webhookResponse = new WebhookResponse();
            $webhookResponse->event_type = $webhookData['event_type'];
            $webhookResponse->pay_id = $payment_id;
            $webhookResponse->event_data = json_encode($webhookData);
            $webhookResponse->save();

            // Additional logic for handling the event...
        } catch (\Exception $e) {
            // Log the error or handle it as needed
            Log::error('Webhook Error: ' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['Webhook handling failed!']);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
                'payload' => null,
            ], 500);
        }


        return response()->json(['success' => 'Webhook Handled'], 200);
    }

    private function onCheckoutApproved($webhookData)
    {
        Log::info('CHECKOUT ORDER APPROVED', $webhookData);

        $checkout = [
            'paypal_checkout_order' => [
                'event_type' => $webhookData['event_type'],
                'resource_id' => $webhookData['resource']['id'],
                'resource_create_time' => $webhookData['resource']['create_time'] ?? null,
                'purchase_units' => $webhookData['resource']['purchase_units'],
                'status' => $webhookData['resource']['status'],
            ]
        ];
        try {
            $webhookResponse = new WebhookResponse();
            $webhookResponse->event_type = $webhookData['event_type'];
            $webhookResponse->pay_id = $webhookData['resource']['purchase_units'][0]['reference_id'];
            $webhookResponse->event_data = json_encode($webhookData);
            $webhookResponse->save();

            // Additional logic for handling the event...
        } catch (\Exception $e) {
            // Log the error or handle it as needed
            Log::error('Webhook Error: ' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, ['Webhook handling failed!']);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
                'payload' => null,
            ], 500);
        }


        return response()->json(['success' => 'Webhook Handled'], 200);
    }

    public function clientPointBalance()
    {
        $transaction = Transaction::where('client_id', Auth::user()->uuid)->where('status', 'Completed')->orderBy('created_at', 'desc')->first();
        return response()->json(['status' => 'success', 'point' => $transaction->point_balance], 200);
    }

    // public function clientWorkOrderPayment(Request $request)
    // {
    //     $rules = [
    //         'work_order_unique_id' => 'required|exists:work_orders,work_order_unique_id',
    //     ];

    //     $validator = Validator::make($request->all(), $rules);

    //     if ($validator->fails()) {
    //         $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
    //         return response()->json([
    //             'errors' => $formattedErrors,
    //             'payload' => null,
    //         ], 422);
    //     }

    //     $work_order = WorkOrder::where('work_order_unique_id', $request->work_order_unique_id)->first();

    //     if (!$work_order) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Work order not found',
    //         ], 404);
    //     }

    //     if ($work_order->status == "Done") {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Work Order payment already done',
    //         ], 404);
    //     }

    //     $payment = Payment::where('work_order_unique_id', $request->work_order_unique_id)->where('provider_id', $work_order->assigned_id)->first();

    //     $profile = Profile::where('user_id', Auth::user()->id)->first();

    //     $service_fee = $payment->services_fee;

    //     $data = json_decode($service_fee, true); // Decode JSON to an associative array

    //     $totalPercentage = array_reduce($data, function ($carry, $item) {
    //         return $carry + (float) $item['percentage'];
    //     }, 0);

    //     if ($payment->pay_change_fee > 0) {
    //         $pay_amount = $payment->pay_change_fee + $payment->expense_fee ?? 0;
    //     } else {
    //         $pay_amount = $payment->total_labor + $payment->expense_fee ?? 0;
    //     }

    //     $existingTransaction = Transaction::where('client_id', Auth::user()->uuid)->where('status', 'Completed')->orderBy('created_at', 'desc')->first();

    //     if ($existingTransaction->point_balance > 0) {
    //         $newPointBalance = $existingTransaction->point_balance - ($pay_amount + $totalPercentage);
    //     }
    //     if ($existingTransaction->point_balance < $newPointBalance) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Insufficient balance',
    //         ], 404);
    //     }

    //     $transaction = new Transaction();
    //     $transaction->transaction_unique_id = UniqueIdentifierService::generateUniqueIdentifier(new Transaction(), 'transaction_unique_id', 'uuid');
    //     $transaction->client_id = Auth::user()->uuid;
    //     $transaction->provider_id = $work_order->assigned_uuid;
    //     $transaction->transaction_type = "Payment";
    //     $transaction->work_order_unique_id = $request->work_order_unique_id;
    //     $transaction->service_fee = $totalPercentage;
    //     $transaction->debit = $pay_amount;
    //     $transaction->point_debit = $pay_amount + $totalPercentage;
    //     $transaction->point_balance = $newPointBalance;
    //     $transaction->status = "Pending";
    //     $transaction->description = "Work Order Payment to Provider";
    //     $transaction->gateway = 'PayPal';
    //     $transaction->save();

    //     $provider = new PayPalClient;
    //     $provider->setApiCredentials(config('paypal'));
    //     $provider->getAccessToken();

    //     $response = $provider->createOrder([
    //         "intent" => "CAPTURE",
    //         "application_context" => [
    //             "return_url" => route('paypal.success'),
    //             "cancel_url" => route('paypal.cancel'),
    //         ],
    //         "purchase_units" => [
    //             [
    //                 "amount" => [
    //                     "currency_code" => "USD",
    //                     "value" => floatval($pay_amount + $totalPercentage),
    //                 ],
    //                 "reference_id" => $transaction->transaction_unique_id,
    //             ]
    //         ]
    //     ]);

    //     if (isset($response['id']) && $response['id'] != null) {
    //         foreach ($response['links'] as $link) {
    //             if ($link['rel'] === "approve") {
    //                 $responseArray['paypal']['payment_link'] = $link['href'];
    //                 $responseArray['paypal']['total_amount'] = $pay_amount + $totalPercentage;
    //                 break;
    //             }
    //         }
    //     }

    //     $responseArray['client'] = $profile->first_name . ' ' . $profile->last_name;
    //     $responseArray['email'] = Auth::user()->email;
    //     $responseArray['amount'] = $pay_amount + $totalPercentage;
    //     $responseArray['currency'] = "USD";
    //     $responseArray['service_name'] = $work_order->work_order_title;

    //     return response()->json([
    //         'data' => $responseArray,
    //     ]);
    // }

    public function providerWithdraw(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Payment successful',
        ]);
    }
}
