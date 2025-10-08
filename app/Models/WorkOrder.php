<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'work_order_unique_id',
        'template_id',
        'work_order_title',
        'default_client_id',
        'project_id',
        'export_bool',
        'counter_offer_bool',
        'gps_bool',
        'service_description_public',
        'service_description_note_private',
        'work_category_id',
        'additional_work_category_id',
        'service_type_id',
        'qualification_type',
        'location_id',
        'schedule_type',
        'schedule_date',
        'schedule_time',
        'time_zone',
        'schedule_date_between_1',
        'schedule_date_between_2',
        'schedule_time_between_1',
        'schedule_time_between_2',
        'between_date',
        'between_time',
        'through_date',
        'through_time',
        'work_order_manager_id',
        'additional_contact_id',
        'documents_file',
        'tasks',
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
        'labor',
        'status',
        'assigned_status',
        'provider_status',
        'assigned_id',
        'assigned_uuid',
        'rule_id',
        'shipment_id',
        'state_tax',
    ];

    protected $casts = [
        'documents_file' => 'array', // or 'collection'
    ];


    public function user()
    {
        return $this->belongsTo(User::class, 'uuid', 'uuid');
    }

    public function assignUser()
    {
        return $this->belongsTo(User::class, 'assigned_uuid', 'uuid');
    }
    public function template()
    {
        return $this->belongsTo(Template::class, 'template_id');
    }

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

    public function additionalContacts()
    {
        return $this->hasMany(AdditionalContact::class, 'work_order_unique_id', 'work_order_unique_id');
    }

    public function shipment()
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class, 'work_order_unique_id', 'work_order_unique_id');
    }

    public function bank_account()
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'work_order_unique_id', 'work_order_unique_id');
    }
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'assigned_id', 'id');
    }
}
