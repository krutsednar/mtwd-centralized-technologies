<?php

use App\Http\Middleware\ForceHttps;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: '127.0.0.1',     // nginx runs on the same machine; artisan sees REMOTE_ADDR=127.0.0.1
            headers: Request::HEADER_X_FORWARDED_FOR |
                     Request::HEADER_X_FORWARDED_HOST |
                     Request::HEADER_X_FORWARDED_PORT |
                     Request::HEADER_X_FORWARDED_PROTO,
        );

        // Redirect any plain-HTTP request to its HTTPS equivalent.
        // Works after TrustProxies so $request->isSecure() reads X-Forwarded-Proto correctly.
        $middleware->web(ForceHttps::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
