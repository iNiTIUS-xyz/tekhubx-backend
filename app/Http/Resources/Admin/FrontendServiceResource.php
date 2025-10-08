<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FrontendServiceResource extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'frontend_service_category_id' => $this->frontend_service_category_id,
            'short_description' => $this->short_description,
            'banner_image' => $this->banner_image,
            'description' => $this->description,
            'status' => $this->status,
        ];
    }
}
