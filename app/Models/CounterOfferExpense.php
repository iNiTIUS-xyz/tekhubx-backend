<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CounterOfferExpense extends Model
{
    use HasFactory;
    protected $fillable = [
        'category',
        'description',
        'total_amount',
    ];
}
