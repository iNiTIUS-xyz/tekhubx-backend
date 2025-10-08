<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'country',
        'currency',
        'account_holder_name',
        'account_holder_type',
        'routing_number',
        'account_number',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'uuid', 'uuid');
    }

}
