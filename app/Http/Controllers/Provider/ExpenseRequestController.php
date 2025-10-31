<?php

namespace App\Http\Controllers\Provider;

use App\Models\WorkOrder;
use App\Models\HistoryLog;
use Illuminate\Http\Request;
use App\Models\ExpenseRequest;
use App\Utils\ServerErrorMask;
use App\Classes\FileUploadClass;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Classes\NotificationSentClass;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Provider\ExpenseRequestResource;

class ExpenseRequestController extends Controller
{
    protected $fileUpload;
    protected $sentNotification;

    public function __construct(FileUploadClass $fileUpload, NotificationSentClass $sentNotification)
    {
        $this->fileUpload = $fileUpload;
        $this->sentNotification = $sentNotification;
    }

    public function index()
    {
        try {
            $expenseRequests = ExpenseRequest::query()
                ->where("user_id", Auth::user()->id)
                ->with([
                    'user' => fn($q) => $q->select(['id', 'organization_role', 'username', 'email', 'status']),
                    'expenseCategory' => fn($q) => $q->select(['id', 'name']),
                    'workOrder' => fn($q) => $q->select(['id', 'work_order_unique_id', 'uuid', 'assigned_id', 'status', 'assigned_status']),
                ])
                ->get();

            return response()->json([
                'status' => 'success',
                'expense_request' => ExpenseRequestResource::collection($expenseRequests),
            ]);
        } catch (\Exception $e) {
            Log::error('Expense request query not found' . $e->getMessage());
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
            'expense_requests' => 'required|array|min:1',
            'expense_requests.*.expense_category_id' => 'required|exists:expense_categories,id',
            'expense_requests.*.amount' => 'required|numeric|min:0',
            'expense_requests.*.description' => 'required|string',
            'expense_requests.*.file' => 'nullable|file|mimes:jpg,jpeg,png,heic,pdf,doc,docx|max:5120',
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
                ->select(['id', 'work_order_unique_id', 'uuid', 'assigned_id', 'status', 'assigned_status', 'assigned_uuid'])
                ->where('work_order_unique_id', $request->work_order_unique_id)
                ->first();

            if ($workOrder->assigned_uuid != Auth::user()->uuid) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your are not able to sent Expense request',
                ]);
            }

            // $expenseRequests = collect($request->expense_requests)->map(function ($data) {
            //     return $data = (object) $data;
            // });

            // foreach ($expenseRequests as $expenseReq) {

            //     $expenseRequest = new ExpenseRequest();

            //     $expenseRequest->user_id = Auth::user()->id;
            //     $expenseRequest->work_order_unique_id = $workOrder->work_order_unique_id;
            //     $expenseRequest->expense_category_id = $expenseReq->expense_category_id;
            //     $expenseRequest->amount = $expenseReq->amount;
            //     $expenseRequest->description = $expenseReq->description;

            //     if (!empty($expenseReq->file) && $expenseReq->hasFile("file")) {
            //         $image_url = $this->fileUpload->imageUploader($request->file('file'), 'expenseRequest');
            //         $expenseRequest->file = $image_url;
            //     }

            //     $expenseRequest->status = 'Pending';

            //     $expenseRequest->save();

            //     $this->sentNotification->expenseRequestSent($expenseRequest);

            // }
            foreach ($request->expense_requests as $index => $expenseReq) {
                $expenseRequest = new ExpenseRequest();

                $expenseRequest->user_id = Auth::user()->id;
                $expenseRequest->work_order_unique_id = $workOrder->work_order_unique_id;
                $expenseRequest->expense_category_id = $expenseReq['expense_category_id'];
                $expenseRequest->amount = $expenseReq['amount'];
                $expenseRequest->description = $expenseReq['description'];

                // Check if the file is uploaded and handle the upload
                if ($request->hasFile("expense_requests.$index.file")) {
                    $file = $request->file("expense_requests.$index.file");
                    $image_url = $this->fileUpload->imageUploader($file, 'expenseRequest');
                    $expenseRequest->file = $image_url;
                }

                $expenseRequest->status = 'Pending';

                $expenseRequest->save();

                $history = new HistoryLog();
                $history->provider_id = Auth::user()->id;
                $history->work_order_unique_id = $request->work_order_unique_id;
                $history->expense_request_id = $expenseRequest->id;
                $history->description = 'Expense Request';
                $history->type = 'provider';
                $history->date_time = now();
                $history->save();

                $this->sentNotification->expenseRequestSent($expenseRequest);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Expense Request successfully Sent',
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
        try {
            $expenseRequests = ExpenseRequest::query()
                ->where("user_id", Auth::user()->id)
                ->with([
                    'user' => fn($q) => $q->select(['id', 'organization_role', 'username', 'email', 'status']),
                    'expenseCategory' => fn($q) => $q->select(['id', 'name']),
                    'workOrder' => fn($q) => $q->select(['id', 'work_order_unique_id', 'uuid', 'assigned_id', 'status', 'assigned_status']),
                ])
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'expense_request' => new ExpenseRequestResource($expenseRequests),
            ]);
        } catch (\Exception $e) {
            Log::error('Expense request query not found' . $e->getMessage());
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
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string',
            'file' => 'nullable|file|mimes:jpg,jpeg,png,heic,pdf,doc,docx|max:5120',
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
                    'message' => 'Your are not able to sent Expense request',
                ]);
            }

            $expenseRequest = ExpenseRequest::query()
                ->where('user_id', Auth::user()->id)
                ->findOrFail($id);

            $expenseRequest->work_order_unique_id = $workOrder->work_order_unique_id;
            $expenseRequest->expense_category_id = $request->expense_category_id;
            $expenseRequest->amount = $request->amount;
            $expenseRequest->description = $request->description;

            if (!empty($request->file) && $request->hasFile("file")) {
                $image_url = $this->fileUpload->imageUploader($request->file('file'), 'expenseRequest');
                $expenseRequest->file = $image_url;
            }

            $expenseRequest->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Expense Request successfully Updated',
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
            $expenseRequests = ExpenseRequest::query()
                ->where("user_id", Auth::user()->id)
                ->findOrFail($id);

            $this->fileUpload->fileUnlink($expenseRequests->file);

            $expenseRequests->delete();

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Expense Request Successfully Deleted',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Expense request query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function workOrderExpenseRequest($work_order_unique_id)
    {
        $expenseRequests = ExpenseRequest::with('expenseCategory')
            ->where('work_order_unique_id', $work_order_unique_id)
            ->get();

        return response()->json([
            'status' => 'success',
            'expense_request' => $expenseRequests,
        ]);
    }
}
