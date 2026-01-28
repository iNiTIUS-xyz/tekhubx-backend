<?php

namespace App\Http\Controllers\Common;

use Carbon\Carbon;
use Stripe\Stripe;
use Stripe\Account;
use Stripe\Webhook;
use App\Models\Plan;
use App\Models\User;
use Stripe\Customer;
use App\Models\Country;
use App\Models\Payment;
use App\Models\Profile;
use Stripe\AccountLink;
use Stripe\StripeClient;
use App\Models\WorkOrder;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use App\Models\BankAccount;
use App\Models\Subscription;
use Illuminate\Http\Request;
use App\Models\PaymentSetting;
use App\Utils\ServerErrorMask;
use App\Models\WebhookResponse;
use App\Models\PaymentIntentSave;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\UniqueIdentifierService;
use Illuminate\Support\Facades\Validator;
use Stevebauman\Location\Facades\Location;

class StripeController extends Controller
{

    //step 1
    public function stripeConnect(Request $request)
    {
        $rules = [
            'email' => 'required',
            'type' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
            ], 422);
        }
        $stripe = PaymentSetting::where('gateway_name', 'stripe')->first();
        Stripe::setApiKey($stripe->stripe_secret);

        $profile = Profile::where('user_id', Auth::user()->id)->first();
        if (!$profile) {
            Log::channel('payment_log')->error('Profile not found for user', ['user_id' => Auth::user()->id]);
            return response()->json([
                'status' => 'error',
                'message' => 'User profile not found',
            ], 404);
        }
        $country = Country::where('id', $profile->country_id)->first();
        if (!$country) {
            Log::channel('payment_log')->error('Country not found for profile', [
                'user_id' => Auth::user()->id,
                'profile_id' => $profile->id,
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Country not found, update your profile',
            ], 404);
        }
        // Create a Stripe account for the user
        try {
            DB::beginTransaction();
            $account = Account::create([
                'type' => 'express',
                'country' => $country->short_name, // Replace with the provider's country
                'email' => $request->email,
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
            ]);

            // Generate an account onboarding link
            $link = AccountLink::create([
                'account' => $account->id,
                'refresh_url' => config('app.frontend_url') . '/stripe-onboarding?type=' . $request->type,
                // 'return_url' => env('FRONTEND_URL') . '/stripe-connection/callback/' . Auth::user()->id,
                'return_url' => route('stripe.callback', [
                    'account_id' => $account->id,
                    'user_uuid' => Auth::user()->uuid,
                ]),
                'type' => 'account_onboarding',
            ]);

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Stripe account connected successfully',
                'url' => $link->url,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('payment_log')->error('Stripe connect failed: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'email' => $request->email,
                'trace' => $e->getTraceAsString(),
            ]);
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    //step 2
    public function storePaymentMethod(Request $request)
    {
        // Validate input
        $rules = [
            'email' => 'required|email',
            'accountHolderName' => 'required|string',
            'accountHolderType' => 'required|in:individual,company',
            'routingNumber' => 'required|string',
            'accountNumber' => 'required|string',
            'country' => 'required|string|size:2', // US or CA
            'amount1' => 'nullable|integer|min:1',
            'amount2' => 'nullable|integer|min:1',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
            ], 422);
        }

        $stripe = PaymentSetting::where('gateway_name', 'stripe')->first();

        Stripe::setApiKey($stripe->stripe_secret);

        $user = Auth::user();

        DB::beginTransaction();

        try {
            // Step 1: Create Stripe Customer if not exists
            if (!$user->stripe_customer_id) {
                $customer = Customer::create([
                    'email' => $request->email,
                    'name' => $request->accountHolderName,
                ]);
                $user->stripe_customer_id = $customer->id;
                $user->save();
            }

            // Step 2: Determine the payment method type
            $bankAccountType = $request->country === 'US' ? 'us_bank_account' : 'au_becs_debit';

            // Step 3: Create Payment Method
            $paymentMethod = PaymentMethod::create([
                'type' => $bankAccountType,
                $bankAccountType => [
                    'account_holder_type' => $request->accountHolderType,
                    'account_number' => $request->accountNumber,
                    'routing_number' => $request->routingNumber,
                ],
                'billing_details' => [
                    'name' => $request->accountHolderName,
                    'email' => $request->email,
                ],
            ]);

            // Save bank account details locally
            $check = BankAccount::where('uuid', $user->uuid)->first();
            if (!$check) {
                BankAccount::create([
                    'uuid' => $user->uuid,
                    'country' => $request->country,
                    'currency' => 'usd',
                    'account_holder_name' => $request->accountHolderName,
                    'account_holder_type' => $request->accountHolderType,
                    'routing_number' => $request->routingNumber,
                    'account_number' => $request->accountNumber,
                ]);
            }

            // Step 4: Check if the Stripe account is already verified
            $account = \Stripe\Account::retrieve($user->stripe_account_id);
            $isAccountVerified = $account->details_submitted && $account->charges_enabled;

            // Step 5: Handle Bank Account Verification for US Bank Account
            if ($request->country === 'US' && !$isAccountVerified) {
                $setupIntent = \Stripe\SetupIntent::create([
                    'payment_method' => $paymentMethod->id,
                    'customer' => $user->stripe_customer_id,
                    'payment_method_types' => ['us_bank_account'],
                    'usage' => 'off_session',
                ]);

                $setupIntent->confirm([
                    'payment_method' => $paymentMethod->id,
                    'mandate_data' => [
                        'customer_acceptance' => [
                            'type' => 'online',
                            'online' => [
                                'ip_address' => $request->ip(),
                                'user_agent' => $request->header('User-Agent'),
                            ],
                        ],
                    ],
                ]);

                if ($setupIntent->status === 'requires_action') {
                    $user->setup_intent_id = $setupIntent->id;
                    $user->save();

                    DB::commit();

                    return response()->json([
                        'status' => 'verification_required',
                        'message' => 'Micro-deposits have been sent to your bank account. Please verify the amounts to complete the setup.',
                        'setup_intent_id' => $setupIntent->id,
                    ]);
                } elseif ($setupIntent->status === 'succeeded') {
                    $paymentMethod->attach(['customer' => $user->stripe_customer_id]);
                    $user->stripe_payment_method_id = $paymentMethod->id;
                    $user->save();
                } else {
                    throw new \Exception('Micro-deposit verification failed.');
                }

                if ($request->has('amount1') && $request->has('amount2')) {
                    $setupIntent->verifyMicrodeposits([
                        'amounts' => [$request->amount1, $request->amount2],
                    ]);
                }
            } else {
                // If the account is already verified, directly attach the payment method
                $paymentMethod->attach(['customer' => $user->stripe_customer_id]);
                $user->stripe_payment_method_id = $paymentMethod->id;
                $user->save();
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Bank payment method saved successfully.',
                'payment_method_id' => $paymentMethod->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('payment_log')->error('Payment Request Failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
            ]);
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    //step3
    public function verifyMicrodeposits(Request $request)
    {
        // Validate input
        $rules = [
            'amount1' => 'required|integer',
            'amount2' => 'required|integer',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json(['errors' => $formattedErrors], 422);
        }

        $stripe = PaymentSetting::where('gateway_name', 'stripe')->first();

        Stripe::setApiKey($stripe->stripe_secret);

        try {
            $user = Auth::user();

            // Step 1: Retrieve the SetupIntent
            $setupIntent = \Stripe\SetupIntent::retrieve($user->setup_intent_id);

            // Step 2: Verify micro-deposits using the correct method
            $setupIntent->verifyMicrodeposits([
                'amounts' => [$request->amount1, $request->amount2], // User-provided amounts
            ]);

            // Step 3: Check if the SetupIntent verification was successful
            if ($setupIntent->status === 'succeeded') {
                // Attach payment method to user if verified
                $paymentMethod = \Stripe\PaymentMethod::retrieve($setupIntent->payment_method);
                $paymentMethod->attach(['customer' => $user->stripe_customer_id]);

                $user->stripe_payment_method_id = $paymentMethod->id;
                $user->save();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Micro-deposits verified successfully.',
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Micro-deposit verification failed. Please check the amounts and try again.',
                ], 400);
            }
        } catch (\Exception $e) {
            Log::channel('payment_log')->error('Verification Failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Verification failed. Please check the amounts and try again.',
            ], 500);
        }
    }

    public function stripeCallback(Request $request)
    {
        $stripe = PaymentSetting::where('gateway_name', 'stripe')->first();

        Stripe::setApiKey($stripe->stripe_secret);

        try {
            // Retrieve the authenticated user
            $accountId = $request->get('account_id');
            $user = User::where('uuid', $request->get('user_uuid'))->first();
            // $account = \Stripe\Account::retrieve($accountId);

            Log::channel('payment_log')->info('Stripe Callback - User', [
                'user_id' => $user->id,
                'email' => $user->email,
                'stripe_account_id' => $user->stripe_account_id
            ]);
            // Retrieve the connected account details (Stripe account)
            $account = \Stripe\Account::retrieve($accountId);

            Log::channel('payment_log')->info('Stripe Callback - Account', [
                'account_id' => $account->id,
                'details' => $account->details_submitted,
                'charges' => $account->charges_enabled
            ]);

            // Log the Stripe account details for debugging
            Log::channel('payment_log')->info('Stripe Account:', ['account' => $account]);

            // Create a Customer object for the user (if not already created)
            if (!$user->stripe_customer_id) {
                $customer = \Stripe\Customer::create([
                    'email' => $account->email,
                    'name' => $user->profile->first_name . ' ' . $user->profile->last_name,
                ]);

                // Log the newly created Stripe Customer
                Log::channel('payment_log')->info('Stripe Customer Created:', ['customer' => $customer]);
            }
            $user->stripe_customer_id = $customer->id;
            $user->stripe_account_id = $account->id;
            $user->stripe_email = $account->email;
            $user->save();
            return redirect()->to(env('FRONTEND_URL') . '/stripe-connection/success/' . $user->uuid);
        } catch (\Exception $e) {
            Log::channel('payment_log')->error('Stripe Connect Callback Failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->to(env('FRONTEND_URL') . '/stripe-connection/failed/' . $user->uuid);
        }
    }

    public function stripeCallbackTwo(Request $request)
    {
        $stripe = PaymentSetting::where('gateway_name', 'stripe')->first();

        Stripe::setApiKey($stripe->stripe_secret);


        $accountId = $request->query('account_id'); // or $request->account_id
        $userUuid  = $request->query('user_uuid');
        try {
            // Retrieve the authenticated user
            $user = User::where('uuid', $userUuid)->first();

            $user->stripe_account_id = $accountId;
            $user->save();


            Log::channel('payment_log')->info('Stripe Callback - User', [
                'user_id' => $user->id,
                'email' => $user->email,
                'stripe_account_id' => $user->stripe_account_id
            ]);

            // Create a Customer object for the user (if not already created)
            if (!$user->stripe_customer_id) {
                $customer = \Stripe\Customer::create([
                    'email' => $user->stripe_email,
                    'name' => $user->profile->first_name . ' ' . $user->profile->last_name,
                ]);
                // Save the Stripe customer ID in the user table
                $user->stripe_customer_id = $customer->id;
                $user->save();
                // Log the newly created Stripe Customer
                Log::channel('payment_log')->info('Stripe Customer Created:', ['customer' => $customer]);
            }
            $frontendUrl = rtrim(env('FRONTEND_URL'), '/') . '/stripe-connection/success/' . $user->uuid;
            Log::info('Redirecting to frontend', ['url' => $frontendUrl]);
            return redirect()->to($frontendUrl);
        } catch (\Exception $e) {
            // Log the full error message and stack trace
            Log::channel('payment_log')->error('Stripe Connect Completion Failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Redirect to frontend error page
            Log::channel('payment_log')->error('Stripe Connect Callback Failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->to(env('FRONTEND_URL') . '/stripe-connection/failed/' . $user->uuid);
        }
    }

    public function workOrderAssigne($client_id, $work_order_unique_id, $provider_id)
    {
        $stripe = PaymentSetting::where('gateway_name', 'stripe')->first();

        Stripe::setApiKey($stripe->stripe_secret);

        $user = $client_id;
        $work_order = WorkOrder::where('work_order_unique_id', $work_order_unique_id)->first();
        $payment = Payment::where('work_order_unique_id', $work_order_unique_id)->where('client_id', $user->uuid)->first();

        $totalFees = 0;
        $labor = $payment->total_labor ?? 0;
        $tax_fee = $payment->tax ?? 0;

        $servicesFeeData = $payment->services_fee ? json_decode($payment->services_fee, true) : null;

        foreach ($servicesFeeData as $fee) {
            // Ensure that both 'name' and 'percentage' exist in each fee entry
            if (isset($fee['name']) && isset($fee['percentage'])) {
                // Convert the percentage to decimal and calculate the fee
                $feePercentage = $fee['percentage'] / 100;
                $individualFee = $labor * $feePercentage;

                // Add this fee to the total fees
                $totalFees += $individualFee;
            }
        }

        $total_amount = $labor + $totalFees + $tax_fee;
        try {
            DB::beginTransaction();
            $paymentIntent = PaymentIntent::create([
                'amount' => $total_amount * 100, // Convert to cents
                'currency' => 'usd',
                'customer' => $user->stripe_customer_id,
                'payment_method' => $user->stripe_payment_method_id, // Payment Method ID
                'confirmation_method' => 'manual',
                'capture_method' => 'automatic', // Freeze funds
                'payment_method_types' => ['us_bank_account', 'card'], // Allow us_bank_account
                'metadata' => [
                    'work_order_id' => $work_order_unique_id,
                ],
            ]);
            $paymentIntent = $paymentIntent->confirm();
            $paymentIntentSave = new PaymentIntentSave();
            $paymentIntentSave->payment_intent_id = $paymentIntent->id;
            $paymentIntentSave->work_order_unique_id = $work_order_unique_id;
            $paymentIntentSave->provider_id = $provider_id;
            $paymentIntentSave->amount = $total_amount;
            $paymentIntentSave->intent_status = $paymentIntent->status;
            $paymentIntentSave->client_secret = $paymentIntent->client_secret;
            $paymentIntentSave->payment_method = $user->stripe_payment_method_id;
            $paymentIntentSave->description = 'Payment Intent create for work order ' . $work_order_unique_id;
            $paymentIntentSave->save();
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Payment Intent created successfully for work order ' . $work_order_unique_id,
                'client_secret' => $paymentIntent->client_secret,
                'payment_status' => $paymentIntent->status,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('payment_log')->error('Payment Intent create fail' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function clientWorkOrderPayment(Request $request)
    {
        $rules = [
            'work_order_unique_id' => 'required|exists:work_orders,work_order_unique_id',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        // Set Stripe API Key
        $stripe = PaymentSetting::where('gateway_name', 'stripe')->first();

        Stripe::setApiKey($stripe->stripe_secret);

        try {
            DB::beginTransaction();

            $client = Auth::user();
            $work_order_id = $request->work_order_unique_id;

            $paymentIntentSave = PaymentIntentSave::where('work_order_unique_id', $work_order_id)->first();
            $work_order = WorkOrder::where('work_order_unique_id', $work_order_id)->first();
            $payment = Payment::where('work_order_unique_id', $work_order_id)->where('client_id', $client->uuid)->first();

            if (!$payment || !$paymentIntentSave || !$work_order) {
                DB::rollBack();
                Log::channel('payment_log')->error('Missing required payment or work order data.');
                $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, 'Missing required payment or work order data.');
                return response()->json([
                    'status' => 'error',
                    'message' => $systemError,
                ], 500);
                // throw new \Exception("Missing required payment or work order data.");
            }

            // Calculate fees
            $totalFees = 0;
            $labor = $payment->total_labor ?? 0;
            $tax_fee = $payment->tax ?? 0;

            $servicesFeeData = $payment->services_fee ? json_decode($payment->services_fee, true) : [];

            foreach ($servicesFeeData as $fee) {
                if (isset($fee['name']) && isset($fee['percentage'])) {
                    $feePercentage = $fee['percentage'] / 100;
                    $totalFees += $labor * $feePercentage;
                }
            }

            $extra_amount = ($payment->pay_change_fee ?? 0) + ($payment->expense_fee ?? 0);
            $total_amount = $paymentIntentSave->amount + $extra_amount;

            // Retrieve and capture payment
            $paymentIntent = PaymentIntent::retrieve($paymentIntentSave->payment_intent_id);
            Log::channel('payment_log')->info('Payment Intent ID: ' . $paymentIntent->id);

            if ($paymentIntent->status === 'requires_capture') {
                $capturedPayment = $paymentIntent->capture();
                $paymentIntentSave->capture_status = $capturedPayment->status;
                $paymentIntentSave->capture_id = $capturedPayment->id;
            } elseif ($paymentIntent->status === 'succeeded') {
                $capturedPayment = $paymentIntent;
                $paymentIntentSave->capture_status = $paymentIntent->status;
                $paymentIntentSave->capture_id = $paymentIntent->id;
            } else {
                DB::rollBack();
                Log::channel('payment_log')->error('Unexpected PaymentIntent status: ' . $paymentIntent->status);
                $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, 'Unexpected PaymentIntent status: ' . $paymentIntent->status);
                return response()->json([
                    'status' => 'error',
                    'message' => $systemError,
                ]);
                // throw new \Exception("Unexpected PaymentIntent status: " . $paymentIntent->status);
            }

            // Transfer funds to admin
            $transfer = \Stripe\Transfer::create([
                'amount' => $total_amount * 100,
                'currency' => 'usd',
                'destination' => config('stripe.ACCOUNT_ID'),
            ]);
            Log::channel('payment_log')->info('Transfer ID: ' . $transfer->id);

            $paymentIntentSave->transfer_id = $transfer->id;
            $paymentIntentSave->save();

            // Update client's point balance
            $existingBalance = Payment::where('client_id', $client->uuid)
                ->where('status', 'Completed')
                ->orderBy('created_at', 'desc')
                ->first();

            $existing_point_balance = $existingBalance->point_balance ?? 0;
            Log::channel('payment_log')->info('Client existing balance: ' . $existing_point_balance);

            $new_balance = $existing_point_balance - $total_amount;
            Log::channel('payment_log')->info('Client new balance: ' . $new_balance);

            $payment->stripe_payment_intent_id = $capturedPayment->id;
            $payment->point_debit = $total_amount;
            $payment->point_balance = $new_balance;
            $payment->status = 'Under Review';
            $payment->save();

            // Update provider balance
            $provider_existingBalance = Payment::where('provider_id', $work_order->assigned_uuid)
                ->where('status', 'Deposited')
                ->orderBy('created_at', 'desc')
                ->first();

            $provider_balance = $provider_existingBalance->balance ?? 0;
            $provider_new_balance = $provider_balance + $labor + $extra_amount;

            Log::channel('payment_log')->info('Provider new balance: ' . $provider_new_balance);

            $payment_table = new Payment();
            $payment_table->provider_id = $work_order->assigned_uuid;
            $payment_table->work_order_unique_id = $work_order->work_order_unique_id;
            $payment_table->stripe_payment_intent_id = $capturedPayment->id;
            $payment_table->payment_unique_id = UniqueIdentifierService::generateUniqueIdentifier(new Payment(), 'payment_unique_id', 'uuid');
            $payment_table->credit = $labor + $extra_amount;
            $payment_table->balance = $provider_new_balance;
            $payment_table->status = 'Hold';
            $payment_table->transaction_type = 'Payment';
            $payment_table->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Payment transferred successfully.',
                'payment_intent' => $capturedPayment->id,
                'transfer_id' => $transfer->id,
                'status' => $capturedPayment->status,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('payment_log')->error('clientWorkOrderPayment failed: ' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function requestWithdrawal(Request $request)
    {
        $rules = [
            'amount' => 'required|numeric|min:50',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
            ], 422);
        }
        // Get the authenticated provider
        $provider = Auth::user();

        $user = User::where('uuid', $provider->uuid)->first();
        if ($user->stripe_account_id == null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Account not found',
            ]);
        }

        $profile = Profile::where('user_id', $provider->id)->first();
        if ($profile->social_security_number == null) {
            return response()->json([
                'status' => 'error',
                'message' => 'SSN not found',
            ]);
        }
        // Check if the provider has sufficient balance
        $providerBalance = Payment::where('provider_id', $provider->uuid)
            ->where('status', 'Deposited')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($providerBalance->balance < $request->amount) {
            return response()->json([
                'error' => 'Insufficient balance.',
            ], 400);
        }

        // Set Stripe API Key
        $stripe = PaymentSetting::where('gateway_name', 'stripe')->first();

        Stripe::setApiKey($stripe->stripe_secret);

        try {
            DB::beginTransaction();
            // Create a payout to the provider's connected Stripe account
            $payout = \Stripe\Payout::create([
                'amount' => $request->amount * 100, // Convert to cents
                'currency' => 'usd',
                'destination' => $provider->stripe_account_id, // Provider's connected Stripe account ID
            ]);

            // Deduct the withdrawal amount from the provider's balance
            $withdrawal = new Payment();
            $withdrawal->provider_id = $provider->uuid;
            $withdrawal->payment_unique_id = UniqueIdentifierService::generateUniqueIdentifier(new Payment(), 'payment_unique_id', 'uuid');
            $withdrawal->debit = $request->amount;
            $withdrawal->balance = $providerBalance - $request->amount;
            $withdrawal->status = 'Under Review';
            $withdrawal->transaction_type = 'Withdraw';
            $withdrawal->save();
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Withdrawal request successful.',
                'payout_id' => $payout->id,
                'balance' => $withdrawal->balance,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('payment_log')->error('Expense request query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    //clinet subscription
    public function purchaseSubscription(Request $request)
    {
        $rules = [
            'plan_id' => 'required|exists:plans,id',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }
        $plan = Plan::find($request->plan_id);

        $user = User::where('uuid', Auth::user()->uuid)->first();
        if (!$user->stripe_customer_id) {
            return response()->json([
                'status' => 'success',
                'message' => 'Account not found.',
                'plan_details' => $plan,
                'is_payable' => false,
            ], 200);
        }
        // Stripe
        $stripe_settings = PaymentSetting::where('gateway_name', 'stripe')->first();

        $stripe = new StripeClient($stripe_settings->stripe_secret);

        $client = Auth::user();

        // Save the subscription details
        $subscription = Subscription::where('uuid', $client->uuid)->first();
        if (!$subscription) {
            $subscription = new Subscription();
            $subscription->uuid = $client->uuid;
        }
        $subscription->status = 'Under Review';
        $subscription->plan_id = $plan->id;
        $subscription->amount = $plan->amount;
        $subscription->point = $plan->point;
        $subscription->work_order_fee = $plan->work_order_fee;
        $subscription->start_date_time = Carbon::now();
        $subscription->end_date_time = Carbon::now()->addMonths($plan->expired_month);
        $subscription->save();

        $existingBalance = Payment::where('client_id', $client->uuid)
            ->where('status', 'Completed')
            ->orderBy('created_at', 'desc')
            ->first();
        $new_balance = $existingBalance ? $existingBalance->point_balance + $plan->point : $plan->point;
        $payment_table = new Payment();
        $payment_table->client_id = $client->uuid;
        $payment_table->payment_unique_id = UniqueIdentifierService::generateUniqueIdentifier(new Payment(), 'payment_unique_id', 'uuid');
        $payment_table->credit = $plan->amount;
        $payment_table->balance = $plan->amount;
        $payment_table->point_credit = $plan->point;
        $payment_table->point_balance = $new_balance;
        $payment_table->status = 'Under Review';
        $payment_table->transaction_type = 'Subscription';
        $payment_table->gateway = 'Stripe';
        $payment_table->save();

        $stripe_response = $stripe->checkout->sessions->create([
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'USD',
                        'product_data' => [
                            'name' => $plan->name,
                        ],
                        'unit_amount' => intval($plan->amount * 100),
                    ],
                    'quantity' => 1,
                ],
            ],
            'mode' => 'payment',
            'success_url' => route('stripe.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('stripe.cancel', ['payment_id' => $payment_table->payment_unique_id]),
            'payment_intent_data' => [
                'metadata' => [
                    'payment_id' => $payment_table->payment_unique_id,
                ],
            ],
            'metadata' => [
                'payment_id' => $payment_table->payment_unique_id,
            ],
        ]);

        if (isset($stripe_response->id) && $stripe_response->id != '') {
            $responseArray['payment_link'] = $stripe_response->url;
            $responseArray['client'] = $client->profile->first_name . ' ' . $client->profile->last_name;
            $responseArray['email'] = Auth::user()->email;
            $responseArray['amount'] = $plan->amount;
            $responseArray['currency'] = "USD";
            $responseArray['service_name'] = 'Buy Subscription';
            $responseArray['gateway'] = 'Stripe';
        }
        return response()->json([
            'data' => $responseArray,
        ]);
    }

    public function stripe_success(Request $request)
    {
        $browserMetadata = [
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
        ];

        if (!isset($request->session_id)) {
            return redirect(env('FRONTEND_URL') . '/stripe-connection/failed');
        }

        try {
            $lookupIp = $request->ip() === '127.0.0.1' ? '8.8.4.4' : $request->ip();
            $locationData = Location::get($lookupIp);
            $locationIp = $locationData->ip ?? $request->ip();

            $stripe_settings = PaymentSetting::where('gateway_name', 'stripe')->first();
            $stripe = new StripeClient($stripe_settings->stripe_secret);

            $session_response = $stripe->checkout->sessions->retrieve($request->session_id);
            $payment_id = $session_response->metadata->payment_id ?? null;

            if (!$payment_id) {
                Log::channel('payment_log')->error('Stripe success missing payment_id metadata', [
                    'session_id' => $request->session_id,
                ]);
                return redirect(env('FRONTEND_URL') . '/stripe-connection/failed');
            }

            if ($session_response->payment_status === "paid") {
                $payment = Payment::where('payment_unique_id', $payment_id)->first();
                if ($payment) {
                    $payment->ip_address = json_encode($locationIp);
                    $payment->meta_data = json_encode($browserMetadata);
                    $payment->status = 'Completed';
                    if (!empty($session_response->payment_intent)) {
                        $payment->stripe_payment_intent_id = $session_response->payment_intent;
                    }
                    $payment->save();

                    $subscription = Subscription::where('uuid', $payment->client_id)->first();
                    if ($subscription) {
                        $subscription->status = 'Completed';
                        $subscription->save();
                    }
                }
                return redirect(env('FRONTEND_URL') . "/stripe-connection/success/$payment_id");
            }

            return redirect(env('FRONTEND_URL') . "/stripe-connection/failed/$payment_id");
        } catch (\Exception $e) {
            Log::channel('payment_log')->error('Stripe success handler failed', [
                'session_id' => $request->session_id,
                'error' => $e->getMessage(),
            ]);
            return redirect(env('FRONTEND_URL') . '/stripe-connection/failed');
        }
    }

    public function stripe_cancel($payment_id)
    {
        return redirect(env('FRONTEND_URL') . "/stripe-connection/failed/$payment_id");
    }
    // Stripe Webhook
    public function handleWebhook(Request $request)
    {
        // Always log first
        Log::channel('payment_log')->info('Stripe webhook received', [
            'ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
        ]);

        // --- CRITICAL: Get raw headers (bypasses Laravel & proxy stripping) ---
        $allHeaders = getallheaders(); // This reads Apache/Nginx raw headers
        $sig_header = $allHeaders['Stripe-Signature']
            ?? $_SERVER['HTTP_STRIPE_SIGNATURE']
            ?? $request->header('Stripe-Signature')
            ?? null;

        Log::channel('payment_log')->info('Stripe-Signature sources', [
            'getallheaders' => $allHeaders['Stripe-Signature'] ?? 'MISSING',
            '_SERVER' => $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? 'MISSING',
            'laravel' => $request->header('Stripe-Signature'),
        ]);

        if (!$sig_header) {
            Log::channel('payment_log')->error('Stripe-Signature header completely missing');
            return response('Missing signature', 400);
        }
        // Set Stripe API Key
        $stripe_settings = PaymentSetting::where('gateway_name', 'stripe')->first();

        Stripe::setApiKey($stripe_settings->stripe_secret);

        // Retrieve the webhook payload and signature
        $payload = $request->getContent();
        // $sig_header = $request->header('Stripe-Signature');

        try {
            // Verify the webhook signature
            $event = Webhook::constructEvent(
                $payload,
                $sig_header,
                config('stripe.WEBHOOK_SECRET')
            );
            Log::channel('payment_log')->info('Stripe webhook event verified', ['event_type' => $event->type]);
        } catch (\Exception $e) {
            Log::channel('payment_log')->error('Stripe webhook signature verification failed: ' . $e->getMessage());
            return response('Webhook Error', 400);
        }

        // Save the webhook event to the database (optional)
        $webhookLog = WebhookResponse::create([
            'event_id' => $event->id,
            'type' => $event->type,
            'payload' => $event->toArray(),
            'status' => 'pending',
        ]);
        Log::channel('payment_log')->info('Stripe webhook event logged', ['event_id' => $webhookLog->id, 'event_type' => $event->type]);
        // Process the webhook event
        try {
            Log::channel('payment_log')->info('Processing Stripe webhook event', ['event_type' => $event->type]);
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $paymentIntent = $event->data->object;

                    // Find the subscription and payment associated with this payment intent
                    $payment_id = $event->data->object->metadata->payment_id ?? null;
                    $subpayment = $payment_id
                        ? Payment::where('payment_unique_id', $payment_id)->where('transaction_type', 'Subscription')->first()
                        : null;
                    $subscription = $subpayment ? Subscription::where('uuid', $subpayment->client_id)->first() : null;
                    // $subpayment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->where('transaction_type', 'Subscription')->first();
                    $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->where('transaction_type', 'Payment')->first();

                    if ($subpayment) {
                        // Update the subscription status to "Completed"
                        if ($subscription) {
                            $subscription->status = 'Completed';
                            $subscription->save();
                        }

                        // Update the payment status to "Completed"
                        $subpayment->status = 'Completed';
                        $subpayment->save();
                    }

                    if ($payment) {
                        // Update the payment status to "Completed"
                        $payment->status = 'Completed';
                        $payment->save();

                        // Update the work order status if needed
                        $workOrder = WorkOrder::where('work_order_unique_id', $payment->work_order_unique_id)->first();
                        if ($workOrder) {
                            $workOrder->status = 'Done';
                            $workOrder->save();
                        }
                    }
                    Log::channel('payment_log')->info('Payment intent succeeded', [
                        'payment_id' => $payment_id,
                        'subscription_status' => $subscription->status ?? null,
                        'payment_status' => $payment->status ?? null,
                    ]);
                    break;
                case 'checkout.session.completed':
                    $session = $event->data->object;
                    $payment_id = $session->metadata->payment_id ?? null;
                    if (!$payment_id) {
                        Log::channel('payment_log')->error('Checkout session completed without payment_id metadata', [
                            'session_id' => $session->id ?? null,
                        ]);
                        break;
                    }

                    $subpayment = Payment::where('payment_unique_id', $payment_id)
                        ->where('transaction_type', 'Subscription')
                        ->first();
                    if ($subpayment) {
                        $subpayment->status = 'Completed';
                        if (!empty($session->payment_intent)) {
                            $subpayment->stripe_payment_intent_id = $session->payment_intent;
                        }
                        $subpayment->save();

                        $subscription = Subscription::where('uuid', $subpayment->client_id)->first();
                        if ($subscription) {
                            $subscription->status = 'Completed';
                            $subscription->save();
                        }
                    }

                    Log::channel('payment_log')->info('Checkout session completed', [
                        'payment_id' => $payment_id,
                        'session_id' => $session->id ?? null,
                    ]);
                    break;
                case 'payout.paid':
                    $payout = $event->data->object;

                    // Find the provider associated with this payout
                    $provider = User::where('stripe_account_id', $payout->destination)->first();

                    if ($provider) {
                        // Mark all "Deposited" payments as "Completed"
                        Payment::where('provider_id', $provider->uuid)
                            ->where('status', 'Deposited')
                            ->update(['status' => 'Completed']);

                        // Mark the withdrawal as "Completed"
                        Payment::where('provider_id', $provider->uuid)
                            ->where('status', 'Under Review')
                            ->where('transaction_type', 'Withdraw')
                            ->update(['status' => 'Completed']);
                    }
                    Log::channel('payment_log')->info('Payout paid', [
                        'payout_id' => $payout->id,
                        'provider_id' => $provider->uuid ?? null,
                    ]);
                    break;
                case 'payout.failed':
                    $payout = $event->data->object;

                    // Find the provider associated with this payout
                    $provider = User::where('stripe_account_id', $payout->destination)->first();

                    if ($provider) {
                        // Refund the provider's balance if the payout failed
                        $refundAmount = $payout->amount / 100; // Convert back to dollars
                        $providerBalance = Payment::where('provider_id', $provider->uuid)
                            ->where('status', 'Completed')
                            ->orderBy('created_at', 'desc')
                            ->first();

                        $refund = new Payment();
                        $refund->provider_id = $provider->uuid;
                        $refund->payment_unique_id = UniqueIdentifierService::generateUniqueIdentifier(new Payment(), 'payment_unique_id', 'uuid');
                        $refund->credit = $refundAmount;
                        $refund->balance = $providerBalance->balance + $refundAmount;
                        $refund->status = 'Refunded';
                        $refund->transaction_type = 'Refund';
                        $refund->save();
                    }
                    Log::channel('payment_log')->info('Payout failed', [
                        'payout_id' => $payout->id,
                        'provider_id' => $provider->uuid ?? null,
                    ]);
                    break;
                // Add more cases as needed
                default:
                    Log::channel('payment_log')->info('Unhandled Stripe webhook event: ' . $event->type);
                    break;
            }

            // Update the webhook log status to "processed"
            $webhookLog->update([
                'status' => 'processed',
            ]);
        } catch (\Exception $e) {
            // Log the error and update the webhook log status to "failed"
            Log::channel('payment_log')->error('Stripe webhook processing failed: ' . $e->getMessage());
            $webhookLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return response('Webhook Processing Failed', 500);
        }

        return response('Webhook Processed', 200);
    }

    public function deleteStripeAccount(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'Super Admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized action.',
            ], 403);
        }
        if (!$user->stripe_account_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'No Stripe account connected.',
            ], 404);
        }

        $stripe_settings = PaymentSetting::where('gateway_name', 'stripe')->first();

        Stripe::setApiKey($stripe_settings->stripe_secret);

        try {
            // Delete the Stripe account
            $account = \Stripe\Account::retrieve($user->stripe_account_id);
            if (!$account) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Stripe account not found.',
                ], 404);
            }
            // Disconnect the account
            $deletedAccount = $account->delete();

            if (!isset($deletedAccount->deleted) || !$deletedAccount->deleted) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete Stripe account on Stripe.',
                ], 500);
            }
            Log::channel('payment_log')->info('Stripe account deleted successfully', ['account_id' => $user->stripe_account_id]);

            //if account deleted successfully, remove the account details from the user else do nothing
            //how can check account deleted or not
            $user->stripe_account_id = null;
            $user->stripe_email = null;
            $user->stripe_customer_id = null;
            $user->stripe_payment_method_id = null;
            $user->setup_intent_id = null;
            $user->save();

            BankAccount::where('uuid', $user->uuid)->delete();

            Log::channel('payment_log')->info('User Stripe details cleared', ['user_id' => $user->id]);
            return response()->json([
                'status' => 'success',
                'message' => 'Stripe account disconnected and deleted successfully.',
            ]);
        } catch (\Exception $e) {
            Log::channel('payment_log')->error('Stripe Account Delete Failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete Stripe account.',
            ], 500);
        }
    }
}
