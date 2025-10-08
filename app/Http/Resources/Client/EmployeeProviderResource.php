<?php

namespace App\Http\Resources\Client;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Review;
use App\Models\WorkOrder;
use App\Models\CounterOffer;
use Illuminate\Http\Request;
use App\Models\SendWorkRequest;
use App\Models\EmployeeProvider;
use App\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeProviderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $employed_provider = EmployeeProvider::find($this->id);
        $last_active = User::find($employed_provider->user_id);
        $lastActiveTime = Carbon::parse($last_active->updated_at);
        $humanReadable = $lastActiveTime->diffForHumans();

        $work_order_completed = WorkOrder::where('assigned_id', $employed_provider->user_id)->where('provider_status', 'Completed')->count();
        $work_order_assigned = WorkOrder::where('assigned_id', $employed_provider->user_id)->where('provider_status', 'Assigned')->count();

        $clientCount = WorkOrder::where('assigned_id', $employed_provider->user_id)
            ->where('provider_status', 'Completed')
            ->distinct('uuid')
            ->count('uuid');
        // $send_work_request = SendWorkRequest::where('user_id', $employed_provider->user_id)->where('status', 'Active')->count() ?? 0;
        // $counter_offer = CounterOffer::where('user_id', $employed_provider->user_id)->where('status', 'Active')->count() ?? 0;

        $rating = Review::where('tag', 'client')
            ->where('provider_id', $employed_provider->user_id)
            ->avg('rating');

        $user_id = $employed_provider->user_id ?? null;

        if ($user_id) {
            $send_work_request = SendWorkRequest::where('user_id', $user_id)
                ->where('status', 'Active')
                ->count();

            $counter_offer = CounterOffer::where('user_id', $user_id)
                ->where('status', 'Active')
                ->count();

            $total_request = $send_work_request + $counter_offer;
        } else {
            $total_request = 0;
        }
        return  [
            'id' => $this->id,
            'provider' => new UserResource($this->providerUser),
            'profile_id' => $this->profile->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'profile_image' => $this->profile->profile_image,
            'last_active' => $humanReadable,
            'completed_work_order' => $work_order_completed ?? 0,
            'assigned_work_order' => $work_order_assigned ?? 0,
            'clients' => $clientCount ?? 0,
            'total_request' => $total_request ?? 0,
            'rating' => $rating ?? 0,
            'email' => $this->email,
            'phone' => $this->phone,
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'city' => $this->city,
            'zip_code' => $this->zip_code,
            'state' => $this->state,
            'country' => $this->country,
            'work_category' => $this->workCategory,
            'bio' => $this->bio,
            'status' => $this->status,
            'about' => $this->about ?? '',
            'workSummery' => $this->workSummery->map(function ($summary) {
                return [
                    'id' => $summary->id,
                    'work_category' => $summary->subCategory?->name, // Prefer subCategory name if available
                ];
            }),
            
            'skillSet' => $this->skillSet->map(function ($skillSet) {
                return [
                    'id' => $skillSet->id,
                    'name' => $skillSet->name,
                ];
            }),
            'equipment' => $this->equipment->map(function ($equipment) {
                return [
                    'id' => $equipment->id,
                    'name' => $equipment->name,
                ];
            }),
            // 'skillSet' => $this->skillSet ?? null,
            'employmentHistory' => $this->employmentHistory ?? [],
            'education' => $this->education ?? [],
            'licenseCertificate' => $this->licenseCertificate->map(function ($license) {
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
