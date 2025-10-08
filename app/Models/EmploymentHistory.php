<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmploymentHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_provider_id',
        'user_id',
        'uuid',
        'company_name',
        'position',
        'start_date',
        'end_date',
        'location',
        'description',
    ];


    public function employeeProvider()
    {
        return $this->belongsTo(EmployeeProvider::class, 'employee_provider_id', 'id');
    }
}
