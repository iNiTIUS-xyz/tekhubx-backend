<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\AdditionalContact;
use App\Models\QualificationSubCategory;
use App\Models\QualificationType;

class TemplateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $additionalContactIds = $this->additional_contact_id ? json_decode($this->additional_contact_id, true) : [];

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'template_name' => $this->template_name,
            'default_client_id' => $this->default_client_id,
            'project_id' => $this->project_id,
            'work_order_title' => $this->work_order_title,
            'export_button' => $this->export_button,
            'counter_offer' => $this->counter_offer,
            'gps_on' => $this->gps_on,
            'public_description' => $this->public_description,
            'private_description' => $this->private_description,
            'work_category_id' => $this->work_category_id,
            'additional_work_category_id' => $this->additional_work_category_id,
            'service_type_id' => $this->service_type_id,
            'qualification_type' => $this->transformQualificationType(),
            'work_order_manager_id' => $this->work_order_manager_id,
            'additional_contacts' => AdditionalContact::whereIn('id', $additionalContactIds)->get(),
            'tasks' => json_decode($this->task),
            'buyer_custom_field' => $this->buyer_custom_field,
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
            'rule_id' => $this->rule_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'additional_contact' => $this->whenLoaded('additional_contact'),
        ];
    }



    protected function transformQualificationType()
    {
        $outerDecoded = json_decode($this->qualification_type, true);

        if (is_array($outerDecoded)) {
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
        return [];
    }

}
