<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Resources\Json\JsonResource;

class DefaultClientListResource extends JsonResource
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
            'client_title' => $this->client_title,
            'client_manager_id' => $this->client_manager_id,
            'logo' => $this->logo,
            'website' => $this->website,
            'notes' => $this->notes,
            'company_name_logo' => $this->company_name_logo,
            'client_name_logo' => $this->client_name_logo,
            'policies' => $this->policies,
            'instructions' => $this->instructions,
            'address_line_one' => $this->address_line_one,
            'address_line_two' => $this->address_line_two,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zip_code,
            'country' => $this->country,
            'location_type' => $this->location_type,
            'projects' => $this->projects,
            'work_orders' => $this->work_orders,
            'status' => $this->status,
        ];
    }
}
