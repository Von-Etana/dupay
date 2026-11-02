<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Models\LoginChallenge;
use App\Services\{LoginChallengeService,UserSecurityService};
use Illuminate\Http\Request;
class LoginChallengeController extends Controller {
 public function verify(Request $r){$d=$r->validate(['challenge_id'=>'required','otp'=>'required|digits:6']); $c=LoginChallenge::with('user')->find($d['challenge_id']); if(!$c||!$c->user||UserSecurityService::otpLocked($c->user)) return response(['success'=>false,'code'=>'account_locked'],423); if(now()->gte($c->expires_at)) return response(['success'=>false,'code'=>'otp_expired'],422); if(!app(LoginChallengeService::class)->verify($c,$d['otp'])){ $locked=UserSecurityService::recordOtpFailure($c->user); return response(['success'=>false,'code'=>$locked?'otp_retry_limit':'otp_invalid'], $locked?423:422); } UserSecurityService::resetOtpFailures($c->user); app(LoginChallengeService::class)->registerDevice($c->user,$c); $token=$c->user->createToken('authToken'); UserSecurityService::recordEvent('otp_success',$c->user); return response(['success'=>true,'user'=>$c->user,'access_token'=>$token->accessToken]); }
 public function resend(Request $r){$d=$r->validate(['challenge_id'=>'required']); $old=LoginChallenge::with('user')->findOrFail($d['challenge_id']); if($old->sent_at&&now()->diffInSeconds($old->sent_at)<60)return response(['success'=>false,'code'=>'resend_cooldown'],429); $c=app(LoginChallengeService::class)->begin($old->user,$old->device_id_hash,true); return response(['success'=>false,'code'=>'otp_required','challenge_id'=>$c->id]);}
 public function send(Request $r){$d=$r->validate(['challenge_id'=>'required']); return $this->resend($r);}
}
