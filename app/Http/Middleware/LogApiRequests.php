<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        Log::info(sprintf(
            '%s %s %d %.2fms user:%s ip:%s',
            $request->method(),
            $request->path(),
            $response->getStatusCode(),
            (microtime(true) - $start) * 1000,
            $request->user()?->id ?? '-',
            $request->ip(),
        ));

        return $response;
    }
}
