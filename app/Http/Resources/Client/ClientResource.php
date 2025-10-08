<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "client_title" => $this->client_title,
            "client_manager_id" => $this->client_manager_id,
            "website" => $this->website,
            "logo" => $this->logo,
            "notes" => $this->notes,
            "default_policies" => $this->default_policies,
            "default_standard_instruction" => $this->default_standard_instruction,
            "address_line_1" => $this->address_line_1,
            "address_line_2" => $this->address_line_2,
            "city" => $this->city,
            "state_id" => $this->state_id,
            "zip_code" => $this->zip_code,
            "country_id" => $this->country_id,
            "location_type" => $this->location_type,
            "company_name_with_logo" => $this->company_name_with_logo,
            "client_name_with_logo" => $this->client_name_with_logo,
        ];
    }
}
