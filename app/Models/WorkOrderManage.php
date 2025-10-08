<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrderManage extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'name',
        'user_name',
        'email',
        'status',
        'country_id',
        'state',
        'zip_code',
        'role',
    ];
}
