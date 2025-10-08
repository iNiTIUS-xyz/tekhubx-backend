<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory;
    protected $fillable = [
        'uuid',
        'title',
        'default_client_id',
        'project_manager_id',
        'bank_account_id',
        'provider_penalty',
        'secondary_account_owner_id',
        'auto_dispatch',
        'notification_enabled',
        'other'
    ];

    public function default_client()
    {
        return $this->belongsTo(DefaultClientList::class, 'default_client_id');
    }
    public function project_manager()
    {
        return $this->belongsTo(ClientManager::class, 'project_manager_id');
    }
    public function bank_account()
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }
    public function secondary_acc()
    {
        return $this->belongsTo(SecondayAccountOwner::class, 'secondary_account_owner_id');
    }

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class, 'project_id');
    }
}
