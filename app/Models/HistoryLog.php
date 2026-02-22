<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoryLog extends Model
{
    use HasFactory;

    protected $table = 'history_logs';

    protected $fillable = [
        'client_id',
        'provider_id',
        'work_order_unique_id',
        'work_order_send_request_id',
        'work_order_counter_offer_id',
        'expense_request_id',
        'paychange_id',
        'work_order_report_id',
        'not_interest_id',
        'description',
        'type',
        'date_time',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_unique_id', 'work_order_unique_id');
    }
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id', 'id');
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id', 'id');
    }
}
