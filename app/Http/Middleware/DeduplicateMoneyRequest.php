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
            if ($request->header('Idempotency-Key') === null) {
                return response()->json(['success' => false, 'message' => 'Duplicate request'], 409);
            }
            $existing = DB::table('money_request_keys')
                ->where('user_id', $user->getAuthIdentifier())
                ->where('endpoint_hash', $endpointHash)
                ->where('key_hash', $keyHash)
                ->first();
            if ($existing && $existing->payload_hash !== $payloadHash) {
                return response()->json(['success' => false, 'message' => 'Request ID already used for different details.'], 409);
            }
            if ($existing && $existing->response_body !== null) {
                return response($existing->response_body, $existing->http_status ?: 200)
                    ->header('Content-Type', 'application/json');
            }
            return response()->json([
                'success' => false,
                'code' => 'transaction_processing',
                'message' => 'Transaction is being confirmed. Check its status before retrying.',
            ], 202);
        }

        $response = $next($request);
        if ($key !== null && $response->getStatusCode() < 500) {
            DB::table('money_request_keys')
                ->where('user_id', $user->getAuthIdentifier())
                ->where('endpoint_hash', $endpointHash)
                ->where('key_hash', $keyHash)
                ->update([
                    'status' => 'completed',
                    'http_status' => $response->getStatusCode(),
                    'response_body' => $response->getContent(),
                    'completed_at' => now(),
                ]);
        }
        return $response;
    }
}
