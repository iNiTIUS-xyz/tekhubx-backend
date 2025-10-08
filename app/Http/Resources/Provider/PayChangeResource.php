<?php

namespace App\Http\Resources\Provider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayChangeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "reason" => $this->reason,
            "hour" => $this->extra_hour,
            "status" => $this->status,
            "user" => $this->user,
            "work_order" => $this->workOrder,
        ];
    }
}
