<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_order_unique_id',
        'tracking_number',
        'shipment_description',
        'shipment_carrier',
        'shipment_carrier_name',
        'shipment_direction',
    ];
}
