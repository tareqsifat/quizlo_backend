<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $table = 'activity_log';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'method',
        'path',
        'route',
        'action',
        'status_code',
        'duration_ms',
        'ip',
        'user_agent',
        'payload',
        'created_at',
    ];

    protected $casts = [
        'payload'    => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
