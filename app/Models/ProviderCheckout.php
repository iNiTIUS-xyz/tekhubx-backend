<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderCheckout extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'work_order_unique_id',
        'start_date',
        'start_time',
        'duration',
        'message',
        'confirmed',
        'at_risk',
        'on_my_way',
        'on_my_way_at',
        'is_check_in',
        'is_check_out',
        'check_in_time',
        'timeliness_rate',
        'status'
    ];
}
