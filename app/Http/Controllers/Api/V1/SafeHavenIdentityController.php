<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Fintech\WalletVerificaiton;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SafeHavenIdentityController extends Controller
{
    public function initiate(Request $request)
    {
        $v = Validator::make($request->all(), ['method' => 'required|in:BVN,NIN']);
        if ($v->fails()) return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);
        $record = Auth::user()->walletVerification;
        if (!$record || (int) $record->status !== 0) return response()->json(['success' => false, 'message' => 'Your KYC is not awaiting verification.'], 422);
        if (!$record->safehaven_review_ready_at) return response()->json(['success' => false, 'message' => 'Your verification is still under review.'], 422);
        $method = strtoupper($request->method);
        $number = $method === 'NIN' ? $record->nin : $record->bvn;
        if (!$number) return response()->json(['success' => false, 'message' => "No {$method} is on file."], 422);
        if ($record->safehaven_identity_id && $record->safehaven_identity_expires_at && now()->lt($record->safehaven_identity_expires_at)) {
            return response()->json(['success' => true, 'message' => 'Verification code already sent.', 'challenge_active' => true]);
        }
        $result = initiateSafeHavenIdentity([
            'type' => $method, 'number' => $number,
            'phoneNumber' => $record->mobile_no ?: Auth::user()->phone_number,
        ]);
        if (empty($result['success'])) return response()->json(['success' => false, 'message' => $result['message'] ?? 'Unable to start verification.'], 422);
        $record->safehaven_identity_id = Crypt::encryptString($result['identity_id']);
        $record->safehaven_identity_method = $method;
        $record->safehaven_identity_number_hash = hash('sha256', $number);
        $record->safehaven_identity_initiated_at = now();
        $record->safehaven_identity_expires_at = now()->addMinutes(10);
        $record->safehaven_identity_attempts = 0;
        $record->save();
        return response()->json(['success' => true, 'message' => 'Verification code sent to your registered phone.', 'challenge_active' => true]);
    }

    public function complete(Request $request)
    {
        $v = Validator::make($request->all(), ['otp' => 'required|digits:6']);
        if ($v->fails()) return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);
        $record = Auth::user()->walletVerification;
        if (!$record || !$record->safehaven_identity_id || !$record->safehaven_identity_expires_at || now()->gte($record->safehaven_identity_expires_at)) {
            return response()->json(['success' => false, 'message' => 'This verification code has expired. Start again.'], 422);
        }
        if ((int) $record->safehaven_identity_attempts >= 6) return response()->json(['success' => false, 'message' => 'Too many attempts. Start verification again.'], 429);
        $record->safehaven_identity_attempts = (int) $record->safehaven_identity_attempts + 1;
        $record->save();
        try { $identityId = Crypt::decryptString($record->safehaven_identity_id); } catch (\Throwable $e) { return response()->json(['success' => false, 'message' => 'Verification state is invalid.'], 422); }
        $validated = validateSafeHavenIdentity($identityId, $record->safehaven_identity_method, $request->otp);
        if (empty($validated['success'])) return response()->json(['success' => false, 'message' => $validated['message'] ?? 'Incorrect verification code.'], 422);
        $user = Auth::user();
        $external = 'dupay-kyc-' . $record->id;
        $result = createSafeHavenSubaccount([
            'user_id' => $user->id, 'phoneNumber' => $record->mobile_no ?: $user->phone_number,
            'emailAddress' => $user->email, 'identityType' => $record->safehaven_identity_method,
            'identityNumber' => $record->safehaven_identity_method === 'NIN' ? $record->nin : $record->bvn,
            'identityId' => $identityId, 'otp' => $request->otp, 'externalReference' => $external,
            'dob' => $record->date_of_birth,
        ]);
        if (empty($result['success'])) return response()->json(['success' => false, 'message' => $result['message'] ?? 'Account creation is pending.'], 422);
        $record->safehaven_identity_validated_at = now();
        $record->status = 1;
        $record->reviewed_at = now();
        $record->save();
        return response()->json(['success' => true, 'message' => 'Verification complete. Your account has been created.']);
    }
}
