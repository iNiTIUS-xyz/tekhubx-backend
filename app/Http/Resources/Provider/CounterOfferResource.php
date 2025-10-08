<?php

namespace App\Http\Resources\Provider;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CounterOfferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'uuid_user' => $this->uuidUser ? new UserResource($this->uuidUser) : null,
            'work_order_unique_id' => $this->work_order_unique_id,
            'provider_id' => $this->provider_id,
            'employed_providers_id' => $this->employed_providers_id,
            'pay' => $this->counterOfferPay ? new CounterOfferPayResource($this->counterOfferPay) : null,
            'schedule' => $this->counterOfferSchedule ? new CounterOfferScheduleResource($this->counterOfferSchedule) : null,
            'expense_id' => $this->expense_id,
            'reason' => $this->reason,
            'withdraw' => $this->withdraw,
            'accept_status' => $this->work_order->status ?? null,
        ];
    }
}
