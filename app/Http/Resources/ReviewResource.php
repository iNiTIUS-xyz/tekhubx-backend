<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
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
            // 'uuid' => $this->uuid,
            'provider_id' => $this->provider->profile->first_name . ' ' . $this->provider->profile->last_name,
            // 'client_id' => $this->client_id,
            'rating' => $this->rating,
            'review_text' => $this->review_text,
            // 'tag' => $this->tag,
            // 'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
