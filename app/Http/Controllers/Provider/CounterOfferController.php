<?php

namespace App\Http\Controllers\Provider;

use DateTime;
use App\Models\WorkOrder;
use App\Models\HistoryLog;
use App\Models\CounterOffer;
use App\Utils\ServerErrorMask;
use App\Models\CounterOfferPay;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use App\Models\CounterOfferExpense;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\CounterOfferSchedule;
use Illuminate\Support\Facades\Auth;
use App\Classes\NotificationSentClass;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Provider\CounterOfferRequest;
use App\Http\Resources\Provider\CounterOfferResource;

class CounterOfferController extends Controller
{
    protected $sentNotification;

    public function __construct(NotificationSentClass $sentNotification)
    {
        $this->sentNotification = $sentNotification;
        // $this->middleware('permission:counter_offer,counter_offer.list')->only(['index']);
        $this->middleware('permission:counter_offer.create_store')->only(['store']);
    }

    public function index()
    {
        try {
            $counterOffer = CounterOffer::where('uuid', Auth::user()->uuid)
                ->with([
                    'counterOfferPay',
                    'counterOfferSchedule',
                    'uuidUser',
                ])
                ->get();

            return response()->json([
                'status' => 'success',
                'counter_offer' => CounterOfferResource::collection($counterOffer),
            ]);
        } catch (\Exception $e) {
            Log::error('Counter offer query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }
    public function store(CounterOfferRequest $request)
    {
        $rules = [
            'counter_offers' => 'required|array|min:1',
            'counter_offers.*.work_order_unique_id' => 'required|integer|exists:work_orders,work_order_unique_id',
            'counter_offers.*.employed_providers_id' => 'required|integer',
            'counter_offers.*.counter_offer_pays' => 'nullable|array',
            'counter_offers.*.counter_offer_pays.type' => 'nullable|string|in:Hourly,Fixed,Per Device,Blended',
            'counter_offers.*.counter_offer_pays.amount_per_hour' => 'nullable|numeric',
            'counter_offers.*.counter_offer_pays.max_hour' => 'nullable|numeric',
            'counter_offers.*.counter_offer_pays.total_pay_amount' => 'nullable|numeric',
            'counter_offers.*.counter_offer_pays.amount_per_device' => 'nullable|numeric',
            'counter_offers.*.counter_offer_pays.max_device' => 'nullable|numeric',
            'counter_offers.*.counter_offer_pays.fixed_amount' => 'nullable|numeric',
            'counter_offers.*.counter_offer_pays.fixed_amount_max_hours' => 'nullable|numeric',
            'counter_offers.*.counter_offer_pays.hourly_amount_after' => 'nullable|numeric',
            'counter_offers.*.counter_offer_pays.hourly_amount_max_hours' => 'nullable|numeric',
            'counter_offers.*.counter_offer_expenses' => 'nullable|array',
            'counter_offers.*.counter_offer_expenses.*.category' => 'nullable',
            'counter_offers.*.counter_offer_expenses.*.description' => 'nullable:counter_offers.*.counter_offer_expenses|string',
            'counter_offers.*.counter_offer_expenses.*.total_amount' => 'nullable|numeric',
            'counter_offers.*.counter_offer_schedule' => 'nullable|array',
            'counter_offers.*.counter_offer_schedule.type' => 'nullable|string',
            'counter_offers.*.counter_offer_schedule.arrive_on' => 'nullable',
            'counter_offers.*.counter_offer_schedule.start_at' => 'nullable',
            'counter_offers.*.counter_offer_schedule.start_date' => 'nullable',
            'counter_offers.*.counter_offer_schedule.start_time' => 'nullable',
            'counter_offers.*.counter_offer_schedule.end_date' => 'nullable',
            'counter_offers.*.counter_offer_schedule.end_time' => 'nullable',
            'counter_offers.*.reason' => 'nullable',
        ];

        $validator = Validator::make($request->all(), $rules);

        $validator->sometimes('counter_offers.*.counter_offer_pays.amount_per_hour', 'required|numeric', function ($input, $item) {
            return isset($item['counter_offer_pays']) && $item['counter_offer_pays']['type'] === 'Hourly';
        });

        $validator->sometimes('counter_offers.*.counter_offer_pays.max_hour', 'required|numeric', function ($input, $item) {
            return isset($item['counter_offer_pays']) && $item['counter_offer_pays']['type'] === 'Hourly';
        });

        $validator->sometimes('counter_offers.*.counter_offer_pays.total_pay_amount', 'required|numeric', function ($input, $item) {
            return isset($item['counter_offer_pays']) && $item['counter_offer_pays']['type'] === 'Fixed';
        });

        $validator->sometimes('counter_offers.*.counter_offer_pays.amount_per_device', 'required|numeric', function ($input, $item) {
            return isset($item['counter_offer_pays']) && $item['counter_offer_pays']['type'] === 'Per Device';
        });

        $validator->sometimes('counter_offers.*.counter_offer_pays.max_device', 'required|numeric', function ($input, $item) {
            return isset($item['counter_offer_pays']) && $item['counter_offer_pays']['type'] === 'Per Device';
        });

        $validator->sometimes('counter_offers.*.counter_offer_pays.hourly_amount_max_hours', 'required|numeric', function ($input, $item) {
            return isset($item['counter_offer_pays']) && $item['counter_offer_pays']['type'] === 'Blended';
        });

        $validator->sometimes('counter_offers.*.counter_offer_pays.hourly_amount_after', 'required|numeric', function ($input, $item) {
            return isset($item['counter_offer_pays']) && $item['counter_offer_pays']['type'] === 'Blended';
        });

        $validator->sometimes('counter_offers.*.counter_offer_pays.fixed_amount', 'required|numeric', function ($input, $item) {
            return isset($item['counter_offer_pays']) && $item['counter_offer_pays']['type'] === 'Blended';
        });

        $validator->sometimes('counter_offers.*.counter_offer_pays.fixed_amount_max_hours', 'required|numeric', function ($input, $item) {
            return isset($item['counter_offer_pays']) && $item['counter_offer_pays']['type'] === 'Blended';
        });

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 422);
        }

        try {
            DB::beginTransaction();

            $counterOffers = $request->counter_offers;

            $checkOrderStatus = $this->validateWorkOrderStatus($request);

            if ($checkOrderStatus[0] == false) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Work order already ' . $checkOrderStatus[1]->status . '. You can not counter offer against this work order',
                ]);
            }

