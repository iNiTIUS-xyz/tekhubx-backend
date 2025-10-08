<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'work_order_unique_id',
        'client_id',
        'provider_id',
        'rating',
        'review_text',
        'tag'
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id', 'id');
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id', 'id');
    }

    public function user_uuid()
    {
        return $this->belongsTo(User::class, 'uuid', 'uuid');
    }
}
