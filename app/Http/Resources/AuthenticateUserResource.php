<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthenticateUserResource extends JsonResource
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
            'organization_role' => $this->organization_role,
            'name' => $this->username,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status,
        ];
    }
}
