<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'details',
        'amount',
        'point',
        'work_order_fee',
        'expired_month',
        'status',
    ];
}
