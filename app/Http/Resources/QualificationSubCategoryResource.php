<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QualificationSubCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
         // Grouping by qualification name
         $grouped = $this->collection->groupBy(function($item) {
            return $item->qualification->name;
        });

        return $grouped->map(function ($group) {
            return [
                'id' => $group->first()->qualification->id,
                'name' => $group->first()->qualification->name,
                'qualification_type_id' => $group->first()->qualification_type_id,
                'qualification_sub_cats' => $group->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                    ];
                })->values()->all(),
            ];
        })->values()->all();
    }
}
