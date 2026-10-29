<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

class DeduplicateMoneyRequest
{
    public function handle($request, Closure $next)
    {
        $user = $request->user('api');
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $key = $request->header('Idempotency-Key');
        if ($key !== null && !preg_match('/\A[A-Za-z0-9_-]{16,128}\z/D', $key)) {
            return response()->json(['success' => false, 'message' => 'Invalid Idempotency-Key'], 400);
        }

        $endpointHash = hash('sha256', $request->method() . ':' . $request->path());
        $payloadHash = hash('sha256', $request->getContent());
        // Bank withdrawal already carries a name-enquiry session ID. Treat it
        // as a durable key for older clients that do not send the header.
        $sessionId = $request->input('session_id');
        if ($key === null && in_array($request->path(), ['api/v1/fund/withdraw', 'api/v1/fund/withdraw/two'], true)
            && is_scalar($sessionId) && (string) $sessionId !== '') {
            $key = 'session:' . (string) $sessionId;
        }
        // Other older clients get a short duplicate window until upgraded.
        $keyHash = hash('sha256', $key ?? 'legacy:' . $payloadHash . ':' . intdiv(time(), 60));

        $inserted = DB::table('money_request_keys')->insertOrIgnore([
            'user_id' => $user->getAuthIdentifier(),
            'endpoint_hash' => $endpointHash,
            'key_hash' => $keyHash,
            'payload_hash' => $payloadHash,
            'created_at' => now(),
        ]);

        if ($inserted !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Request already submitted. Check transaction history before retrying.',
            ], 409);
        }

        return $next($request);
    }
}