            foreach ($counterOffers as $counter_offer) {
                $counter_offer = (object) $counter_offer;

                $afterWithdraw = $counter_offer->withdraw;
                $currentDateTime = new DateTime();

                if (strpos($afterWithdraw, 'Minute') !== false) {
                    $minutes = intval($afterWithdraw);
                    $currentDateTime->modify("+{$minutes} minutes");
                } elseif (strpos($afterWithdraw, 'Hour') !== false) {
                    $hours = intval($afterWithdraw);
                    $currentDateTime->modify("+{$hours} hours");
                } elseif (strpos($afterWithdraw, 'Day') !== false) {
                    $days = intval($afterWithdraw);
                    $currentDateTime->modify("+{$days} days");
                }

                // Create and save CounterOffer
                $count_off = new CounterOffer();
                $count_off->uuid = Auth::user()->uuid;
                $count_off->user_id = Auth::user()->id;
                $count_off->work_order_unique_id = $counter_offer->work_order_unique_id;
                $count_off->employed_providers_id = $counter_offer->employed_providers_id;
                $count_off->reason = $counter_offer->reason;
                $count_off->withdraw = $counter_offer->withdraw;
                $count_off->expired_request_time = $currentDateTime->format('Y-m-d H:i:s');
                $count_off->status = 'Active';
                $count_off->save();

                $history = new HistoryLog();
                $history->provider_id = Auth::user()->id;
                $history->work_order_unique_id = $request->work_order_unique_id;
                $history->work_order_counter_offer_id = $count_off->id;
                $history->description = 'Work Order Request With Counter Offer';
                $history->type = 'provider';
                $history->date_time = now();
                $history->save();

                $this->sentNotification->counterOfferSent($count_off);

                // Store counter_offer_pays if it exists
                if (isset($counter_offer->counter_offer_pays) && $counter_offer->counter_offer_pays) {
                    $counterOfferPay = (object) $counter_offer->counter_offer_pays;

                    $counterPay = new CounterOfferPay();
                    $counterPay->type = $counterOfferPay->type ?? null;
                    $counterPay->max_hour = $counterOfferPay->max_hour ?? null;
                    $counterPay->amount_per_hour = $counterOfferPay->amount_per_hour ?? null;
                    $counterPay->total_pay_amount = $counterOfferPay->total_pay_amount ?? null;
                    $counterPay->amount_per_device = $counterOfferPay->amount_per_device ?? null;
                    $counterPay->max_device = $counterOfferPay->max_device ?? null;
                    $counterPay->fixed_amount = $counterOfferPay->fixed_amount ?? null;
                    $counterPay->fixed_amount_max_hours = $counterOfferPay->fixed_amount_max_hours ?? null;
                    $counterPay->hourly_amount_after = $counterOfferPay->hourly_amount_after ?? null;
                    $counterPay->hourly_amount_max_hours = $counterOfferPay->hourly_amount_max_hours ?? null;
                    $counterPay->save();

                    // Associate the saved records with CounterOffer
                    $count_off->pay_id = $counterPay ? $counterPay->id : null;
                    $count_off->save();
                }

                // Store counter_offer_expenses if they exist
                $expenseIds = [];
                if (isset($counter_offer->counter_offer_expenses) && count($counter_offer->counter_offer_expenses) > 0) {
                    foreach ($counter_offer->counter_offer_expenses as $counterOfferExpense) {
                        $counterOfferExpense = (object) $counterOfferExpense;

                        $expense = new CounterOfferExpense();
                        $expense->category = $counterOfferExpense->category;
                        $expense->description = $counterOfferExpense->description;
                        $expense->total_amount = $counterOfferExpense->total_amount ?? null;
                        $expense->save();

                        $expenseIds[] = $expense->id;
                    }
                    $count_off->expense_id = $expenseIds ? json_encode($expenseIds) : null;
                    $count_off->save();
                }

                // Store counter_offer_schedule if it exists
                if (isset($counter_offer->counter_offer_schedule) && $counter_offer->counter_offer_schedule) {
                    $counterOfferSchedule = (object) $counter_offer->counter_offer_schedule;
                    $schedule = new CounterOfferSchedule();
                    $schedule->type = $counterOfferSchedule->type ?? null;
                    $schedule->arrive_on = $counterOfferSchedule->arrive_on ?? null;
                    $schedule->start_at = $counterOfferSchedule->start_at ?? null;
                    $schedule->start_date = $counterOfferSchedule->start_date ?? null;
                    $schedule->start_time = $counterOfferSchedule->start_time ?? null;
                    $schedule->end_date = $counterOfferSchedule->end_date ?? null;
                    $schedule->end_time = $counterOfferSchedule->end_time ?? null;
                    $schedule->save();

                    $count_off->schedule_id = $schedule ? $schedule->id : null;
                    $count_off->save();
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Counter Offer Successfully Sent',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Counter offer query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function validateWorkOrderStatus($request)
    {

        $workReqSends = collect($request->counter_offers)->groupBy('work_order_unique_id');

        $workOrderIds = $workReqSends->keys();


        $work = WorkOrder::query()
            ->where('work_order_unique_id', $workOrderIds[0])
            ->first();

        if ($work->status == 'Active' || $work->status == 'Published') {
            return [true, $work];
        }

        return [false, $work];
    }
}
