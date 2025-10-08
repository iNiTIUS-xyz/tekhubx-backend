<?php

namespace App\Http\Resources;

use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HistoryLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $workOrder = WorkOrder::where('work_order_unique_id', $this->work_order_unique_id)->first();
        return [
            'id' => $this->id,
            'by_user' => trim(($this->client->profile->first_name ?? '') . ' ' . ($this->client->profile->last_name ?? '')),
            'event' => $this->description,
            'date' => $this->date_time,
        ];
    }
}
