<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'provider_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address_line_1',
        'address_line_2',
        'city',
        'zip_code',
        'bio',
        'state_id',
        'country_id',
        'work_category_id',
        'status',
        'role',
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'provider_id', 'user_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function providerUser()
    {
        return $this->belongsTo(User::class, 'provider_id', 'id');
    }
    public function state()
    {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }

    public function workCategory()
    {
        return $this->belongsTo(WorkCategory::class);
    }

    public function licenseCertificate()
    {
        return $this->hasMany(LicenseAndCertificate::class, 'employee_provider_id');
    }

    public function about()
    {
        return $this->belongsTo(About::class, 'id', 'employee_provider_id');
    }

    public function workSummery()
    {
        return $this->hasMany(WorkSummery::class, 'employee_provider_id', 'id');
    }

    public function skillSet()
    {
        return $this->hasMany(SkillSet::class, 'employee_provider_id', 'id');
    }

    public function equipment()
    {
        return $this->hasMany(Equipment::class, 'employee_provider_id', 'id');
    }

    public function employmentHistory()
    {
        return $this->hasMany(EmploymentHistory::class, 'employee_provider_id', 'id');
    }

    public function education()
    {
        return $this->hasMany(Education::class, 'employee_provider_id', 'id');
    }
}
