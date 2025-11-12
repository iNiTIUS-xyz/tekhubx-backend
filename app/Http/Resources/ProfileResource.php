<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Review;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = User::find($this->user_id);
        if ($user->organization_role == 'Provider Company' || $user->organization_role == 'Provider') {
            $rating = Review::where('tag', 'client')
                ->where('provider_id', $this->user_id)
                ->avg('rating');
        } else {

            $rating = Review::where('tag', 'provider')
                ->where('client_id', $this->user_id)
                ->avg('rating');
        }
        $jobs = WorkOrder::where('assigned_id', $this->id)->where('status', 'Done')->count();

        $last_active = User::find($this->user_id);
        $lastActiveTime = Carbon::parse($last_active->updated_at);
        $humanReadable = $lastActiveTime->diffForHumans();

        // Check if the authenticated user is viewing their own profile
        $isOwnProfile = $request->user() && $request->user()->id === $this->user_id;
        $lastActive = $isOwnProfile ? 'Active now' : $humanReadable;

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
            'country_id' => $this->country_id,
            'state_id' => $this->state_id,
            'city' => $this->city,
            'address_1' => $this->address_1,
            'address_2' => $this->address_2,
            'zip_code' => $this->zip_code,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'social_security_number' => $this->social_security_number,
            'why_chosen_us' => $this->why_chosen_us,
            'profile_image' => $this->profile_image,
            'rating' => $rating ?? 0,
            'jobs' => $jobs ?? 0,
            'last_active' => $lastActive,
            'talent_type' => 'tekhubx'
        ];
    }
}
