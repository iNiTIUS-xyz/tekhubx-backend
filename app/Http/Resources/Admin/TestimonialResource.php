<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestimonialResource extends JsonResource
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
            'author_name' => $this->author_name,
            'designation' => $this->designation,
            'quote' => $this->quote,
            'review_star' => $this->review_star,
            'image' => $this->image,
            'status' => $this->status,
        ];
    }
}
