<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Education extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_provider_id',
        'user_id',
        'uuid',
        'school_name',
        'degree',
        'field_of_study',
        'start_date',
        'end_date',
        'location',
        'activities'
    ];

    public function employeeProvider()
    {
        return $this->belongsTo(EmployeeProvider::class, 'employee_provider_id', 'id');
    }
}
