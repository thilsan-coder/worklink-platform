<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'job_status_history';

    protected $fillable = [
        'job_id',
        'changed_by_user_id',
        'from_status',
        'to_status',
        'notes',
        'latitude',
        'longitude',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
