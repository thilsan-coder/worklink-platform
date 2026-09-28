<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkerDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'worker_profile_id',
        'document_type',
        'document_number',
        'file_path',
        'file_type',
        'status',
        'rejection_reason',
    ];

    public function workerProfile(): BelongsTo
    {
        return $this->belongsTo(WorkerProfile::class);
    }
}
