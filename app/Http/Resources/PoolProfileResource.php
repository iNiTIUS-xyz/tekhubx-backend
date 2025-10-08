<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use App\Models\Review;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PoolProfileResource extends JsonResource
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
            'uuid' => $this->uuid,
            'organization_role' => $this->organization_role,
            'username' => $this->username,
            'email' => $this->email,
            'profile' => $this->whenLoaded('profiles', function () {
                return new ProfileResource($this->profiles->first());
            }),
            // 'profiles' => ProfileResource::collection($this->whenLoaded('profiles')),
        ];
    }
}
