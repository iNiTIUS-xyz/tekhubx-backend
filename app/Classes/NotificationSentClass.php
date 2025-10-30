<?php

namespace App\Classes;

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use App\Events\NotificationSentEvent;
use App\Http\Resources\NotificationResource;

class NotificationSentClass
{
    public function counterOfferSent($counterOffer)
    {

        $workOrder = WorkOrder::query()
            ->where('work_order_unique_id', $counterOffer->work_order_unique_id)
            ->with('user')
            ->first();

        $notificationText = $workOrder?->user?->username . ' Counter offer against your work order ' . $workOrder->work_order_title . '( ' . $workOrder->work_order_unique_id . ' )';

        $notification = new Notification();
        $notification->sender_id = Auth::user()->id;
        $notification->receiver_id = $workOrder?->user?->id;
        $notification->counter_offer_id = $counterOffer->id;
        $notification->work_order_unique_id = $counterOffer->work_order_unique_id;
        $notification->type = 'Counter Offer';
        $notification->notification_text = $notificationText;
        $notification->is_read = false;
        $notification->save();

        // broadcast(new NotificationSentEvent($notification))->toOthers();
        $resource = new NotificationResource($notification);
        broadcast(new NotificationSentEvent($resource))->toOthers();

        return true;
    }

    public function workRequestSent($workRequests)
    {

        $workOrder = WorkOrder::query()
            ->where('work_order_unique_id', $workRequests->work_order_unique_id)
            ->with('user')
            ->first();


        $notificationText = $workOrder?->user?->username . ' Sent Work Request to your work order ' . $workOrder->work_order_title . '( ' . $workOrder->work_order_unique_id . ' )';

        $notification = new Notification();
        $notification->sender_id = Auth::user()->id;
        $notification->receiver_id = $workOrder?->user?->id;
        $notification->send_work_request_id = $workRequests->id;
        $notification->work_order_unique_id = $workRequests->work_order_unique_id;
        $notification->type = 'Work Order Request';
        $notification->notification_text = $notificationText;
        $notification->is_read = false;
        $notification->save();

        // broadcast(new NotificationSentEvent($notification))->toOthers();
        $resource = new NotificationResource($notification);
        broadcast(new NotificationSentEvent($resource))->toOthers();

        return true;
    }

    public function counterOfferAssignSent($workOrder, $counterOffer, $status = 'yes')
    {
        $workOrder = WorkOrder::query()
            ->where('work_order_unique_id', $workOrder->work_order_unique_id)
            ->with([
                'user',
                'assignUser',
            ])
            ->first();

        if ($status == 'no') {
            $notificationText = $workOrder?->user?->username . ' Counter Offer Cancel for you. Work order is ' . $workOrder->work_order_title . '( ' . $workOrder->work_order_unique_id . ' )';
        } else {
            $notificationText = $workOrder?->user?->username . ' Work Order Successfully Assign to you. Work order is ' . $workOrder->work_order_title . '( ' . $workOrder->work_order_unique_id . ' )';
        }

        $notification = new Notification();
        $notification->sender_id = Auth::user()->id;
        $notification->receiver_id = $workOrder?->assignUser?->id;
        $notification->counter_offer_id = $counterOffer->id;
        $notification->work_order_unique_id = $workOrder->work_order_unique_id;
        $notification->type = $status == 'yes' ? 'Counter Offer Assign' : 'Counter Offer Assign Cancel';
        $notification->notification_text = $notificationText;
        $notification->is_read = false;
        $notification->save();

        // broadcast(new NotificationSentEvent($notification))->toOthers();
        $resource = new NotificationResource($notification);
        broadcast(new NotificationSentEvent($resource))->toOthers();

        return true;
    }

    public function workOrderRequestAssignSent($workOrder, $workRequests)
    {
        $workOrder = WorkOrder::query()
            ->where('work_order_unique_id', $workOrder->work_order_unique_id)
            ->with([
                'user',
                'assignUser',
            ])
            ->first();

        $notificationText = $workOrder?->user?->username . ' Work Request Successfully Assign to you. Work order is ' . $workOrder->work_order_title . '( ' . $workOrder->work_order_unique_id . ' )';

        $notification = new Notification();
        $notification->sender_id = Auth::user()->id;
        $notification->receiver_id = $workOrder?->assignUser?->id;
        $notification->send_work_request_id = $workRequests->id;
        $notification->work_order_unique_id = $workOrder->work_order_unique_id;
        $notification->type = 'Work Request Assign';
        $notification->notification_text = $notificationText;
        $notification->is_read = false;
        $notification->save();

        // broadcast(new NotificationSentEvent($notification))->toOthers();
        $resource = new NotificationResource($notification);
        broadcast(new NotificationSentEvent($resource))->toOthers();

        return true;
    }

    public function expenseRequestSent($expenseRequest)
    {
        $workOrder = WorkOrder::query()
            ->where('work_order_unique_id', $expenseRequest->work_order_unique_id)
            ->with([
                'user',
            ])
            ->first();

        $notificationText = $workOrder?->user?->username . ' Work Order Expense Request Successfully sent. Work order is ' . $workOrder->work_order_title . '( ' . $workOrder->work_order_unique_id . ' )';

        $notification = new Notification();
        $notification->sender_id = Auth::user()->id;
        $notification->receiver_id = $workOrder?->user?->id;
        $notification->expense_request_id = $expenseRequest->id;
        $notification->work_order_unique_id = $workOrder->work_order_unique_id;
        $notification->type = 'Work Order Expense Request';
        $notification->notification_text = $notificationText;
        $notification->is_read = false;
        $notification->save();

        // broadcast(new NotificationSentEvent($notification))->toOthers();
        $resource = new NotificationResource($notification);
        broadcast(new NotificationSentEvent($resource))->toOthers();

        return true;
    }

