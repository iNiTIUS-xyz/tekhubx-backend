<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'company_name',
        'company_bio',
        'about_us',
        'types_of_work',
        'skill_sets',
        'equipments',
        'licenses',
        'certifications',
        'employed_providers',
        'status',
        'logo',
        'address',
        'company_website',
        'annual_revenue',
        'need_technicians',
        'employee_counter',
        'technicians_hire',
    ];

    protected $guarded = [];

    protected $casts = [
        'types_of_work' => 'array',
        'skill_sets' => 'array',
        'equipments' => 'array',
        'licenses' => 'array',
        'certifications' => 'array',
    ];

    /**
     * Get the user that owns the company.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the employed providers for the company.
     */
    public function employedProviders()
    {
        return $this->belongsTo(User::class, 'employed_providers');
    }
}
