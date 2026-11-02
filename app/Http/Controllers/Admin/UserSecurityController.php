<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserSecurityService;
use Illuminate\Http\Request;
use App\Models\SecurityEvent;

class UserSecurityController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->where(function ($q) {
            $q->where('password_locked_until', '>', now())->orWhere('otp_locked_until', '>', now());
        });
        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) { $q->where('email', 'like', "%{$search}%")->orWhere('phone_number', 'like', "%{$search}%"); });
        }
        return view('admin.user_security.index', ['users' => $query->with('latestSecurityEvent')->latest()->paginate(25)->withQueryString()]);
    }

    public function unlock(Request $request, User $user, UserSecurityService $security)
    {
        $data = $request->validate(['reason' => 'required|string|min:10|max:1000']);
        $security->unlock($user, $request, (int) auth('admin')->id(), $data['reason']);
        return back()->with('message', 'Account unlocked and sessions revoked.');
    }

    public function revoke(Request $request, User $user, UserSecurityService $security)
    {
        $security->revokeSessions($user, $request, (int) auth('admin')->id(), 'Admin requested session revocation');
        return back()->with('message', 'All user sessions revoked.');
    }

    public function export(Request $request)
    {
        $events = SecurityEvent::with('user')->latest('created_at')->get();
        return response()->streamDownload(function () use ($events) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['timestamp', 'event', 'user_id', 'email', 'ip_address', 'device_hash', 'user_agent', 'admin_id', 'reason']);
            foreach ($events as $event) {
                fputcsv($out, [$event->created_at, $event->event, $event->user_id, optional($event->user)->email, $event->ip_address, $event->device_hash, $event->user_agent, $event->admin_id, $event->reason]);
            }
            fclose($out);
        }, 'security-events-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }
}
