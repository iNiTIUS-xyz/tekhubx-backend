<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientManager extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_id',
        'name',
        'user_name',
        'email',
        'status',
        'address_one',
        'address_two',
        'country_id',
        'state',
        'zip_code',
        'role',
        'remember_token'
    ];

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
    public function state_name()
    {
        return $this->belongsTo(State::class, 'state');
    }
}
