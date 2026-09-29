<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'customer_id',
        'worker_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'gateway',
        'gateway_transaction_id',
        'paid_at',
        'failure_reason',
        'refunded_at',
        'refund_reason',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
            'metadata' => 'array',
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

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function successfulTransaction(): HasOne
    {
        return $this->hasOne(Transaction::class)->where('status', 'COMPLETED')->where('type', 'PAYMENT');
    }

    public function isPaid(): bool
    {
        return $this->status === 'PAID';
    }

    public function isFailed(): bool
    {
        return $this->status === 'FAILED';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'REFUNDED';
    }
}
