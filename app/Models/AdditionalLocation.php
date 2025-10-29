<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdditionalLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'display_name',
        'location_type',
        'country_id',
        'country_name',
        'address_line_1',
        'address_line_2',
        'city',
        'state_id',
        'state_name',
        'save_name',
        'zip_code',
        'latitude',
        'longitude',

        'name',
        'default_client_id',
        'location_group_id',
        'name_description',
        'phone',
        'phone_ext',
        'email',
        'note'
    ];

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
    public function state_name()
    {
        return $this->belongsTo(State::class, 'state');
    }

     public function stateName()
    {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }

    public function default_client()
    {
        return $this->belongsTo(DefaultClientList::class, 'default_client_id');
    }
    public function defaultClient()
    {
        return $this->belongsTo(DefaultClientList::class, 'default_client_id');
    }
}
