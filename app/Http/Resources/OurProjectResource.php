<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OurProjectResource extends JsonResource
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
            'author' => $this->admin->first_name . ' ' . $this->admin->last_name,
            'title' => $this->title,
            'slug' => $this->slug,
            'image' => $this->image,
            'description' => $this->description,
            'tags' =>  collect(json_decode($this->tags, true)) // Decode JSON to array
            ->flatMap(fn($item) => collect(explode(',', $item))->map(fn($tag) => trim($tag)))
            ->toArray(),
            'status' => $this->status,
            'created_at' => $this->created_at,
            'total_view' => $this->total_view,
        ];
    }
}
