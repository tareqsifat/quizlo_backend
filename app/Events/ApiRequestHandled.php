<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired once per API request after the response is built. Carries only
 * scalars/arrays so it serializes cleanly onto the queue.
 */
class ApiRequestHandled
{
    use Dispatchable;

    public function __construct(
        public ?int $userId,
        public string $method,
        public string $path,
        public ?string $route,
        public ?string $action,
        public int $statusCode,
        public int $durationMs,
        public ?string $ip,
        public ?string $userAgent,
        public ?array $payload,
        public string $occurredAt,
    ) {}
}
