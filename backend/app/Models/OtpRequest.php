<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'otp_code',
        'attempts',
        'is_verified',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'expires_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }
}
