<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'image',
        'designation',
        'gender',
        'portfolio_url',
        'linkedin_url',
        'facebook_url',
        'x_url',
        'status'
    ];

}
