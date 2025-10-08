<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactUsResource extends JsonResource
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
            'name' => $this->first_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'message' => $this->message,
            // 'phone' => $this->phone,
            // 'annual_revenue' => $this->annual_revenue,
            // 'need_technicians' => $this->need_technicians,
            // 'why_chose_us' => $this->why_chose_us,
        ];
    }
}
