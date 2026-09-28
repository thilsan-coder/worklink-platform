<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'phone_verified_at',
        'email',
        'email_verified_at',
        'password',
        'avatar',
        'role',
        'google_id',
        'facebook_id',
        'fcm_token',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function customerProfile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class);
    }

    public function workerProfile(): HasOne
    {
        return $this->hasOne(WorkerProfile::class);
    }

    public function customerJobs(): HasMany
    {
        return $this->hasMany(Job::class, 'customer_id');
    }

    public function workerJobs(): HasMany
    {
        return $this->hasMany(Job::class, 'worker_id');
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function givenReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'customer_id');
    }

    public function receivedReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'worker_id');
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'complainant_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function isCustomer(): bool
    {
        return in_array($this->role, ['customer', 'both']);
    }

    public function isWorker(): bool
    {
        return in_array($this->role, ['worker', 'both']);
    }
}
