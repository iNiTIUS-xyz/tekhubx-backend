<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkillSet extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_provider_id',
        'user_id',
        'uuid',
        'name'
    ];

    public function employeeProvider()
    {
        return $this->belongsTo(EmployeeProvider::class, 'employee_provider_id', 'id');
    }
}
