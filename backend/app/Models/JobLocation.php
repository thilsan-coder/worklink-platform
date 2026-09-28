<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'latitude',
        'longitude',
        'landmark',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }
}
