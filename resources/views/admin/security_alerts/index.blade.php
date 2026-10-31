@extends('admin.layouts.app')

@section('content')
<div class="layout-px-spacing">
    <div class="middle-content container-xxl p-0">
        <!-- HEADER & BREADCRUMB -->
        <div class="page-meta my-4">
            <nav class="breadcrumb-style-one" aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Security Alerts</li>
                </ol>
            </nav>
        </div>

        <!-- STATS CARDS -->
        <div class="row mb-4">
            <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 mb-3">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body d-flex align-items-center justify-content-between p-3">
                        <div>
                            <span class="text-muted text-uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">Pending Alerts</span>
                            <h3 class="mb-0 fw-bold {{ $openCount > 0 ? 'text-warning' : 'text-success' }}">{{ number_format($openCount) }}</h3>
                        </div>
                        <div class="rounded-circle p-3 {{ $openCount > 0 ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 mb-3">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body d-flex align-items-center justify-content-between p-3">
                        <div>
                            <span class="text-muted text-uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">High / Critical Unresolved</span>
                            <h3 class="mb-0 fw-bold {{ $criticalCount > 0 ? 'text-danger' : 'text-muted' }}">{{ number_format($criticalCount) }}</h3>
                        </div>
                        <div class="rounded-circle p-3 {{ $criticalCount > 0 ? 'bg-danger-subtle text-danger' : 'bg-light text-muted' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 mb-3">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body d-flex align-items-center justify-content-between p-3">
                        <div>
                            <span class="text-muted text-uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">Today's Alerts</span>
                            <h3 class="mb-0 fw-bold text-primary">{{ number_format($todayCount) }}</h3>
                        </div>
                        <div class="rounded-circle p-3 bg-primary-subtle text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(session('message'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <strong>Success!</strong> {{ session('message') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="widget-content widget-content-area br-8 p-4 mb-4">
            <h4 class="mb-3">Recent admin activity</h4>
            <div class="table-responsive"><table class="table table-hover align-middle">
                <thead><tr><th>Time</th><th>Admin</th><th>Action</th><th>Module</th><th>IP</th></tr></thead>
                <tbody>
                @forelse($recentActivity as $activity)
                    <tr><td>{{ $activity->created_at?->format('d M Y H:i') }}</td>
                        <td>{{ $activity->user->username ?? 'Unknown' }}</td>
                        <td>{{ $activity->activity }}</td><td>{{ $activity->module }}</td><td>{{ $activity->ip }}</td></tr>
                @empty
                    <tr><td colspan="5" class="text-muted">No recent activity.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>

        <!-- MAIN TABLE -->
        <div class="row">
            <div class="col-12">
                <div class="widget-content widget-content-area br-8 p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                        <div>
                            <h4 class="mb-1 fw-bold">Security Alerts & Audit Log</h4>
                            <p class="text-muted mb-0 small">Real-time alerts, admin actions, and wallet adjustment notifications</p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width: 140px;">Time</th>
                                    <th>Severity</th>
                                    <th style="min-width: 250px;">Alert Details</th>
                                    <th style="min-width: 180px;">Target User</th>
                                    <th>Admin</th>
                                    <th>IP Address</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($alerts as $alert)
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $alert->created_at->format('d M Y') }}</div>
                                        <div class="text-muted small">{{ $alert->created_at->format('H:i:s') }} ({{ $alert->created_at->diffForHumans() }})</div>
                                    </td>
                                    <td>
                                        @php
                                            $sev = strtolower($alert->severity);
                                            $badgeBg = $sev === 'critical' ? 'danger' : ($sev === 'high' ? 'warning' : ($sev === 'medium' ? 'info' : 'secondary'));
                                        @endphp
                                        <span class="badge bg-{{ $badgeBg }} text-uppercase fw-bold" style="letter-spacing: 0.5px;">
                                            {{ $alert->severity }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark mb-1">{{ $alert->title }}</div>
                                        <div class="text-muted small">{{ $alert->message }}</div>
                                        @if(!empty($alert->metadata['amount']))
                                            <div class="mt-2 d-flex flex-wrap gap-1 align-items-center">
                                                <span class="badge bg-dark text-white">
                                                    {{ $alert->metadata['currency'] ?? 'NGN' }} {{ number_format((float)$alert->metadata['amount'], 2) }}
                                                </span>
                                                @if(!empty($alert->metadata['reference']))
                                                    <span class="badge bg-light text-muted border">
                                                        Ref: {{ $alert->metadata['reference'] }}
                                                    </span>
                                                @endif
                                                @if(!empty($alert->metadata['action']))
                                                    <span class="badge {{ strtolower($alert->metadata['action']) === 'credit' ? 'bg-success' : 'bg-danger' }}">
                                                        {{ strtoupper($alert->metadata['action']) }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($alert->target_username || $alert->target_user_id)
                                            <div>
                                                <a href="{{ $alert->target_user_url }}" target="_blank" class="badge bg-primary-subtle text-primary border border-primary-subtle p-2 text-decoration-none d-inline-flex align-items-center" title="Open User Profile in Admin">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                                    {{ '@' . ltrim($alert->target_username ?: ('id:' . $alert->target_user_id), '@') }}
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ms-1 opacity-75"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                                                </a>
                                                @if($alert->target_name)
                                                    <div class="text-dark small fw-semibold mt-1">{{ $alert->target_name }}</div>
                                                @endif
                                                @if($alert->target_email)
                                                    <div class="text-muted small">{{ $alert->target_email }}</div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($alert->admin)
                                            <div class="fw-semibold text-dark">{{ $alert->admin->username ?? ($alert->admin->name ?? $alert->admin->email) }}</div>
                                            <span class="text-muted small">Admin #{{ $alert->admin->id }}</span>
                                        @else
                                            <span class="text-muted">System / Guest</span>
                                        @endif
                                    </td>
                                    <td>
                                        <code class="text-muted small">{{ $alert->ip_address ?? '—' }}</code>
                                    </td>
                                    <td>
                                        @if($alert->acknowledged_at)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                Acknowledged
                                            </span>
                                            @if($alert->acknowledgedBy)
                                                <div class="text-muted small mt-1">by {{ $alert->acknowledgedBy->username ?? $alert->acknowledgedBy->name }}</div>
                                            @endif
                                        @else
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                                Pending
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @can('manage_security_alerts')
                                            @if(!$alert->acknowledged_at)
                                                <form method="POST" action="{{ route('admin.security-alerts.acknowledge', $alert) }}" class="d-inline">
                                                    @csrf
                                                    <button class="btn btn-sm btn-outline-primary" type="submit">
                                                        Acknowledge
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-muted small">Done</span>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <div class="my-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-muted mb-2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                                            <p class="mb-0 fw-semibold">No security alerts recorded.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $alerts->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