    public function payChangeSent($payChange)
    {
        $workOrder = WorkOrder::query()
            ->where('work_order_unique_id', $payChange->work_order_unique_id)
            ->with([
                'user',
            ])
            ->first();

        $notificationText = $workOrder?->user?->username . ' Pay Change Successfully sent. Work order is ' . $workOrder->work_order_title . '( ' . $workOrder->work_order_unique_id . ' )';

        $notification = new Notification();
        $notification->sender_id = Auth::user()->id;
        $notification->receiver_id = $workOrder?->user?->id;
        $notification->pay_change_id = $payChange->id;
        $notification->work_order_unique_id = $workOrder->work_order_unique_id;
        $notification->type = 'Work Order Pay Change';
        $notification->notification_text = $notificationText;
        $notification->is_read = false;
        $notification->save();

        // broadcast(new NotificationSentEvent($notification))->toOthers();
        $resource = new NotificationResource($notification);
        broadcast(new NotificationSentEvent($resource))->toOthers();

        return true;
    }

    public function expenseRequestApproveSent($expenseRequest, $status)
    {
        $workOrder = WorkOrder::query()
            ->where('work_order_unique_id', $expenseRequest->work_order_unique_id)
            ->first();

        if ($status == 'Declined') {
            $notificationText = "Work Order id ('. $workOrder->work_order_unique_id . ') Expense Request Successfully Declined.";
        } else {
            $notificationText = "Work Order id ('. $workOrder->work_order_unique_id . ') Expense Request Successfully Approved.";
        }

        $notification = new Notification();
        $notification->sender_id = Auth::user()->id;
        $notification->receiver_id = $workOrder?->assignUser?->id;
        $notification->expense_request_id = $expenseRequest->id;
        $notification->work_order_unique_id = $workOrder->work_order_unique_id;
        $notification->type = 'Work Order Expense Request Approved';
        $notification->notification_text = $notificationText;
        $notification->is_read = false;
        $notification->save();

        // broadcast(new NotificationSentEvent($notification))->toOthers();
        $resource = new NotificationResource($notification);
        broadcast(new NotificationSentEvent($resource))->toOthers();

        return true;
    }

    public function payChangeApproveSent($payChanges, $status)
    {
        $workOrder = WorkOrder::query()
            ->where('work_order_unique_id', $payChanges->work_order_unique_id)
            ->with([
                'user',
                'assignUser',
            ])
            ->first();

        if ($status == 'Declined') {
            $notificationText = "Work Order id ('. $workOrder->work_order_unique_id . ') Pay Change Request Successfully Declined by " . $workOrder?->user?->username . ".";
        } else {
            $notificationText = "Work Order id ('. $workOrder->work_order_unique_id . ') Pay Change Request Successfully Approved.";
        }

        $notification = new Notification();
        $notification->sender_id = Auth::user()->id;
        $notification->receiver_id = $workOrder?->assignUser?->id;
        $notification->pay_change_id = $payChanges->id;
        $notification->work_order_unique_id = $workOrder->work_order_unique_id;
        $notification->type = 'Work Order Pay Change Approved';
        $notification->notification_text = $notificationText;
        $notification->is_read = false;
        $notification->save();

        // broadcast(new NotificationSentEvent($notification))->toOthers();
        $resource = new NotificationResource($notification);
        broadcast(new NotificationSentEvent($resource))->toOthers();

        return true;
    }

    public function providerNotifyWorkOrderCreate($workOrder)
    {
        $providers = User::query()
            ->where(fn($q) => $q->where('organization_role', 'Provider')->orWhere('organization_role', 'Provider Company'))
            ->get();

        foreach ($providers as $provider) {

            $notificationText = $workOrder?->user?->username . ' Create a new work order for your. Check what is new challenge for your. The Work order is ' . $workOrder->work_order_title . '( ' . $workOrder->work_order_unique_id . ' )';
            
            $notification = new Notification();
            $notification->sender_id = Auth::user()->id;
            $notification->receiver_id = $provider->id;
            $notification->work_order_unique_id = $workOrder->work_order_unique_id;
            $notification->type = 'Work Order Create Notification';
            $notification->notification_text = $notificationText;
            $notification->is_read = false;
            $notification->save();

            $resource = new NotificationResource($notification);
            broadcast(new NotificationSentEvent($resource))->toOthers();
        }

        return true;
    }

    public function markCompletedByProvider($work_order_unique_id)
    {
        $workOrder = WorkOrder::query()
            ->where('work_order_unique_id', $work_order_unique_id)
            ->first();

        $notificationText = 'Work order mark completed by' . $workOrder?->provider?->profile->first_nameq . ' ' . $workOrder?->provider?->profile->last_name;

        $notification = new Notification();
        $notification->sender_id = $workOrder->assigned_id;
        $notification->receiver_id = $workOrder->user_id;
        $notification->work_order_unique_id = $workOrder->work_order_unique_id;
        $notification->type = 'Work Order Make Completed By Provider';
        $notification->notification_text = $notificationText;
        $notification->is_read = false;
        $notification->save();

        // broadcast(new NotificationSentEvent($notification))->toOthers();
        $resource = new NotificationResource($notification);
        broadcast(new NotificationSentEvent($resource))->toOthers();

        return true;
    }
}
