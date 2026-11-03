<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SecurityAlert;
use App\Models\SiteActivity;
use App\Models\User;
use Illuminate\Http\Request;

class SecurityAlertController extends Controller
{
    public function index()
    {
        $alerts = SecurityAlert::with(['admin', 'acknowledgedBy'])
            ->latest()
            ->paginate(25);

        $openCount = SecurityAlert::whereNull('acknowledged_at')->count();
        $criticalCount = SecurityAlert::whereIn('severity', ['critical', 'high'])->whereNull('acknowledged_at')->count();
        $todayCount = SecurityAlert::whereDate('created_at', today())->count();
        $recentActivity = SiteActivity::with('user')->latest()->limit(20)->get();
        $lockedUsers = User::with('profile')
            ->where(function ($query) {
                $query->where('login_locked_until', '>', now())
                    ->orWhere('otp_locked_until', '>', now());
            })
            ->orderByRaw('COALESCE(otp_locked_until, login_locked_until) asc')
            ->paginate(15, ['*'], 'locked_page');

        return view('admin.security_alerts.index', compact('alerts', 'openCount', 'criticalCount', 'todayCount', 'recentActivity', 'lockedUsers'));
    }

    public function acknowledge(Request $request, SecurityAlert $securityAlert)
    {
        $securityAlert->update([
            'acknowledged_at' => now(),
            'acknowledged_by' => auth()->guard('admin')->id(),
        ]);

        return back()->with('message', 'Security alert acknowledged.');
    }
}
