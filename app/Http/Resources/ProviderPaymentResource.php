<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderPaymentResource extends JsonResource
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
            'client_id' => $this->client_id,
            'provider_id' => $this->provider_id,
            'payment_unique_id' => $this->payment_unique_id,
            'work_order_unique_id' => $this->work_order_unique_id,
            'transaction_table_id' => $this->transaction_table_id,
            'total_labor' => $this->total_labor,
            'service_fees' => $this->services_fee,
            'status' => $this->status,
            'payment_date_time' => $this->payment_date_time,
            'description' => $this->description,
            'provider' => $this->whenLoaded('providerUuid', function () {
                return new ProfileResource($this->providerUuid->profile);
            }),
            'work_order' => new WorkOrderResource($this->whenLoaded('workOrder')),
        ];
    }
}
