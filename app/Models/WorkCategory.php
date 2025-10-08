<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkCategory extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $fillable = ['name'];

    public function workSubCategoryData()
    {
        return $this->hasMany(WorkSubCategory::class, 'cat_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'work_category_id');
    }
}
