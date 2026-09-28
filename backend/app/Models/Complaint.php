<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Complaint extends Model
{
    use HasFactory;

    protected $fillable = [
        'complaint_number',
        'job_id',
        'complainant_id',
        'against_user_id',
        'subject',
        'description',
        'status',
        'admin_notes',
        'resolved_at',
        'resolved_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function complainant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'complainant_id');
    }

    public function againstUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'against_user_id');
    }

    public function resolvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'resolved_by_admin_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ComplaintAttachment::class);
    }
}
