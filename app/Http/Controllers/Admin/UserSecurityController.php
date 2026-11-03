<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserSecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class UserSecurityController extends Controller {
 public function unlock(Request $r, User $user){ $data=$r->validate(['reason'=>'required|string|min:5|max:500']); $user->failed_login_attempts=0; $user->login_locked_until=null; $user->otp_failed_attempts=0; $user->otp_locked_until=null; $user->save(); DB::table('oauth_access_tokens')->where('user_id',$user->id)->update(['revoked'=>true]); UserSecurityService::recordEvent('admin_unlock',$user,['reason'=>$data['reason'],'admin_id'=>auth()->guard('admin')->id()],'critical'); return back()->with('success','Account unlocked and sessions revoked.'); }
 public function revokeSessions(User $user){ DB::table('oauth_access_tokens')->where('user_id',$user->id)->update(['revoked'=>true]); UserSecurityService::recordEvent('session_revocation',$user,['admin_id'=>auth()->id()],'high'); return back()->with('success','All sessions revoked.'); }
}
