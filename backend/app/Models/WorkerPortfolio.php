<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkerPortfolio extends Model
{
    use HasFactory;

    protected $table = 'worker_portfolio';

    protected $fillable = [
        'worker_profile_id',
        'title',
        'description',
        'image_path',
        'sort_order',
    ];

    public function workerProfile(): BelongsTo
    {
        return $this->belongsTo(WorkerProfile::class);
    }
}
