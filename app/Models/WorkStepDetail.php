<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkStepDetail extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'short_description',
        'step_one_image',
        'step_one_details',
        'step_two_image',
        'step_two_details',
        'step_three_image',
        'step_three_details',
    ];
}
