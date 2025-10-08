<?php

namespace App\Http\Resources\Provider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseRequestResource extends JsonResource
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
            "amount" => $this->amount,
            "description" => $this->description,
            "file" => $this->file,
            "status" => $this->status,
            "user" => $this->user,
            "expenseCategory" => $this->expenseCategory,
            "workOrder" => $this->workOrder,
        ];
    }
}
