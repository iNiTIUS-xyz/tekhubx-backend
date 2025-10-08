<?php

namespace App\Http\Resources\Provider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CounterOfferPayResource extends JsonResource
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
            'type' => $this->type,
            'max_hour' => $this->max_hour,
            'amount_per_hour' => $this->amount_per_hour,
            'total_pay_amount' => $this->total_pay_amount,
            'amount_per_device' => $this->amount_per_device,
            'max_device' => $this->max_device,
            'fixed_amount' => $this->fixed_amount,
            'fixed_amount_max_hours' => $this->fixed_amount_max_hours,
            'hourly_amount_after' => $this->hourly_amount_after,
            'hourly_amount_max_hours' => $this->hourly_amount_max_hours,
        ];
    }
}
