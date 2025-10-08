<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionAdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            'id' => $this->id,
            'transaction_unique_id' => $this->payment_unique_id,
            'credit' => $this->point_credit,
            'total_balance' => $this->point_balance,
            'transaction_type' => $this->transaction_type,
            'status' => $this->status,
            'description' => $this->description,
            'gateway' => $this->gateway,
            'client_email' => $this->client->email,
            'client_name' => ($profile->first_name ?? '') . ' ' . ($profile->last_name ?? ''),
            'client_phone' => $this->client->profile->phone ?? null,
            'client_address' => $this->client->profile->address_1 ?? null,
            'client_country' => $this->client->profile->country->name ?? null,
            'client_state' => $this->client->profile->state->name ?? null,
            'client_city' => $this->client->profile->city ?? null,
            'client_image' => $this->client->profile->profile_image ?? null,
        ];
    }
}
