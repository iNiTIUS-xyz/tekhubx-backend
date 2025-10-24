<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Review;
use App\Models\WorkOrder;
use App\Models\PoolDetails;
use Illuminate\Http\Request;
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

        $jobs = WorkOrder::where('assigned_id', $this->provider_id)
            ->where('status', 'Complete')
            ->count();

        $pool = PoolDetails::where('provider_id', $this->provider_id)->first();
        $user = User::find($this->provider_id);

        $lastActiveTime = $user ? Carbon::parse($user->updated_at)->diffForHumans() : 'N/A';

        $profile = $this->profile;
        $firstName = $profile->first_name ?? '';
        $lastName = $profile->last_name ?? '';
        $address1 = $profile->address_1 ?? '';
        $city = $profile->city ?? '';

        return [
            'id' => $this->id,
            'provider_id' => $this->provider_id,
            'status' => $this->status,
            'profile_image' => $profile->profile_image ?? '',
            'name' => trim("$firstName $lastName"),
            'address' => trim("$address1, $city", ', '),
            'rating' => $rating ? round($rating, 2) : 0,
            'jobs' => $jobs ?? 0,
            'last_active' => $lastActiveTime,
            'talent_type' => $pool->talentData->pool_name ?? 'tekhubx',
            'talent_info' => $this->talentData,
        ];
    }
}
