<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SecurityAlert;
use Illuminate\Http\Request;

class SecurityAlertController extends Controller
{
    public function index()
    {
        $alerts = SecurityAlert::with('admin')
            ->latest()
            ->paginate(25);

        $openCount = SecurityAlert::whereNull('acknowledged_at')->count();

        return view('admin.security_alerts.index', compact('alerts', 'openCount'));
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
