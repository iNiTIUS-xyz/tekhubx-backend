<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DefaultClientList extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'client_title',
        'client_manager_id',
        'logo',
        'website',
        'notes',
        'company_name_logo',
        'client_name_logo',
        'policies',
        'instructions',
        'address_line_one',
        'address_line_two',
        'city',
        'state_id',
        'zip_code',
        'country_id',
        'location_type',
        'projects',
        'work_orders',
        'status',
    ];
    protected $casts = [
        'company_name_logo' => 'boolean',
        'client_name_logo' => 'boolean',
    ];
    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
