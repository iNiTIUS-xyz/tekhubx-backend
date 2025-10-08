<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CounterOfferPay extends Model
{
    use HasFactory;
    protected $fillable = [
        'type',
        'max_hour',
        'amount_per_hour',
        'total_pay_amount',
        'amount_per_device',
        'max_device',
        'fixed_amount',
        'fixed_amount_max_hours',
        'hourly_amount_after',
        'hourly_amount_max_hours',
    ];
}
