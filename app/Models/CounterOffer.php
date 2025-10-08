<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CounterOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'work_order_unique_id',
        'employed_providers_id',
        'pay_id',
        'schedule_id',
        'expense_id',
        'reason',
        'status',
        'withdraw',
        'expired_request_time',
    ];

    public function counterOfferExpense()
    {
        return $this->belongsTo(CounterOfferExpense::class);
    }

    public function uuidUser()
    {
        return $this->belongsTo(User::class, 'uuid', 'uuid');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function counterOfferPay()
    {
        return $this->belongsTo(CounterOfferPay::class, 'pay_id');
    }

    public function counterOfferSchedule()
    {
        return $this->belongsTo(CounterOfferSchedule::class, 'schedule_id');
    }

    public function work_order()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_unique_id', 'work_order_unique_id');
    }
}
