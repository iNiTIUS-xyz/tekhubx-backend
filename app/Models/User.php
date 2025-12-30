<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $guarded = [];

    protected $fillable = [
        'uuid',
        'organization_role',
        'is_independent',
        'username',
        'email',
        'password',
        'role',
        'role_id',
        'status',
        'email_verified_at',
        'stripe_email',
        'stripe_account_id',
        'stripe_customer_id',
        'stripe_payment_method_id',
        'setup_intent_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function permissions()
    {
        return $this->hasOne(RoleHasPermission::class,  'role_id', 'role_id');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function profile()
    {
        return $this->hasOne(Profile::class, 'user_id');
    }
    public function company()
    {
        return $this->hasOne(Company::class, 'user_id');
    }

    public function profiles()
    {
        return $this->hasMany(Profile::class, 'user_id');  // Correct foreign key
    }

    public function companies()
    {
        return $this->hasMany(Company::class, 'user_id');  // Correct foreign key
    }

    public function sentMessages()
    {
        return $this->hasMany(WorkOrderChatMessage::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(WorkOrderChatMessage::class, 'receiver_id');
    }

    public function about()
    {
        return $this->hasOne(About::class, 'user_id', 'id');
    }

    public function workSummery()
    {
        return $this->hasMany(WorkSummery::class, 'user_id', 'id');
    }
    public function skillSet()
    {
        return $this->hasMany(SkillSet::class, 'user_id', 'id');
    }
    public function equipment()
    {
        return $this->hasMany(Equipment::class, 'user_id', 'id');
    }
    public function employmentHistory()
    {
        return $this->hasMany(EmploymentHistory::class, 'user_id', 'id');
    }
    public function education()
    {
        return $this->hasMany(Education::class, 'user_id', 'id');
    }

    public function licenseAndCertificates()
    {
        return $this->hasMany(LicenseAndCertificate::class, 'provider_id', 'id');
    }

    public function clientLicenseAndCertificates()
    {
        return $this->hasMany(LicenseAndCertificate::class, 'client_id', 'id');
    }
    // public function licenseAndCertificates()
    // {
    //     return $this->hasMany(LicenseAndCertificate::class, 'provider_id', 'id')
    //         ->where(function ($query) {
    //             $query->where('provider_id', $this->id)
    //                 ->orWhere('client_id', $this->id);
    //         });
    // }


    public function latestSentMessage()
    {
        return $this->hasOne(WorkOrderChatMessage::class, 'sender_id')
            ->latestOfMany();
    }

    public function latestReceivedMessage()
    {
        return $this->hasOne(WorkOrderChatMessage::class, 'receiver_id')
            ->latestOfMany();
    }
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function sendWorkRequests()
    {
        // Relationship to find requests associated with this user
        return $this->hasMany(SendWorkRequest::class, 'user_id', 'id');
    }

    public function counterOffers()
    {
        // Relationship to find counter offers associated with this user
        return $this->hasMany(CounterOffer::class, 'user_id', 'id');
    }
}
