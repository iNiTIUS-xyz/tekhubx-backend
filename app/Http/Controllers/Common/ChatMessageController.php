<?php

namespace App\Http\Controllers\Common;

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Utils\ServerErrorMask;
use App\Events\MessageSentEvent;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\WorkOrderChatMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\NotificationResource;

class ChatMessageController extends Controller
{
    public function messageUserList($work_unique_id)
    {
        try {
            $chatUserList = User::query()
                ->whereHas('sentMessages', fn($q) => $q->where('receiver_id', Auth::user()->id)->where('work_order_unique_id', $work_unique_id))
                ->orWhereHas('receivedMessages', fn($q) => $q->where('sender_id', Auth::user()->id)->where('work_order_unique_id', $work_unique_id))
                ->with([
                    'profile',
                    'latestSentMessage' => fn($q) => $q->where('work_order_unique_id', $work_unique_id),
                    'latestReceivedMessage' => fn($q) => $q->where('work_order_unique_id', $work_unique_id),
                ])
                ->get();

            return response()->json([
                'status' => 'success',
                'user_list' => $chatUserList,
            ]);
        } catch (\Exception $e) {

            Log::error('Message user list not found: ' . $e->getMessage());

            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }
    public function fetchMessages(Request $request, $work_unique_id)
    {
        try {

            $receiverId = $request->receiver_id;

            $messagesQuery = WorkOrderChatMessage::query()
                ->where('work_order_unique_id', $work_unique_id)
                ->when($receiverId, function ($query) use ($receiverId, $work_unique_id) {
                    $query->where(function ($q) use ($receiverId, $work_unique_id) {
                        $q->where('sender_id', Auth::id())
                            ->where('work_order_unique_id', $work_unique_id)
                            ->where('receiver_id', $receiverId);
                    })->orWhere(function ($q) use ($receiverId, $work_unique_id) {
                        $q->where('sender_id', $receiverId)
                            ->where('work_order_unique_id', $work_unique_id)
                            ->where('receiver_id', Auth::id());
                    });
                });

            if (Auth::user()->organization_role == 'Client' || Auth::user()->organization_role == 'Provider' || Auth::user()->organization_role == 'Provider Company') {

                $messages = $messagesQuery->with([
                    'sender' => fn($q) => $q->select(['id', 'username', 'email']),
                    'sender.profile' => fn($q) => $q->select(['id', 'user_id', 'first_name', 'last_name', 'profile_image']),
                    'receiver' => fn($q) => $q->select(['id', 'username', 'email']),
                    'receiver.profile' => fn($q) => $q->select(['id', 'user_id', 'first_name', 'last_name', 'profile_image']),
                ])->get();

                return response()->json([
                    'status' => 'success',
                    'messages' => $messages,
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to view these messages.',
            ], 403);
        } catch (\Exception $e) {
            Log::error('Something went wrong: ' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function sendMessage(Request $request, $work_unique_id)
    {
        $rules = [
            'message' => 'required',
            'receiver_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
            ], 422);
        }

        $workOrder = WorkOrder::query()
            ->select(['id', 'work_order_unique_id', 'assigned_id', 'uuid'])
            ->where('work_order_unique_id', $work_unique_id)
            ->first();

        if (!$workOrder) {
            return response()->json([
                'status' => 'error',
                'message' => 'Work Order Not Found',
            ], 403);
        }

        $receiverUser = User::query()
            ->find($request->receiver_id);

        if (!$receiverUser) {
            return response()->json([
                'status' => 'error',
                'message' => 'Receiver User not found',
            ], 403);
        }

        if ($workOrder->assigned_id) {

            if (Auth::user()->organization_role === 'Provider') {

                if ($workOrder->assigned_id != Auth::user()->id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You are not allowed to send messages. Because this work already assigned other',
                    ], 403);
                }
            }
        }

        try {
            DB::beginTransaction();

            $chatMessage = new WorkOrderChatMessage();
            $chatMessage->work_order_unique_id = $work_unique_id;
            $chatMessage->sender_id = Auth::user()->id;
            $chatMessage->receiver_id = $receiverUser->id;
            $chatMessage->message = $request->message;
            $chatMessage->request_date_time = now();
            $chatMessage->save();

            broadcast(new MessageSentEvent($chatMessage))->toOthers();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Message sent successfully!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Message not sent: ' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function chatList(Request $request)
    {
        try {

            $user = Auth::user();

            $messagesQuery = WorkOrderChatMessage::query()
                ->where('sender_id', $user->id)
                ->orWhere('receiver_id', $user->id);

            if (!in_array($user->organization_role, ['Client', 'Provider'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to view these messages.',
                ], 403);
            }

            if (Auth::user()->organization_role == 'Client' || Auth::user()->organization_role == 'Provider') {

                $messages = $messagesQuery->with([
                    'sender' => fn($q) => $q->select(['id',  'username', 'email']),
                    'receiver' => fn($q) => $q->select(['id', 'username', 'email']),
                    'sender.profile'  => fn($q) => $q->select(['id',  'user_id',  'phone', 'profile_image']),
                    'receiver.profile'  => fn($q) => $q->select(['id',  'user_id',  'phone', 'profile_image']),
                ])->get();

                return response()->json([
                    'status' => 'success',
                    'messages' => $messages,
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to view these messages.',
            ], 403);
        } catch (\Throwable $e) {
            Log::error('Chat message not showing not sent: ' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }


    // public function getNotification()
    // {
    //     try {
    //         $user = Auth::user();
    //         $userId = $user->id;
    //         $role = $user->organization_role;

    //         $notifications = Notification::query()
    //             ->where(function ($query) use ($userId) {
    //                 $query->where('receiver_id', $userId)
    //                     ->orWhere('sender_id', $userId);
    //             })
    //             ->where('is_read', false)
    //             ->with([
    //                 'sender' => fn($q) => $q->select(['id', 'username', 'email']),
    //                 'sender.profile' => fn($q) => $q->select(['id', 'user_id', 'first_name', 'last_name', 'profile_image']),
    //                 'receiver' => fn($q) => $q->select(['id', 'username', 'email']),
    //                 'receiver.profile' => fn($q) => $q->select(['id', 'user_id', 'first_name', 'last_name', 'profile_image']),
    //                 'workOrder' => fn($q) => $q->select(['id', 'uuid', 'user_id', 'work_order_unique_id', 'work_order_title']),
    //             ])
    //             ->orderBy('created_at', 'desc')
    //             ->get()
    //             ->filter(function ($notification) use ($userId, $role) {
    //                 // Notifications that are shown ONLY to receiver
    //                 $onlyReceiverTypes = [
    //                     'Counter Offer',
    //                     'Work Order Request',
    //                     'Work Order Expense Request',
    //                     'Work Order Pay Change',
    //                     'Work Order Make Completed By Provider',
    //                 ];

    //                 // Notifications that are shown ONLY to sender (client)
    //                 $onlySenderTypes = [
    //                     'Counter Offer Assign',
    //                     'Work Request Assign',
    //                     'Work Order Expense Request Approved',
    //                     'Work Order Pay Change Approved',
    //                     'Work Order Create Notification',
    //                 ];

    //                 if (in_array($notification->type, $onlyReceiverTypes)) {
    //                     return $notification->receiver_id === $userId;
    //                 }

    //                 if (in_array($notification->type, $onlySenderTypes)) {
    //                     return $notification->receiver_id === $userId;
    //                 }

    //                 // Fallback: show if user is either sender or receiver
    //                 return $notification->receiver_id === $userId || $notification->sender_id === $userId;
    //             });

    //         return response()->json([
    //             'status' => 'success',
    //             'notifications' => NotificationResource::collection($notifications),
    //         ]);
    //     } catch (\Throwable $e) {
    //         Log::error('Notifications not query not found: ' . $e->getMessage());
    //         $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $systemError,
    //         ], 500);
    //     }
    // }
    public function getNotification()
    {
        try {
            $user = Auth::user();
            $userId = $user->id;

            // Define notification types based on direction
            $providerToClientTypes = [
                'Counter Offer',
                'Work Order Request',
                'Work Order Expense Request',
                'Work Order Pay Change',
                'Work Order Make Completed By Provider',
            ];

            $clientToProviderTypes = [
                'Work Order Create Notification',
                'Counter Offer Assign',
                'Counter Offer Assign Cancel',
                'Work Request Assign',
                'Work Order Expense Request Approved',
                'Work Order Pay Change Approved',
            ];

            // Start building base query
            $notifications = Notification::query()
                // ->where('receiver_id', $userId)
                ->where('is_read', false)
                ->with([
                    'sender' => fn($q) => $q->select(['id', 'username', 'email']),
                    'sender.profile' => fn($q) => $q->select(['id', 'user_id', 'first_name', 'last_name', 'profile_image']),
                    'receiver' => fn($q) => $q->select(['id', 'username', 'email']),
                    'receiver.profile' => fn($q) => $q->select(['id', 'user_id', 'first_name', 'last_name', 'profile_image']),
                    'workOrder' => fn($q) => $q->select(['id', 'uuid', 'user_id', 'work_order_unique_id', 'work_order_title']),
                ]);

            // Filter notifications based on role
            if ($user->organization_role === 'Client') {
                $notifications->whereIn('type', $providerToClientTypes);
            } elseif ($user->organization_role === 'Provider' || $user->organization_role === 'Provider Company') {
                $notifications->whereIn('type', $clientToProviderTypes);
            }

            $notifications = $notifications->orderBy('created_at', 'desc')->get();

            return response()->json([
                'status' => 'success',
                'notifications' => NotificationResource::collection($notifications),
            ]);
        } catch (\Throwable $e) {
            Log::error('Notifications query failed: ' . $e->getMessage());

            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);

            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }


    public function notificationRead(Request $request)
    {
        try {
            $notification = Notification::find($request->id);
            $notification->is_read = (bool) $request->is_read;
            $notification->save();
            return response()->json([
                'status' => 'success',
                'message' => 'Notification marked as read',
            ]);
        } catch (\Throwable $e) {
            Log::error('Notification not read: ' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function notificationReadAll()
    {
        try {
            Notification::where('receiver_id', Auth::user()->id)
                ->orWhere('sender_id', Auth::user()->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            return response()->json([
                'status' => 'success',
                'message' => 'All notifications marked as read',
            ]);
        } catch (\Throwable $e) {
            Log::error('Mark all notifications as read failed: ' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }
}
