<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoolDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'talent_id',
        'provider_id',
        'status',
    ];

    public function talentData(){
        return $this->belongsTo(Talent::class, 'talent_id', 'id');
    }

    public function provider(){
        return $this->belongsTo(User::class, 'provider_id', 'id');
    }

    public function profile(){
        return $this->belongsTo(Profile::class, 'provider_id', 'user_id');
    }

}
