<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class State extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'country_id',
        'tax',
        'status',
        'short_name',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
}
