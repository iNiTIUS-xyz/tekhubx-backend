<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentLibrary extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'file_path'
    ];
}
