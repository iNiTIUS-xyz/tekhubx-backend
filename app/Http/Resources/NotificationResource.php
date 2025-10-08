<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        if ($this->type == "Counter Offer" || $this->type == "Work Order Request" || $this->type == "Work Order Expense Request" || $this->type == "Work Order Pay Change" || $this->type == "Work Order Make Completed By Provider") {
            $url = '/dashboard/client/control/work-order/' . $this->work_order_unique_id;
        } elseif ($this->type == "Counter Offer Assign" || $this->type == "Work Request Assign" || $this->type == "Work Order Expense Request Approved" || $this->type == "Work Order Pay Change Approved" || $this->type == "Work Order Create Notification") {
            $url = '/dashboard/provider/work-order/' . $this->work_order_unique_id;
        }
        //  else {
        //     $url = '/dashboard/provider';
        // }

        return [
            'id' => $this->id,
            'notification_text' => $this->notification_text,
            'is_read' => $this->is_read,
            'name' => $this->sender->profile->first_name . ' ' . $this->sender->profile->last_name,
            'profile_image' => $this->sender->profile->profile_image,
            'date' => $this->created_at->format('Y-m-d'),
            'type' => $this->type,
            'url' => $url
        ];
    }
}
