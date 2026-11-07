<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\LoginChallengeService;
use Illuminate\Http\Request;

class LoginChallengeController extends Controller
{
    public function send(Request $request, LoginChallengeService $service)
    {
        $this->validateChallenge($request);
        return $this->respond($service->send($request));
    }

    public function verify(Request $request, LoginChallengeService $service)
    {
        $this->validateChallenge($request);
        $request->validate(['otp' => ['required', 'string', 'regex:/^[0-9]{6}$/'], 'fcmToken' => 'nullable|string|max:512']);
        return $this->respond($service->verify($request));
    }

    private function validateChallenge(Request $request): void
    {
        $request->validate(['challenge_id' => 'required|string|size:64', 'device_id' => 'required|string|min:16|max:255']);
    }

    private function respond(array $result)
    {
        $status = $result['_status'] ?? 200;
        unset($result['_status']);
        return response()->json($result, $status);
    }
}
