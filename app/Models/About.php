<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class About extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_provider_id',
        'user_id',
        'uuid',
        'tagline',
        'biography',
    ];

    public function employeeProvider()
    {
        return $this->belongsTo(EmployeeProvider::class, 'employee_provider_id', 'id');
    }
}
