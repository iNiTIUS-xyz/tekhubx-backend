<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleHasPermission extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $fillable = ['role_id', 'permissions'];

    protected function casts()
    {
        return [
            'permissions' => 'json',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
