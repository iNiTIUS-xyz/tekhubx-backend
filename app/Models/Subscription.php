<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'uuid',
        'start_date_time',
        'end_date_time',
        'amount',
        'point',
        'work_order_fee',
        'stripe_payment_intent_id',	
        'status'
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'uuid', 'uuid');
    }
}
