<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CorrelationIdMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $correlationId = $request->header('X-Correlation-ID')
            ?: $request->header('X-Request-ID')
            ?: (string) Str::uuid();

        // Store in request attributes for access throughout the request lifecycle
        $request->attributes->set('request_id', $correlationId);
        $request->attributes->set('correlation_id', $correlationId);

        // Share with Laravel's structured logger context if supported
        if (method_exists(Log::class, 'withContext')) {
            try {
                Log::withContext([
                    'request_id'     => $correlationId,
                    'correlation_id' => $correlationId,
                    'client_ip'      => $request->ip(),
                ]);
            } catch (\Throwable $e) {
                // Ignore context binding errors on environments without context support
            }
        }

        $response = $next($request);

        // Propagate correlation headers on the outgoing response
        if (method_exists($response, 'header')) {
            $response->header('X-Request-ID', $correlationId);
            $response->header('X-Correlation-ID', $correlationId);
        }

        return $response;
    }
}
