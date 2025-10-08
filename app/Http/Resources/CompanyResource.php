<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
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
            'company_name' => $this->company_name,
            'company_bio' => $this->company_bio,
            'about_us' => $this->about_us,
            // 'types_of_work' => $this->types_of_work,
            // 'skill_sets' => $this->skill_sets,
            // 'equipments' => $this->equipments,
            // 'licenses' => $this->licenses,
            // 'certifications' => $this->certifications,
            // 'employed_providers' => $this->employed_providers,
            // 'status' => $this->status,
            // 'logo' => $this->logo,
            'address' => $this->address,
            'company_website' => $this->company_website,
            'annual_revenue' => $this->annual_revenue,
            'need_technicians' => $this->need_technicians,
            'employee_counter' => $this->employee_counter,
            'technicians_hire' => $this->technicians_hire,
        ];
    }
}
