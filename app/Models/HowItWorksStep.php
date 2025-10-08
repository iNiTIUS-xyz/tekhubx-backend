<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HowItWorksStep extends Model
{
    use HasFactory;

    protected $table = 'how_it_works_steps';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'step_count',
        'step_title',
        'step_description',
        'step_image',
    ];
}
