<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FrontendServiceCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'header_title',
        'sub_header',
        'description',
        'image',
        'status'
    ];

    public function frontendService()
    {
        return $this->hasMany(FrontendService::class, 'frontend_service_category_id');
    }
}
