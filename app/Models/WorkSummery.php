<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkSummery extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_provider_id',
        'user_id',
        'uuid',
        'work_category_id',
        'work_category_name',
        'work_sub_category_id',
        'work_sub_category_name',
    ];

    public function employeeProvider()
    {
        return $this->belongsTo(EmployeeProvider::class, 'employee_provider_id', 'id');
    }

    public function subCategory()
    {
        return $this->belongsTo(WorkSubCategory::class, 'work_category_id', 'id');
    }
}
