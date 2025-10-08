<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Models\QualificationType;
use Illuminate\Support\Facades\Log;
use App\Models\QualificationSubCategory;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckingResource extends JsonResource
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
            'work_order_unique_id' => $this->work_order_unique_id,
            'qualification_type' => $this->transformQualificationType(),
            
        ];
    }

    protected function transformQualificationType()
    {
        $innerDecoded = json_decode($this->qualification_type, true);

        if (is_array($innerDecoded)) {


            if (!is_array($innerDecoded)) {
                return [];
            }

            return array_map(function ($type) {
                if (!is_array($type) || !isset($type['id']) || !isset($type['sub_categories'])) {
                    return [
                        'id' => null,
                        'name' => null,
                        'sub_categories' => [],
                    ];
                }

                $typeId = $type['id'];
                $subCategories = $type['sub_categories'];

                // Fetch the QualificationType and its subcategories
                $qualificationType = QualificationType::find($typeId);

                return [
                    'id' => $typeId,
                    'name' => $qualificationType ? $qualificationType->name : null,
                    'sub_categories' => QualificationSubCategory::whereIn('id', $subCategories)->get()->map(function ($subCategory) {
                        return [
                            'id' => $subCategory->id,
                            'name' => $subCategory->name,
                        ];
                    }),
                ];
            }, $innerDecoded);
        }

        return [];
    }

}
