<?php
namespace App\Services;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class UserSecurityService {
 public static function deviceHash(?string $id): ?string { return $id ? hash('sha256',trim($id)) : null; }
 public static function passwordLocked(User $u): bool { return $u->login_locked_until && now()->lt($u->login_locked_until); }
 public static function otpLocked(User $u): bool { return $u->otp_locked_until && now()->lt($u->otp_locked_until); }
 public static function recordEvent(string $type, ?User $u=null, array $meta=[], string $severity='medium'): void { if(DB::getSchemaBuilder()->hasTable('security_events')) DB::table('security_events')->insert(['user_id'=>$u?->id,'event_type'=>$type,'severity'=>$severity,'ip_address'=>request()->ip(),'user_agent'=>Str::limit((string)request()->userAgent(),512,''),'metadata'=>json_encode($meta),'created_at'=>now(),'updated_at'=>now()]); }
 public static function recordPasswordFailure(User $u): bool { $n=(int)$u->failed_login_attempts+1; $locked=$n>=6; $u->failed_login_attempts=$locked?0:$n; $u->login_locked_until=$locked?now()->addHours(2):null; $u->save(); self::recordEvent($locked?'account_locked':'login_failed',$u,['attempts'=>$n,'lock_hours'=>$locked?2:0],$locked?'critical':'high'); return $locked; }
 public static function resetPasswordFailures(User $u): void { $u->failed_login_attempts=0; $u->login_locked_until=null; $u->save(); }
 public static function recordOtpFailure(User $u): bool { $n=(int)$u->otp_failed_attempts+1; $locked=$n>=3; $u->otp_failed_attempts=$locked?0:$n; $u->otp_locked_until=$locked?now()->addHours(6):null; $u->save(); self::recordEvent($locked?'otp_locked':'otp_failed',$u,['attempts'=>$n],$locked?'critical':'high'); return $locked; }
 public static function resetOtpFailures(User $u): void { $u->otp_failed_attempts=0; $u->otp_locked_until=null; $u->save(); }
}
