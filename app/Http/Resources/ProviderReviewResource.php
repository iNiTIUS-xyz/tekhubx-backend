<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            'id' => $this->id,
            'provider' => $this->provider->profile->first_name . ' ' . $this->provider->profile->last_name,
            'rating' => $this->rating,
            'review_text' => $this->review_text,
        ];
    }
}
