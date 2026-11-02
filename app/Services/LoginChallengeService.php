<?php
namespace App\Services;
use App\Models\{LoginChallenge,User};
use Illuminate\Support\Facades\{Hash,DB};
class LoginChallengeService {
 public function begin(User $u, ?string $device, bool $alreadyHashed=false): LoginChallenge { LoginChallenge::where('user_id',$u->id)->whereNull('consumed_at')->update(['consumed_at'=>now()]); $otp=(string)random_int(100000,999999); $c=LoginChallenge::create(['user_id'=>$u->id,'device_id_hash'=>$alreadyHashed?$device:UserSecurityService::deviceHash($device),'otp_hash'=>Hash::make($otp),'expires_at'=>now()->addMinutes(10),'sent_at'=>now()]); UserSecurityService::recordEvent('otp_sent',$u,['challenge_id'=>$c->id]); return $c; }
 public function verify(LoginChallenge $c,string $otp): bool { if($c->consumed_at||now()->gte($c->expires_at)) return false; if(!Hash::check($otp,$c->otp_hash)) return false; $c->update(['consumed_at'=>now()]); return true; }
 public function registerDevice(User $u,LoginChallenge $c): void { if(!$c->device_id_hash)return; DB::table('user_devices')->updateOrInsert(['user_id'=>$u->id,'device_id_hash'=>$c->device_id_hash],['user_agent'=>request()->userAgent(),'last_ip'=>request()->ip(),'verified_at'=>now(),'last_seen_at'=>now(),'revoked_at'=>null,'updated_at'=>now(),'created_at'=>now()]); UserSecurityService::recordEvent('new_device_login',$u,['device_id_hash'=>$c->device_id_hash]); }
}
