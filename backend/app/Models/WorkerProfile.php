<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkerProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'bio',
        'experience_years',
        'hourly_rate',
        'service_area_radius_km',
        'current_latitude',
        'current_longitude',
        'address',
        'availability_status',
        'verification_status',
        'verification_rejection_reason',
        'verified_at',
        'average_rating',
        'total_reviews',
        'completed_jobs_count',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'average_rating' => 'decimal:2',
            'hourly_rate' => 'decimal:2',
            'experience_years' => 'integer',
            'service_area_radius_km' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'worker_skills')->withTimestamps();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(WorkerDocument::class);
    }

    public function portfolioItems(): HasMany
    {
        return $this->hasMany(WorkerPortfolio::class);
    }
}
