<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FrontendService extends Model
{
    use HasFactory;

    protected $fillable = [
        'frontend_service_category_id',
        'title',
        'slug',
        'short_description',
        'banner_image',
        'description'
    ];

    public function frontendServiceCategory()
    {
        return $this->belongsTo(FrontendServiceCategory::class);
    }
}
