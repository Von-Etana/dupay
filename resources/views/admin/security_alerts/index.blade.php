@extends('admin.layouts.app')

@section('content')
<div class="layout-px-spacing">
    <div class="middle-content container-xxl p-0">
        <div class="row layout-top-spacing">
            <div class="col-12">
                <div class="widget-content widget-content-area br-8">

                    {{-- Header --}}
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div>
                            <h4 class="mb-1">Security Incident Alerts</h4>
                            <p class="text-muted mb-0">Monitor and investigate automated security anomalies, lockouts, and unusual activities.</p>
                        </div>
                        <div class="d-flex gap-2">
                            @can('view_user_security')
                                <a href="{{ route('admin.user-security.index') }}" class="btn btn-outline-primary btn-sm">
                                    View Locked Users
                                </a>
                            @endcan
                        </div>
                    </div>

                    @if(session('message'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('message') }}
                        </div>
                    @endif

                    {{-- Status Filter Tabs / Quick Filter Buttons --}}
                    @php
                        $currentStatus = request('status', 'all');
                        $currentSeverity = request('severity', 'all');
                    @endphp
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <a href="{{ route('admin.security-alerts.index', ['status' => 'pending'] + request()->except(['status', 'page'])) }}"
                           class="btn btn-sm {{ $currentStatus === 'pending' ? 'btn-danger text-white' : 'btn-outline-danger' }}">
                            &#9888; Pending Review
                            <span class="badge bg-white text-danger ms-1">{{ $openCount }}</span>
                        </a>

                        <a href="{{ route('admin.security-alerts.index', ['severity' => 'critical'] + request()->except(['severity', 'page'])) }}"
                           class="btn btn-sm {{ $currentSeverity === 'critical' ? 'btn-dark text-white' : 'btn-outline-dark' }}">
                            Critical Severity
                            <span class="badge bg-danger ms-1">{{ $criticalCount }}</span>
                        </a>

                        <a href="{{ route('admin.security-alerts.index', ['status' => 'acknowledged'] + request()->except(['status', 'page'])) }}"
                           class="btn btn-sm {{ $currentStatus === 'acknowledged' ? 'btn-success text-white' : 'btn-outline-success' }}">
                            &#10003; Acknowledged / Resolved
                            <span class="badge bg-light text-success ms-1">{{ $acknowledgedCount }}</span>
                        </a>

                        <a href="{{ route('admin.security-alerts.index', ['status' => 'all'] + request()->except(['status', 'page'])) }}"
                           class="btn btn-sm {{ $currentStatus === 'all' && $currentSeverity === 'all' && !request('search') ? 'btn-secondary text-white' : 'btn-outline-secondary' }}">
                            All Alerts
                            <span class="badge bg-light text-dark ms-1">{{ $totalCount }}</span>
                        </a>
                    </div>

                    {{-- Search & Severity Filters Form --}}
                    <div class="card bg-light border-0 mb-4 p-3">
                        <form method="GET" action="{{ route('admin.security-alerts.index') }}" class="row g-2 align-items-center">
                            @if(request('status'))
                                <input type="hidden" name="status" value="{{ request('status') }}">
                            @endif

                            <div class="col-md-5">
                                <input type="text" name="search" value="{{ request('search') }}"
                                       class="form-control form-control-sm"
                                       placeholder="Search alert title, message, IP, or type...">
                            </div>

                            <div class="col-md-3">
                                <select name="severity" class="form-select form-select-sm">
                                    <option value="all" {{ request('severity') == 'all' ? 'selected' : '' }}>All Severities</option>
                                    <option value="critical" {{ request('severity') == 'critical' ? 'selected' : '' }}>Critical</option>
                                    <option value="high" {{ request('severity') == 'high' ? 'selected' : '' }}>High</option>
                                    <option value="medium" {{ request('severity') == 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="low" {{ request('severity') == 'low' ? 'selected' : '' }}>Low</option>
                                </select>
                            </div>

                            <div class="col-md-4 d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                                <a href="{{ route('admin.security-alerts.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                            </div>
                        </form>
                    </div>

                    {{-- Alerts Table --}}
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 150px;">Time</th>
                                    <th style="width: 90px;">Severity</th>
                                    <th>Incident Alert (Click to view full report)</th>
                                    <th style="width: 140px;">IP / Correlation</th>
                                    <th style="width: 130px;">Status</th>
                                    <th style="width: 160px;" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($alerts as $alert)
                                @php
                                    $sev = strtolower($alert->severity ?? 'medium');
                                    $sevBadge = $sev === 'critical' ? 'danger' : ($sev === 'high' ? 'warning' : ($sev === 'medium' ? 'info' : 'secondary'));
                                    $showUrl = route('admin.security-alerts.show', $alert);
                                @endphp
                                <tr style="cursor: pointer;" onclick="if (!event.target.closest('a, button, form, input')) window.location='{{ $showUrl }}';" title="Click to view full report">
                                    <td>
                                        <small class="d-block text-dark fw-bold">{{ $alert->created_at->format('Y-m-d H:i') }}</small>
                                        <small class="text-muted">{{ $alert->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $sevBadge }} text-uppercase px-2 py-1">
                                            {{ $alert->severity }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ $showUrl }}" class="fw-bold text-primary text-decoration-none d-block">
                                            {{ $alert->title }}
                                        </a>
                                        <small class="text-muted d-block text-truncate" style="max-width: 460px;">
                                            {{ $alert->message }}
                                        </small>
                                        @if(!empty($alert->metadata['user_id']))
                                            <small class="badge bg-light text-dark border mt-1">
                                                User #{{ $alert->metadata['user_id'] }}
                                            </small>
                                        @endif
                                        @if(!empty($alert->metadata['amount']))
                                            <small class="badge bg-light text-danger border mt-1">
                                                &#8358;{{ number_format((float)$alert->metadata['amount'], 2) }}
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="font-monospace d-block">{{ $alert->ip_address ?? '—' }}</small>
                                        @if(!empty($alert->metadata['request_id']))
                                            <small class="font-monospace text-muted" title="{{ $alert->metadata['request_id'] }}">
                                                {{ Str::limit($alert->metadata['request_id'], 12) }}
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($alert->acknowledged_at)
                                            <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                                                &#10003; Acknowledged
                                            </span>
                                            <small class="d-block text-muted mt-1" style="font-size: 11px;">
                                                {{ $alert->acknowledgedBy->username ?? ($alert->acknowledgedBy->email ?? 'Admin') }}
                                            </small>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1">
                                                &#9888; Pending Review
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex gap-1 justify-content-end">
                                            <a href="{{ $showUrl }}" class="btn btn-sm btn-outline-primary" title="View Full Investigation Report">
                                                View Report &rarr;
                                            </a>

                                            @can('manage_security_alerts')
                                                @if(!$alert->acknowledged_at)
                                                    <form method="POST" action="{{ route('admin.security-alerts.acknowledge', $alert) }}" class="d-inline" onsubmit="return confirm('Acknowledge alert #{{ $alert->id }}?');">
                                                        @csrf
                                                        <button class="btn btn-sm btn-outline-success" type="submit" title="Mark as Acknowledged">
                                                            &#10003;
                                                        </button>
                                                    </form>
                                                @endif
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <em>No security alerts found matching the current filter criteria.</em>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="mt-3">
                        {{ $alerts->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
