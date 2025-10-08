<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
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
            'page_title' => $this->page_title,
            'page_slug' => $this->page_slug,
            'banner_image' => $this->banner_image,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'status' => $this->status,
        ];
    }
}
