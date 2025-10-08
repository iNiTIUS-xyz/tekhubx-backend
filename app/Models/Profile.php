<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Profile extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'company_id',
        'first_name',
        'last_name',
        'phone',
        'about',
        'terms_of_service',
        'login_date_time',
        'joining_ip',
        'joining_ip_location',
        'joining_city',
        'why_chosen_us',
        'country_id',
        'state_id',
        'city',
        'address_1',
        'address_2',
        'zip_code',
        'latitude',
        'longitude',
        'social_security_number',
        'profile_status',
        'profile_image'
    ];

    protected $casts = [
        'terms_of_service' => 'boolean',
        'login_date_time' => 'datetime',
    ];

    /**
     * Get the user that owns the profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company that the profile belongs to.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }

    protected $guarded = [];
}
