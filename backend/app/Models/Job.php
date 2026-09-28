<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Job extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'job_number',
        'customer_id',
        'worker_id',
        'category_id',
        'skill_id',
        'title',
        'description',
        'status',
        'cancel_reason',
        'reject_reason',
        'estimated_cost',
        'final_cost',
        'scheduled_at',
        'started_at',
        'completed_at',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'estimated_cost' => 'decimal:2',
            'final_cost' => 'decimal:2',
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function location(): HasOne
    {
        return $this->hasOne(JobLocation::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(JobImage::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(JobStatusHistory::class);
    }

    public function chat(): HasOne
    {
        return $this->hasOne(Chat::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }
}
