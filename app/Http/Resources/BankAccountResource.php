<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankAccountResource extends JsonResource
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
            'country' => $this->country,
            'currency' => $this->currency,
            'account_holder_name' => $this->account_holder_name,
            'account_holder_type' => $this->account_holder_type,
            'routing_number' => $this->routing_number,
            'account_number' => $this->account_number,
            // 'status' => $this->status,
        ];
    }
}
