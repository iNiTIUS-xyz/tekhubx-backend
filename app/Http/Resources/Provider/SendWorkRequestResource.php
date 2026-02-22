<?php

namespace App\Http\Resources\Provider;

use Illuminate\Http\Request;
use App\Models\EmployeeProvider;
use App\Models\Profile;
use Illuminate\Http\Resources\Json\JsonResource;

class SendWorkRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $employed = EmployeeProvider::where('user_id', $this->user_id)->first();
        $user = $this->relationLoaded('user') ? $this->user : null;
        $profile = null;
        if ($user?->id) {
            $profile = Profile::where('user_id', $user->id)->first();
        }

        $firstName = $employed->first_name ?? $profile->first_name ?? null;
        $lastName = $employed->last_name ?? $profile->last_name ?? null;
        $email = $employed->email ?? $user?->email ?? null;
        $phone = $employed->phone ?? $profile?->phone ?? null;

        return [
            'id' => $this->id,
            'work_order_unique_id' => $this->work_order_unique_id,
            'uuid' => $this->uuid,
            'employed_provider_id' => [
                'first_name' => $firstName ?? ($user?->username ?? 'N/A'),
                'last_name' => $lastName ?? '',
                'email' => $email,
                'phone' => $phone,
            ],
            'provider' => [
                'id' => $user?->id,
                'email' => $user?->email,
                'profile' => [
                    'first_name' => $profile?->first_name ?? $firstName ?? ($user?->username ?? 'N/A'),
                    'last_name' => $profile?->last_name ?? $lastName ?? '',
                    'phone' => $profile?->phone ?? $phone,
                ],
            ],
            'request_date_time' => $this->request_date_time,
            'after_withdraw' => $this->after_withdraw,
            'expired_request_time' => $this->expired_request_time,
            'status' => $this->status,
            'accept_status' => $this->work_order->status ?? null,
        ];
    }
}
