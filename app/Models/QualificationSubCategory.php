<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QualificationSubCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'qualification_type_id',
        'name',
        'status'
    ];

    public function qualification(){
        return $this->belongsTo(QualificationType::class, 'qualification_type_id');
    }


}
