<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveTracking extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_order_unique_id',
        'provider_id',
        'latitude',
        'longitude',
        'speed',
        'heading',
        'accuracy',
        'status',
        'tracked_at',
    ];

    protected $casts = [
        'speed' => 'float',
        'heading' => 'float',
        'accuracy' => 'float',
        'tracked_at' => 'datetime',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_unique_id', 'work_order_unique_id');
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id', 'id');
    }
}
