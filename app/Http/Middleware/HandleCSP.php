<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleCSP
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Allow unsafe-eval for Leaflet Velocity (Debugging Permissive)
        $response->headers->set('Content-Security-Policy', "default-src * 'self' 'unsafe-inline' 'unsafe-eval' data: gap: content: blob:; script-src * 'self' 'unsafe-inline' 'unsafe-eval' blob: data:; style-src * 'self' 'unsafe-inline'; img-src * 'self' data: blob:; connect-src * 'self'; worker-src * 'self' 'unsafe-inline' 'unsafe-eval' blob: data:;");

        return $response;
    }
}
