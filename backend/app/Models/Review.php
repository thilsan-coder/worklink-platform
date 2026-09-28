<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'job_id',
        'customer_id',
        'worker_id',
        'overall_rating',
        'communication_rating',
        'work_quality_rating',
        'punctuality_rating',
        'professionalism_rating',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'overall_rating' => 'decimal:1',
            'communication_rating' => 'decimal:1',
            'work_quality_rating' => 'decimal:1',
            'punctuality_rating' => 'decimal:1',
            'professionalism_rating' => 'decimal:1',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }
}
