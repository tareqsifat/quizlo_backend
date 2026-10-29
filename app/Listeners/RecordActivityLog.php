<?php

namespace App\Listeners;

use App\Events\ApiRequestHandled;
use App\Models\ActivityLog;
use Illuminate\Contracts\Queue\ShouldQueue;

class RecordActivityLog implements ShouldQueue
{
    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 10;

    public function handle(ApiRequestHandled $event): void
    {
        ActivityLog::create([
            'user_id'     => $event->userId,
            'method'      => $event->method,
            'path'        => $event->path,
            'route'       => $event->route,
            'action'      => $event->action,
            'status_code' => $event->statusCode,
            'duration_ms' => $event->durationMs,
            'ip'          => $event->ip,
            'user_agent'  => $event->userAgent,
            'payload'     => $event->payload,
            'created_at'  => $event->occurredAt,
        ]);
    }
}
