<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Review;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use App\Models\SendWorkRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class UserCompleteDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $last_active = User::find($this->id);
        $lastActiveTime = Carbon::parse($last_active->updated_at);
        $humanReadable = $lastActiveTime->diffForHumans();

        $rating = Review::where('tag', 'client')
            ->where('provider_id', $this->id)
            ->avg('rating');

        $work_order_completed = WorkOrder::where('assigned_id', $this->id)->where('provider_status', 'Completed')->count();
        $work_order_assigned = WorkOrder::where('assigned_id', $this->id)->where('provider_status', 'Assigned')->count();

        $clientCount = WorkOrder::where('assigned_id', $this->id)
            ->where('provider_status', 'Completed')
            ->distinct('uuid')
            ->count('uuid');

        // $send_work_request = SendWorkRequest::where('user_id', $this->id)->where('status', 'Active')->count() ?? 0;
        // $counter_offer = CounterOffer::where('user_id', $this->id)->where('status', 'Active')->count() ?? 0;
        $user_id = $employed_provider->user_id ?? null;

        if ($user_id) {
            $send_work_request = SendWorkRequest::where('user_id', $user_id)
                ->where('status', 'Active')
                ->count();

            // $counter_offer = CounterOffer::where('user_id', $user_id)
            //     ->where('status', 'Active')
            //     ->count();

            // $total_request = $send_work_request + $counter_offer;
            $total_request = $send_work_request;
        } else {
            $total_request = 0;
        }
        return  [
            'id' => $this->id,
            'provider' => new UserResource($this->providerUser),
            'first_name' => $this->profile->first_name,
            'last_name' => $this->profile->last_name,
            'email' => $this->email,
            'phone' => $this->profile->phone,
            'address' => $this->profile->address_1 . ', ' .
                $this->profile->city . ', ' .
                ($this->profile->state ? $this->profile->state->name : 'State not available') . ', ' .
                $this->profile->zip_code . ', ' .
                ($this->profile->country ? $this->profile->country->name : 'Country not available'),

            // 'address' => $this->profile->address_1 . ', ' . $this->profile->city . ', ' . $this->profile->state->name ?? '' . ', ' . $this->profile->zip_code . ', ' . $this->profile->country->name ?? '',
            'bio' => $this->bio,
            'last_active' => $humanReadable,
            'rating' => $rating,
            'completed_work_order' => $work_order_completed ?? 0,
            'assigned_work_order' => $work_order_assigned ?? 0,
            'clients' => $clientCount ?? 0,
            'total_request' => $total_request,
            'status' => $this->status,
            'about' => $this->about ?? null,
            // 'workSummery' => $this->workSummery ?? null,
            'workSummery' => $this->workSummery->map(function ($summary) {
                return [
                    'id' => $summary->id,
                    'work_category' => $summary->subCategory?->name, // Prefer subCategory name if available
                ];
            }),

            'skillSet' => $this->skillSet ?? null,
            'equipment' => $this->equipment ?? null,
            'employmentHistory' => $this->employmentHistory ?? null,
            'education' => $this->education ?? null,
            'licenseCertificate' => $this->licenseAndCertificates->map(function ($license) {
                return [
                    'id' => $license->id,
                    'license_number' => $license->license_number,
                    'certificate_number' => $license->certificate_number,
                    'state_name' => $license->state_name,
                    'issue_date' => $license->issue_date,
                    'expired_date' => $license->expired_date,
                    'file' => $license->file,
                    'status' => $license->status,
                    'certificate' => $license->certificate, // This uses the certificate relationship
                    'license' => $license->license, // This uses the certificate relationship
                ];
            }),
        ];
    }
}
