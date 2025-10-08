<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionAdminResource extends JsonResource
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
            'transaction_unique_id' => $this->payment_unique_id,
            'client_id' => $this->client_id,
            'provider_id' => $this->provider_id,
            'work_order_unique_id' => $this->work_order_unique_id,
            'service_fee' => $this->service_fee,
            'debit' => $this->debit,
            'credit' => $this->credit,
            'balance' => $this->balance,
            'point_debit' => $this->point_debit,
            'point_credit' => $this->point_credit,
            'point_balance' => $this->point_balance,
            'transaction_type' => $this->transaction_type,
            'status' => $this->status,
            'description' => $this->description,
            'gateway' => $this->gateway,
            'work_order' => new WorkOrderResource($this->whenLoaded('workOrder')),
            'provider' => $this->whenLoaded('provider', function () {
                return new ProfileResource($this->provider->profile);
            }),
            'client' => $this->whenLoaded('client', function () {
                return new ProfileResource($this->client->profile);
            }),
        ];
    }
}
