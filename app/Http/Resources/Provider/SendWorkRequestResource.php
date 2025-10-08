<?php

namespace App\Http\Resources\Provider;

use Illuminate\Http\Request;
use App\Models\EmployeeProvider;
use Illuminate\Http\Resources\Json\JsonResource;

class SendWorkRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $employed = EmployeeProvider::where('user_id', $this->user_id)->first();
        return [
            'id' => $this->id,
            'work_order_unique_id' => $this->work_order_unique_id,
            'uuid' => $this->uuid,
            'employed_provider_id' => $employed,
            'request_date_time' => $this->request_date_time,
            'after_withdraw' => $this->after_withdraw,
            'expired_request_time' => $this->expired_request_time,
            'status' => $this->status,
            'accept_status' => $this->work_order->status ?? null,
        ];
    }
}
