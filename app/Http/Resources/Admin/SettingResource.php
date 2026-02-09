<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
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
            'website_name' => $this->website_name,
            'website_logo' => $this->website_logo,
            'website_favicon' => $this->website_favicon,
            'phone_numbers' => json_decode($this->phone_numbers),
            'email_addresses' => json_decode($this->email_addresses),
            'address' => $this->address,
        ];
    }
}
