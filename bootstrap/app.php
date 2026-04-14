<?php

use App\Exceptions\HostingerQuotaExceededException;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequestIdMiddleware;
use App\Http\Middleware\ResponseTimeMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(RequestIdMiddleware::class);
        $middleware->append(ResponseTimeMiddleware::class);
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HostingerQuotaExceededException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        });
    })->create();
