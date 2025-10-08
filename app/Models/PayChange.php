<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayChange extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'work_order_unique_id',
        'reason',
        'extra_hour',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_unique_id', 'work_order_unique_id');
    }
}
