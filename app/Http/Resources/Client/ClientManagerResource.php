<?php

namespace App\Http\Resources\Client;

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
            'name' => $this->name,
            'image' => $this->image,
            'email' => $this->email,
            'phone' => $this->phone,
            'note' => $this->note,
            'role' => $this->role,
            'status' => $this->status,
        ];
    }
}
