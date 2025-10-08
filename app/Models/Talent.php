<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Talent extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'client_id',
        'pool_name'
    ];

    public function poolDetails()
    {
        return $this->hasMany(PoolDetails::class, 'talent_id', 'id');
    }

}
