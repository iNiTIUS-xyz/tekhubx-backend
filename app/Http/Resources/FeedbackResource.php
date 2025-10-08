<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedbackResource extends JsonResource
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
            'client' => $this->client ?? null, 
            'client_profile' => $this->client->profile ?? null, 
            'client_name' => $this->client->profile->first_name . ' ' . $this->client->profile->last_name,
            'provider' => $this->provider ?? null,
            'provider_profile' => $this->provider->profile ?? null,
            'reason' => $this->reason,
            'comments' => $this->comments,
        ];
    }
}
