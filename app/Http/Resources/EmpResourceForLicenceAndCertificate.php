<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Models\QualificationSubCategory;
use Illuminate\Http\Resources\Json\JsonResource;

class EmpResourceForLicenceAndCertificate extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'license_certificate' => $this->licenseCertificate->map(function ($licenseCert) {
                $result = [
                    'expired_date' => $licenseCert->expired_date,
                ];
                
                if (!is_null($licenseCert->certificate_id)) {
                    $result['certificate_id'] = $licenseCert->certificate_id;
                    $name = QualificationSubCategory::find($licenseCert->certificate_id);
                    $result['certificate_name'] = $name->name;
                }

                if (!is_null($licenseCert->license_id)) {
                    $result['license_id'] = $licenseCert->license_id;
                    $name = QualificationSubCategory::find($licenseCert->license_id);
                    $result['license_name'] = $name->name;
                }

                return $result;
            })
        ];
    }
}
