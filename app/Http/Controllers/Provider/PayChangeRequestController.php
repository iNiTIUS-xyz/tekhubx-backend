<?php

namespace App\Http\Controllers\Provider;

use App\Models\PayChange;
use App\Models\WorkOrder;
use App\Models\HistoryLog;
use Illuminate\Http\Request;
use App\Utils\ServerErrorMask;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Classes\NotificationSentClass;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Provider\PayChangeResource;

class PayChangeRequestController extends Controller
{
    protected $sentNotification;

    public function __construct(NotificationSentClass $sentNotification)
    {
        $this->sentNotification = $sentNotification;
    }

    public function index()
    {
        try {
            $payChanges = PayChange::query()
                ->where("user_id", Auth::user()->id)
                ->with([
                    'user' => fn($q) => $q->select(['id', 'organization_role', 'username', 'email', 'status']),
                    'workOrder' => fn($q) => $q->select(['id', 'work_order_unique_id', 'uuid', 'assigned_id', 'status', 'assigned_status']),
                ])
                ->get();

            return response()->json([
                'status' => 'success',
                'pay_change' => PayChangeResource::collection($payChanges),
            ]);
        } catch (\Exception $e) {
            Log::error('Pay change query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $rules = [
            'work_order_unique_id' => 'required|exists:work_orders,work_order_unique_id',
            'reason' => 'required|string|min:10',
            'extra_hour' => 'nullable',
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

            $workOrder = WorkOrder::query()
                ->select(['id', 'work_order_unique_id', 'uuid', 'assigned_id', 'assigned_uuid', 'status', 'assigned_status'])
                ->where('work_order_unique_id', $request->work_order_unique_id)
                ->first();

            if ($workOrder->assigned_uuid != Auth::user()->uuid) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your are not able to sent pay change request',
                ]);
            }

            $payChange = new PayChange();

            $payChange->user_id = Auth::user()->id;
            $payChange->work_order_unique_id = $workOrder->work_order_unique_id;
            $payChange->reason = $request->reason;
            $payChange->extra_hour = $request->extra_hour;
            $payChange->status = 'Pending';

            $payChange->save();

            $history = new HistoryLog();
            $history->provider_id = Auth::user()->id;
            $history->work_order_unique_id = $request->work_order_unique_id;
            $history->paychange_id = $payChange->id;
            $history->description = 'Payment Change Request';
            $history->type = 'provider';
            $history->date_time = now();
            $history->save();

            $this->sentNotification->payChangeSent($payChange);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pay Change Request Successfully Sent',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Store filed' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function edit(string $id)
    {
        //
        try {
            $payChanges = PayChange::query()
                ->where("user_id", Auth::user()->id)
                ->with([
                    'user' => fn($q) => $q->select(['id', 'organization_role', 'username', 'email', 'status']),
                    'workOrder' => fn($q) => $q->select(['id', 'work_order_unique_id', 'uuid', 'assigned_id', 'status', 'assigned_status']),
                ])
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'pay_change' => new PayChangeResource($payChanges),
            ]);
        } catch (\Exception $e) {
            Log::error('Pay change query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function update(Request $request, string $id)
    {

        $rules = [
            'work_order_unique_id' => 'required|exists:work_orders,work_order_unique_id',
            'reason' => 'required|string|min:10',
            'extra_hour' => 'nullable',
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

            $workOrder = WorkOrder::query()
                ->select(['id', 'work_order_unique_id', 'uuid', 'assigned_id', 'status', 'assigned_status'])
                ->where('work_order_unique_id', $request->work_order_unique_id)
                ->first();

            if ($workOrder->assigned_id != Auth::user()->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your are not able to sent pay change request',
                ]);
            }

            $payChange = PayChange::query()
                ->where('user_id', Auth::user()->id)
                ->findOrFail($id);

            $payChange->work_order_unique_id = $workOrder->work_order_unique_id;
            $payChange->reason = $request->reason;
            $payChange->extra_hour = $request->extra_hour;

            $payChange->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pay Change Request Successfully Updated',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Store filed' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            DB::beginTransaction();
            $payChanges = PayChange::query()
                ->where("user_id", Auth::user()->id)
                ->findOrFail($id);

            $payChanges->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pay Change Request Successfully Deleted',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Pay change query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function workOrderPayChangeRequest($work_order_unique_id)
    {
        $payChangeRequests = PayChange::query()
            ->where('work_order_unique_id', $work_order_unique_id)
            ->get();

        return response()->json([
            'status' => 'success',
            'pay_change' => PayChangeResource::collection($payChangeRequests),
        ]);
    }
}
