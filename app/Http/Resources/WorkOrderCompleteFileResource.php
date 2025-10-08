<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderCompleteFileResource extends JsonResource
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
            'work_order_unique_id' => $this->work_order_unique_id,
            'file' => $this->file ? Storage::url($this->file) : null,
            'description' => $this->description,
            'provider_id' => $this->profile,
        ];
    }
}
