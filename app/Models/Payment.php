<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory ;

    protected $fillable = [
        'client_id',
        'provider_id',
        'account_id',
        'payment_unique_id',
        'stripe_payment_intent_id',
        'work_order_unique_id',
        'total_labor',
        'services_fee',
        'tax',
        'extra_fee',
        'extra_fee_note',
        'payment_date_time',
        'gateway',
        'expense_fee',
        'pay_change_fee',
        'debit',
        'credit',
        'balance',
        'point_debit',
        'point_credit',
        'point_balance',
        'ip_address',
        'meta_data',
        'status',
        'transaction_type',
        'description',
    ];

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id', 'uuid');
        // return $this->belongsTo(User::class, 'client_id', 'uuid')->where('role', 'Super Admin')->where('organization_role', 'Client');
    }

    public function provider()
    {
        // return $this->belongsTo(User::class, 'provider_id', 'uuid')->where('role', 'Super Admin')->where('organization_role', 'Provider');
        return $this->belongsTo(User::class, 'provider_id', 'uuid');
    }

    public function providerUuid()
    {
        return $this->belongsTo(User::class, 'provider_id', 'uuid');
    }

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_unique_id', 'work_order_unique_id');
    }
}
