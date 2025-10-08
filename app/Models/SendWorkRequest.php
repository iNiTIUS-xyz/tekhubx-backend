<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SendWorkRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'work_order_unique_id',
        'uuid',
        'user_id',
        'request_date_time',
        'after_withdraw',
        'expired_request_time',
        'status',
    ];

    public function users()
    {
        return $this->belongsTo(User::class, 'uuid', 'uuid');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    // public function employed_provider()
    // {
    //     return $this->belongsTo(EmployeeProvider::class, 'employed_provider_id', 'id');
    // }

    public function work_order()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_unique_id', 'work_order_unique_id');
    }

}
