<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'template_name',
        'default_client_id',
        'project_id',
        'work_order_title',
        'export_button',
        'counter_offer',
        'gps_on',
        'public_description',
        'private_description',
        'work_category_id',
        'additional_work_category_id',
        'service_type_id',
        'qualification_type',
        'work_order_manager_id',
        'additional_contact_id',
        'task',
        'buyer_custom_field',
        'pay_type',
        'hourly_rate',
        'max_hours',
        'approximate_hour_complete',
        'total_pay',
        'per_device_rate',
        'max_device',
        'fixed_payment',
        'fixed_hours',
        'additional_hourly_rate',
        'max_additional_hour',
        'bank_account_id',
        'rule_id'
    ];

    public function default_client()
    {
        return $this->belongsTo(DefaultClientList::class, 'default_client_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function work_category()
    {
        return $this->belongsTo(WorkCategory::class, 'work_category_id');
    }

    public function additional_work_category()
    {
        return $this->belongsTo(WorkCategory::class, 'additional_work_category_id');
    }

    public function service_type()
    {
        return $this->belongsTo(Service::class, 'service_type_id');
    }

    public function additional_location()
    {
        return $this->belongsTo(AdditionalLocation::class, 'location_id');
    }

    public function manager()
    {
        return $this->belongsTo(ClientManager::class, 'work_order_manager_id');
    }

    public function additional_contact()
    {
        return $this->belongsTo(AdditionalContact::class, 'additional_contact_id');
    }
    public function shipment()
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }

    public function bank_account()
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

}
