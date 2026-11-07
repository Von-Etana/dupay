<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\SecurityAlert;
use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Http\Request;

class SecurityAlertController extends Controller
{
    public function index(Request $request)
    {
        $query = SecurityAlert::with(['admin', 'acknowledgedBy'])->latest();

        // Status Filter
        $status = $request->input('status', 'all');
        if ($status === 'pending' || $status === 'open') {
            $query->whereNull('acknowledged_at');
        } elseif ($status === 'acknowledged' || $status === 'resolved') {
            $query->whereNotNull('acknowledged_at');
        }

        // Severity Filter
        if ($request->filled('severity') && $request->severity !== 'all') {
            $query->where('severity', $request->severity);
        }

        // Search Filter
        if ($request->filled('search')) {
            $search = '%' . trim($request->search) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                  ->orWhere('message', 'like', $search)
                  ->orWhere('type', 'like', $search)
                  ->orWhere('ip_address', 'like', $search);
            });
        }

        $alerts = $query->paginate(20)->withQueryString();

        // Summary Statistics
        $totalCount        = SecurityAlert::count();
        $openCount         = SecurityAlert::whereNull('acknowledged_at')->count();
        $acknowledgedCount = SecurityAlert::whereNotNull('acknowledged_at')->count();
        $criticalCount     = SecurityAlert::where('severity', 'critical')->whereNull('acknowledged_at')->count();

        return view('admin.security_alerts.index', compact(
            'alerts',
            'status',
            'totalCount',
            'openCount',
            'acknowledgedCount',
            'criticalCount'
        ));
    }

    public function show(SecurityAlert $securityAlert)
    {
        $securityAlert->load(['admin', 'acknowledgedBy']);

        $user = null;
        $recentEvents = collect();
        $recentTransactions = collect();
        $affectedAdmin = null;

        $metadata = (array) ($securityAlert->metadata ?? []);
        $userId = $metadata['user_id'] ?? null;

        if ($userId) {
            $user = User::with(['profile', 'walletVerification', 'virtualAccount'])->find($userId);
            if ($user) {
                $recentEvents = SecurityEvent::where('user_id', $user->id)
                    ->latest('created_at')
                    ->limit(10)
                    ->get();

                try {
                    $recentTransactions = $user->transaction()
                        ->latest('created_at')
                        ->limit(6)
                        ->get();
                } catch (\Throwable $e) {}
            }
        }

        $adminId = $securityAlert->admin_id ?? ($metadata['admin_id'] ?? null);
        if ($adminId) {
            $affectedAdmin = Admin::find($adminId);
        }

        return view('admin.security_alerts.show', compact(
            'securityAlert',
            'metadata',
            'user',
            'affectedAdmin',
            'recentEvents',
            'recentTransactions'
        ));
    }

    public function acknowledge(Request $request, SecurityAlert $securityAlert)
    {
        $securityAlert->update([
            'acknowledged_at' => now(),
            'acknowledged_by' => auth()->guard('admin')->id(),
        ]);

        return back()->with('message', "Security alert #{$securityAlert->id} has been acknowledged.");
    }
}
