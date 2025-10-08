<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Review;
use App\Models\WorkOrder;
use App\Models\PoolDetails;
use Illuminate\Http\Request;
use App\Http\Resources\ProviderResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PoolDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $rating = Review::where('tag', 'client')
                ->where('provider_id', $this->provider_id)
                ->avg('rating');
        $jobs = WorkOrder::where('assigned_id', $this->provider_id)->where('status', 'Complete')->count();
        $pool_name = PoolDetails::where('provider_id', $this->provider_id)->first();

        $last_active = User::find($this->provider_id);
        $lastActiveTime = Carbon::parse($last_active->updated_at);
        $humanReadable = $lastActiveTime->diffForHumans();


        return [
            'id' => $this->id,
            'provider_id' => $this->provider_id,
            'status' => $this->status,
            'profile_image' => $this->profile->profile_image,
            'name' => $this->profile->first_name . ' ' . $this->profile->last_name,
            'address' => $this->profile->address_1 . ',' . $this->profile->city,
            'rating' => $rating ?? 0,
            'jobs' => $jobs ?? 0,
            'last_active' => $humanReadable,
            'talent_type' => $pool_name->talentData->pool_name ?? 'tekhubx',
            'talent_info' => $this->talentData
        ];
    }
}
