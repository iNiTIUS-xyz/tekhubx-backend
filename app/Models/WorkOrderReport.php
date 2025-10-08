<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrderReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'work_order_unique_id',
        'type',
        'description'
    ];

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id', 'id');
    }
}
