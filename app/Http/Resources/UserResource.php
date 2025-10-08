<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'uuid' => $this->uuid,
            'organization_role' => $this->organization_role,
            'username' => $this->username,
            'email' => $this->email,
            'profiles' => ProfileResource::collection($this->whenLoaded('profiles')),
            'companies' => CompanyResource::collection($this->whenLoaded('companies')),
        ];
    }
}
