<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ResponseTimeMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        $elapsed = round((microtime(true) - $start) * 1000, 2);

        $response->headers->set('X-Response-Time', $elapsed . 'ms');

        // GAP 5: Log slow requests as WARNING with structured context
        $threshold = (int) config('app.slow_request_threshold_ms', 2000);
        if ($elapsed > $threshold) {
            Log::warning('Slow request detected.', [
                'elapsed_ms' => $elapsed,
                'threshold'  => $threshold,
                'method'     => $request->method(),
                'route'      => $request->path(),
                'user_id'    => $request->user()?->id,
            ]);
        }

        return $response;
    }
}
