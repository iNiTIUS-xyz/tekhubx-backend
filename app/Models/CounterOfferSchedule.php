<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CounterOfferSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'arrive_on',
        'start_at',
        'start_date',
        'start_time',
        'end_date',
        'end_time',
    ];
}
