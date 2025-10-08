<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Client\WorkSubCategoryResource;

class WorkServiceResource extends JsonResource
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
            'status' => $this->status,
            'work_sub_category_id' => $this->work_sub_category_id,
            'work_sub_category_name' => $this->workSubCategory->name,
            // 'work_sub_category_data' => new WorkSubCategoryResource($this->workSubCategory)
        ];
    }
}
