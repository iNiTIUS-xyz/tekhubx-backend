<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
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
            'display_name' => $this->display_name,
            'default_client_id' => $this->default_client_id,
            'default_client_title' => $this->defaultClient->client_title ?? null,
            'location_group_id' => $this->location_group_id,
            'country_id' => $this->country_id,
            'country_name' => $this->country->name,
            'state_id' => $this->state_id,
            'state_name' => $this->stateName->name ?? null,
            'zip_code' => $this->zip_code,
            'location_type' => $this->location_type,
            'address_1' => $this->address_line_1,
            'address_2' => $this->address_line_2,
            'city' => $this->city,
            'name_description' => $this->name_description,
            'phone' => $this->phone,
            'phone_ext' => $this->phone_ext,
            'email' => $this->email,
            'note' => $this->note,
            'latitude' => $this->latitude ?? null,
            'longitude' => $this->longitude ?? null,
        ];
    }
}
