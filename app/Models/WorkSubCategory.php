<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkSubCategory extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'cat_id'
    ];

    public function category()
    {
        return $this->belongsTo(WorkCategory::class, 'cat_id');
    }
    public function services()
    {
        return $this->hasMany(Service::class, 'work_sub_category_id');
    }
}
