<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Shipment;
use App\Models\PayChange;
use App\Models\ExpenseRequest;
use App\Models\WorkOrderReport;
use App\Models\ProviderCheckout;
use App\Models\AdditionalContact;
use App\Models\QualificationType;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\ProjectResource;
use App\Models\QualificationSubCategory;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class WorkOrderResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    public function toArray($request)
    {

        // Decode the JSON fields
        $shipmentIds = json_decode($this->shipment_id, true) ?: [];
        $additionalContactIds = json_decode($this->additional_contact_id, true) ?: [];

        $tasksData = json_decode($this->tasks, true) ?: [];

        // $servicesFeeData = $this->payment->services_fee ? json_decode($this->payment->services_fee, true) : null;

        $servicesFeeData = $this->payment && $this->payment->services_fee
            ? json_decode($this->payment->services_fee, true)
            : null;

        $labor = $this->labor;
        $totalFees = null;

        if ($labor && $servicesFeeData) {

            foreach ($servicesFeeData as $fee) {
                // Ensure that both 'name' and 'percentage' exist in each fee entry
                if (isset($fee['name']) && isset($fee['percentage'])) {
                    // Convert the percentage to decimal and calculate the fee
                    $feePercentage = $fee['percentage'] / 100;
                    $individualFee = $labor * $feePercentage;

                    // Add this fee to the total fees
                    $totalFees += $individualFee;
                }
            }
        }

        $providerCheck = ProviderCheckout::where('work_order_unique_id', $this->work_order_unique_id)->first();

        if ($providerCheck) {
            $checkout_start_time = 'On Track';
            if ($providerCheck->at_risk === 'yes') {
                $confirmed = 'At Risk';
            } else {
                $confirmed = null;
            }

            $on_my_way = $providerCheck->on_my_way === 'yes' ? 'on my way' : null;

            if ($providerCheck->is_check_in === 'yes') {
                $status = $providerCheck->status ?? null;
                $check_in = "On Site";
            } else {
                $status = null;
            }
        } else {
            $confirmed = null;
            $on_my_way = null;
            $status = null;
            $check_in = null;
        }

        $workOrderReport = WorkOrderReport::where('provider_id', optional(Auth::user())->id)
            ->where('work_order_unique_id', $this->work_order_unique_id)->first();

        $user_details = User::where('uuid', $this->uuid)->where('role', "Super Admin")->first();

        $paychange = PayChange::where('work_order_unique_id', $this->work_order_unique_id)->where('status', 'Accept')->first();
        $expenseRequest = ExpenseRequest::where('work_order_unique_id', $this->work_order_unique_id)->where('status', 'Accept')->first();

        $toDate = null;

        switch ($this->schedule_type) {
            case 'Arrive at a specific date and time - (Hard Start)':
                $toDate = $this->schedule_date;
                break;

            case 'Complete work between specific hours':
                $toDate = $this->schedule_date_between_1;
                break;

            case 'Complete work anytime over a date range':
                $toDate = $this->between_date;
                break;

            default:
                $toDate = null;
                break;
        }

        // Convert $toDate to Carbon instance
        $toDate = $toDate ? Carbon::parse($toDate) : null;
        //     Draft, Published, Assigned,Done,Approved, In-Flight,All
        if ($this->status === 'Draft') {
            $tab_status = 'Draft';
        } elseif ($this->status === 'Published') {
            $tab_status = 'Published';
        } elseif ($this->status === 'Assigned') {
            $tab_status = 'Assigned';
        } elseif ($this->provider_status === 'Completed') {
            $tab_status = 'Done';
        } elseif ($this->assigned_status === 'Complete') {
            $tab_status = 'Approved';
        } elseif ($toDate && $toDate->equalTo(Carbon::today())) {
            $tab_status = 'In-Flight';
        } else {
            $tab_status = 'All';
            // $tab_status = ['Draft', 'Published', 'Assigned', 'Done', 'Approved', 'In-Flight', 'All'];
        }

        $files = $this->documents_file;

        // If it's a JSON string, decode it
        if (is_string($files)) {
            $files = json_decode($files, true);
        }

        // Now map through properly
        $documents = collect($files)
            ->filter() // remove null/empty
            ->map(function ($file) {
                return asset('storage/' . ltrim($file, '/'));
            })
            ->values()
            ->toArray();

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'user_id' => $this->user_id,
            'template' => $this->template,
            'company' => optional(optional($user_details)->company)->company_name,
            'project_title' => optional($this->project)->title,
            'client_title' => optional($this->default_client)->client_title,
            'project' => $this->project,
            'work_order_unique_id' => $this->work_order_unique_id,
            'work_order_title' => $this->work_order_title,
            'default_client' => $this->default_client,
            'project' => new ProjectResource($this->whenLoaded('project')),
            'export_bool' => $this->export_bool,
            'counter_offer_bool' => $this->counter_offer_bool,
            'gps_bool' => $this->gps_bool,
            'service_description_public' => $this->service_description_public,
            'service_description_note_private' => $this->service_description_note_private,
            'work_category' => $this->work_category,
            'additional_work_category' => $this->additional_work_category,
            'service_type_details' => $this->service_type ?? null,
            'service_type' => optional($this->service_type)->name,
            'location' => $this->additional_location,
            'schedule_type' => $this->schedule_type,
            'schedule_date' => $this->schedule_date,
            'schedule_time' => $this->schedule_time,
            'time_zone' => $this->time_zone,
            'schedule_date_between_1' => $this->schedule_date_between_1,
            'schedule_date_between_2' => $this->schedule_date_between_2,
            'schedule_time_between_1' => $this->schedule_time_between_1,
            'schedule_time_between_2' => $this->schedule_time_between_2,
            'between_date' => $this->between_date,
            'between_time' => $this->between_time,
            'through_date' => $this->through_date,
            'through_time' => $this->through_time,
            // 'documents_file' => $this->documents_file,
            'documents_file' => $documents,
            'buyer_custom_field' => $this->buyer_custom_field,
            'manager' => $this->manager,
            'shipments' => Shipment::whereIn('id', is_array($shipmentIds) ? $shipmentIds : [])->get(),
            'additional_contacts' => AdditionalContact::whereIn('id', is_array($additionalContactIds) ? $additionalContactIds : [])->get(),
            'qualification_type' => $this->transformQualificationType(),
            'tasks' => $tasksData,
            'status' => $this->status,
            'pay_type' => $this->pay_type,
            'hourly_rate' => $this->hourly_rate,
            'max_hours' => $this->max_hours,
            'approximate_hour_complete' => $this->approximate_hour_complete,
            'total_pay' => $this->total_pay,
            'per_device_rate' => $this->per_device_rate,
            'max_device' => $this->max_device,
            'fixed_payment' => $this->fixed_payment,
            'fixed_hours' => $this->fixed_hours,
            'additional_hourly_rate' => $this->additional_hourly_rate,
            'max_additional_hour' => $this->max_additional_hour,
            'bank_account_id' => $this->bank_account_id,
            'assigned_status' => $this->assigned_status,
            'provider_status' => $this->provider_status,
            'rule_id' => $this->rule_id,
            'labor' => $this->labor,
            'service_fee' => $servicesFeeData ?? null,
            'total_service_fees' => $totalFees,
            'state_tax' => $this->state_tax,
            'provider_start_time' => $checkout_start_time ?? null,
            'provider_confirmed' => $confirmed,
            'provider_on_my_way' => $on_my_way,
            'provider_checkin_status' => $status,
            'provider_checkin' => $check_in ?? null,
            'has_report' => $workOrderReport ? true : false,
            'pay_change' => $paychange ?? null,
            'expense_request' => $expenseRequest ?? null,
            'tab_status' => $tab_status,
        ];
    }

    protected function transformQualificationType()
    {
        // Decode the outer JSON string
        $outerDecoded = json_decode($this->qualification_type, true);

        // Ensure $outerDecoded is an array and contains items
        if (is_array($outerDecoded)) {
            // Process each type object in the array
            return array_map(function ($type) {
                // Ensure $type is an array and contains 'id' and 'sub_categories'
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
            }, $outerDecoded);
        }

        // Handle case where outer result is not as expected
        return [];
    }
}
