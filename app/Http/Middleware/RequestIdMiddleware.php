<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestIdMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Accept caller-provided ID or generate a new UUID (GAP 1)
        $raw       = $request->header('X-Request-ID');
        $requestId = ($raw && Str::isUuid($raw)) ? $raw : (string) Str::uuid();

        $request->headers->set('X-Request-ID', $requestId);

        // Bind to container so HttpHostingerProxyClient can forward as X-Correlation-ID (GAP 1)
        app()->instance('request.id', $requestId);

        // Base log context: request_id always present
        Log::withContext(['request_id' => $requestId]);

        $response = $next($request);

        // GAP 2: add user_id after the request has been authenticated
        if ($request->user()) {
            Log::withContext(['user_id' => $request->user()->id]);
        }

        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}
