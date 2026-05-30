<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    protected $table = 'otp_verifications';

    public $timestamps = true;
    const UPDATED_AT = null; // only created_at is in SQL

    protected $fillable = [
        'phone',
        'otp_code',
        'purpose',
        'expires_at',
        'verified_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];
}
