<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventBackHistoryMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        return $response->withHeaders([
            'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => 'Sun, 02 Jan 1990 00:00:00 GMT',
        ]);
    }
}
