<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
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
            'name' => $this->name,
            'image' => $this->image,
            'designation' => $this->designation,
            'gender' => $this->gender,
            'portfolio_url' => $this->portfolio_url,
            'linkedin_url' => $this->linkedin_url,
            'facebook_url' => $this->facebook_url,
            'x_url' => $this->x_url,
            'status' => $this->status,
        ];
    }
}
