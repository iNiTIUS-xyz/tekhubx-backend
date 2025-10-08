<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'counter_offer_id',
        'work_order_unique_id',
        'send_work_request_id',
        'expense_request_id',
        'pay_change_id',
        'payment_id',
        'type',
        'notification_text',
        'is_read',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id', 'id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id', 'id');
    }

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_unique_id', 'work_order_unique_id');
    }

    public function payment()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_unique_id', 'work_order_unique_id');
    }

}
