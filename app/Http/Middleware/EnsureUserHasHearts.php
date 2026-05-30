<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasHearts
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if ($user && $user->heart && $user->heart->current_hearts <= 0) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'No hearts remaining. Refill required.',
            ], 403);
        }

        return $next($request);
    }
}
