<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZipCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'zip_code',
        'latitude',
        'longitude',
        'state_id',
        'status',
    ];

    public function state()
    {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }
}
