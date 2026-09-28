<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'default_address',
        'default_latitude',
        'default_longitude',
        'emergency_contact',
        'preferred_payment_method',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
