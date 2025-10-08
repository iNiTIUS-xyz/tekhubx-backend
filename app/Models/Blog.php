<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'image',
        'description',
        'tags',
        'status',
        'slug',
        'admin_id',
        'total_view',
    ];

    public function admin()
    {
        return $this->belongsTo(Profile::class, 'admin_id', 'user_id');
    }

}
