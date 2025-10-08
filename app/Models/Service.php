<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'work_sub_category_id'];

    public function workSubCategory()
    {
        return $this->belongsTo(WorkSubCategory::class, 'work_sub_category_id');
    }

}
