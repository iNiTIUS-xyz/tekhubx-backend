<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientManagerResource extends JsonResource
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
            'user_id' => $this->user_id,
            'client_id' => $this->client_id,
            'name' => $this->name,
            'user_name' => $this->user_name,
            'email' => $this->email,
            'country' => $this->country->name,
            'country_id' => $this->country_id,
            'state_id' => $this->state,
            'state' => $this->state_name->name ?? null,
            'zip_code' => $this->zip_code,
            'status' => $this->status,
            'role' => $this->role,
            'address_one' => $this->address_one,
            'address_two' => $this->address_two,
        ];
    }
}
