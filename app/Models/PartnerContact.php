<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartnerContact extends Model
{
    use HasFactory;
    protected $fillable = [
        'first_name',
        'last_name',
        'company_name',
        'email',
        'about_your_company',
        'partnership_interested',
    ];
}
