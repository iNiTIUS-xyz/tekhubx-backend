<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'client_title',
        'client_manager_id',
        'website',
        'logo',
        'notes',
        'default_policies',
        'default_standard_instruction',
        'address_line_1',
        'address_line_2',
        'city',
        'state_id',
        'zip_code',
        'country_id',
        'location_type',
        'company_name_with_logo',
        'client_name_with_logo'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
