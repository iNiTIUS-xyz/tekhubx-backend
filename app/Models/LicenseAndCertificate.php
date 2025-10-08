<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LicenseAndCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'client_id',
        'user_id',
        'uuid',
        'employee_provider_id',
        'license_id',
        'certificate_id',
        'state_name',
        'license_number',
        'applicable_work_category_id',
        'certificate_number',
        'issue_date',
        'expired_date',
        'file',
        'status',
    ];


    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id', 'id');
    }
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id', 'id');
    }
    public function employeeProvider()
    {
        return $this->belongsTo(EmployeeProvider::class, 'employee_provider_id', 'id');
    }

    public function certificate()
    {
        return $this->belongsTo(QualificationSubCategory::class, 'certificate_id', 'id')->where('qualification_type_id', 1);
    }

    public function license()
    {
        return $this->belongsTo(QualificationSubCategory::class, 'license_id', 'id')->where('qualification_type_id', 2);
    }
}
