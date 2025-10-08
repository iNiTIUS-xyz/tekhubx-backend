<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrderCompleteFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_uuid',
        'provider_uuid',
        'client_id',
        'provider_id',
        'work_order_unique_id',
        'file',
        'description',
    ];

    public function profile(){
        return $this->belongsTo(Profile::class, 'provider_id', 'user_id');
    }

    public function workOrder(){
        return $this->belongsTo(WorkOrder::class, 'work_order_unique_id', 'work_order_unique_id');
    }
}
