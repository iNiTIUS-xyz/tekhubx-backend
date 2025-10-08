<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LicenseAndCertificateResource extends JsonResource
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
            'provider' => $this->provider ?? null,
            'client' => $this->client ?? null,
            'employee_provider' => $this->employeeProvider,
            'license_id' => $this->license_id,
            'certificate_id' => $this->certificate_id,
            'state_name' => $this->state_name,
            'license_number' => $this->license_number,
            'applicable_work_category_id' => $this->applicable_work_category_id,
            'certificate_number' => $this->certificate_number,
            'issue_date' => $this->issue_date,
            'expired_date' => $this->expired_date,
            'file' => $this->file,
            'status' => $this->status,
        ];
    }
}
