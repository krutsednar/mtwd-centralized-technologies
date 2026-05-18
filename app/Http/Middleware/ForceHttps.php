<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttps
{
    /**
     * Redirect all plain-HTTP requests to their HTTPS equivalent.
     *
     * Because nginx terminates TLS and forwards via HTTP to artisan serve,
     * we rely on the X-Forwarded-Proto header (trusted via TrustProxies)
     * rather than checking the raw TCP connection.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isSecure()) {
            return redirect()->secure(
                $request->getRequestUri(),
                301,
            );
        }

        return $next($request);
    }
}
