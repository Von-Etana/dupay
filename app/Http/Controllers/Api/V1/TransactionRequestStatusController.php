<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionRequestStatusController extends Controller
{
    public function show(Request $request, string $requestId)
    {
        if (!preg_match('/\A[A-Za-z0-9_-]{16,128}\z/D', $requestId)) {
            return response()->json(['success' => false, 'message' => 'Invalid request ID'], 400);
        }
        $endpoint = $request->query('endpoint');
        $allowed = ['fund/transfer', 'fund/withdraw', 'fund/withdraw/two', 'buy/airtime', 'buy/data', 'buy/cable', 'buy/data-card', 'buy/exam-pin', 'buy-epin', 'pay-bill'];
        if (!in_array($endpoint, $allowed, true)) {
            return response()->json(['success' => false, 'message' => 'Invalid endpoint'], 400);
        }
        $row = DB::table('money_request_keys')
            ->where('user_id', $request->user('api')->getAuthIdentifier())
            ->where('endpoint_hash', hash('sha256', 'POST:api/v1/' . $endpoint))
            ->where('key_hash', hash('sha256', $requestId))
            ->first();
        if (!$row) {
            return response()->json(['success' => false, 'code' => 'transaction_unknown'], 404);
        }
        return response()->json([
            'success' => true,
            'status' => $row->response_body === null ? 'processing' : 'completed',
            'http_status' => $row->http_status,
            'result' => $row->response_body === null ? null : json_decode($row->response_body, true),
        ]);
    }
}
