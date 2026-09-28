<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'customer_id',
        'worker_id',
        'last_message_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'is_active' => 'boolean',
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

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
