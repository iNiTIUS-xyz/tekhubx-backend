<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'gateway_name',
        'stripe_secret',
        'stripe_public',
        'stripe_webhook_secret',
        'stripe_account_id',
        'stripe_mode',
    ];
}
