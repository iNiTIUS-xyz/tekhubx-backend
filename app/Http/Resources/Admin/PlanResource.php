<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
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
            'name' => $this->name,
            'details' => $this->details,
            'amount' => $this->amount,
            'point' => $this->point,
            'work_order_fee' => $this->work_order_fee,
            'expired_month' => $this->expired_month,
            'status' => $this->status,
        ];
    }
}
