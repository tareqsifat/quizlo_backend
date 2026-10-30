<?php

namespace App\Http\Middleware;

use App\Events\ApiRequestHandled;
use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequests
{
    /**
     * Request keys whose values are never written to the activity log.
     */
    private const SENSITIVE_KEYS = [
        'password', 'password_confirmation', 'current_password', 'new_password',
        'token', 'access_token', 'refresh_token', 'id_token', 'client_secret',
        'otp', 'otp_code', 'secret',
    ];

    /**
     * Payloads larger than this (JSON bytes) are replaced with a marker.
     */
    private const MAX_PAYLOAD_BYTES = 16384;

    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        $durationMs = (microtime(true) - $start) * 1000;
        $userId = $request->user()?->id;

        Log::info(sprintf(
            '%s %s %d %.2fms user:%s ip:%s',
            $request->method(),
            $request->path(),
            $response->getStatusCode(),
            $durationMs,
            $userId ?? '-',
            $request->ip(),
        ));

        // Persisted to activity_log by a queued listener so the request isn't slowed down.
        try {
            if (!$this->shouldRecord($request)) {
                return $response;
            }

            $route = $request->route();

            ApiRequestHandled::dispatch(
                $userId,
                $request->method(),
                Str::limit($request->path(), 2048, ''),
                $route?->uri(),
                $route?->getActionName(),
                $response->getStatusCode(),
                (int) round($durationMs),
                $request->ip(),
                Str::limit((string) $request->userAgent(), 512, '') ?: null,
                $this->payload($request),
                now()->toDateTimeString(),
            );
        } catch (\Throwable $e) {
            // Activity logging must never break the API response.
            Log::warning('Failed to dispatch activity log: '.$e->getMessage());
        }

        return $response;
    }

    /**
     * Read-only requests (GET, HEAD, OPTIONS) are skipped unless an admin turned them on.
     */
    private function shouldRecord(Request $request): bool
    {
        return !in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)
            || (bool) Setting::get(Setting::ACTIVITY_LOG_INCLUDE_GET, false);
    }

    private function payload(Request $request): ?array
    {
        $input = $this->redact($request->except(array_keys($request->allFiles())));

        if ($files = array_keys($request->allFiles())) {
            $input['_files'] = $files;
        }

        if ($input === []) {
            return null;
        }

        if (strlen((string) json_encode($input)) > self::MAX_PAYLOAD_BYTES) {
            return ['_truncated' => true, '_keys' => array_keys($input)];
        }

        return $input;
    }

    private function redact(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::SENSITIVE_KEYS, true)) {
                $data[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $data[$key] = $this->redact($value);
            }
        }

        return $data;
    }
}
