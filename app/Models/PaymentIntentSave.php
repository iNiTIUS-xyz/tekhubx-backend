<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentIntentSave extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'payment_intent_id',
        'work_order_unique_id',
        'amount',
        'client_secret',
        'payment_method',
        'capture_id',	
        'transfer_id',	
        'intent_status',
        'capture_status',
        'description',
    ];
}
