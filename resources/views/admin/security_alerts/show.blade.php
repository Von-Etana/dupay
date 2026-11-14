@extends('admin.layouts.app')

@section('content')
<div class="layout-px-spacing">
    <div class="middle-content container-xxl p-0">
        <div class="row layout-top-spacing">
            <div class="col-12">

                {{-- Breadcrumb & Navigation Bar --}}
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <a href="{{ route('admin.security-alerts.index') }}" class="btn btn-outline-secondary btn-sm mb-2">
                            &larr; Back to Security Alerts
                        </a>
                        <h4 class="mb-0">
                            Security Incident Report <span class="text-muted">#{{ $securityAlert->id }}</span>
                        </h4>
                        <small class="text-muted">
                            Incident recorded on {{ $securityAlert->created_at->format('M d, Y \a\t H:i:s T') }} 
                            ({{ $securityAlert->created_at->diffForHumans() }})
                        </small>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        @if($securityAlert->acknowledged_at)
                            <span class="badge bg-success py-2 px-3 fs-6">
                                &#10003; Acknowledged ({{ $securityAlert->acknowledged_at->diffForHumans() }})
                            </span>
                        @else
                            <span class="badge bg-danger py-2 px-3 fs-6">
                                &#9888; Pending Action
                            </span>
                            @can('manage_security_alerts')
                                <form method="POST" action="{{ route('admin.security-alerts.acknowledge', $securityAlert) }}" class="d-inline" onsubmit="return confirm('Acknowledge this security alert?');">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        Mark as Acknowledged
                                    </button>
                                </form>
                            @endcan
                        @endif
                    </div>
                </div>

                @if(session('message'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('message') }}
                    </div>
                @endif

                {{-- Top Incident Summary Stat Cards --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <span class="text-muted text-uppercase fw-bold fs-7">Severity</span>
                                <div class="mt-2">
                                    @php
                                        $sev = strtolower($securityAlert->severity ?? 'medium');
                                        $sevClass = $sev === 'critical' ? 'danger' : ($sev === 'high' ? 'warning' : ($sev === 'medium' ? 'info' : 'secondary'));
                                    @endphp
                                    <span class="badge bg-{{ $sevClass }} fs-6 px-3 py-2 text-uppercase">
                                        {{ $securityAlert->severity }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <span class="text-muted text-uppercase fw-bold fs-7">Alert Type</span>
                                <div class="mt-2">
                                    <code class="fs-6 fw-bold text-dark">{{ $securityAlert->type }}</code>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <span class="text-muted text-uppercase fw-bold fs-7">Client IP</span>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark border fs-6 px-2 py-1 font-monospace">
                                        {{ $securityAlert->ip_address ?? 'Unknown' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <span class="text-muted text-uppercase fw-bold fs-7">Status</span>
                                <div class="mt-2">
                                    @if($securityAlert->acknowledged_at)
                                        <span class="badge bg-success fs-6 px-3 py-2">
                                            Acknowledged
                                        </span>
                                        <small class="d-block text-muted mt-1">
                                            By {{ $securityAlert->acknowledgedBy->username ?? ($securityAlert->acknowledgedBy->email ?? 'Admin') }}
                                        </small>
                                    @else
                                        <span class="badge bg-danger fs-6 px-3 py-2">
                                            Pending Review
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Alert Narrative & Trigger Breakdown --}}
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="card-title mb-0 fw-bold">Incident Details</h5>
                    </div>
                    <div class="card-body">
                        <h6 class="fw-bold text-primary mb-2">{{ $securityAlert->title }}</h6>
                        <p class="fs-6 text-dark mb-3">{{ $securityAlert->message }}</p>

                        @if(!empty($metadata['triggers']) && is_array($metadata['triggers']))
                            <div class="alert alert-warning border border-warning mb-0">
                                <h6 class="fw-bold mb-2">&#9888; Security Rule Triggers Activated:</h6>
                                <ul class="mb-0 ps-3">
                                    @foreach($metadata['triggers'] as $trigger)
                                        <li><strong>{{ $trigger }}</strong></li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Subject Dossier (User / Admin) --}}
                @if($user)
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0 fw-bold">Subject Intelligence (User #{{ $user->id }})</h5>
                            <div>
                                <a href="{{ route('admin.user.show', $user->id) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                    View Full User Profile &rarr;
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Full Name</small>
                                    <strong>{{ $user->profile->first_name ?? '' }} {{ $user->profile->last_name ?? '' }}</strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Username / Phone</small>
                                    <strong>{{ $user->profile->username ?? '—' }}</strong><br>
                                    <small class="text-muted">{{ $user->phone_number ?? '—' }}</small>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Email Address</small>
                                    <strong>{{ $user->email }}</strong>
                                    @if((string)$user->email_verify === '1')
                                        <span class="badge bg-success ms-1">Verified</span>
                                    @else
                                        <span class="badge bg-warning ms-1">Unverified</span>
                                    @endif
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Wallet Balance</small>
                                    <strong class="text-success fs-6">&#8358;{{ number_format((float) ($user->wallet->balance ?? 0), 2) }}</strong>
                                </div>

                                <div class="col-md-3">
                                    <small class="text-muted d-block">Account Status</small>
                                    @if($user->ban_status == 1)
                                        <span class="badge bg-danger">Banned</span>
                                    @elseif($user->blocked_status == 1)
                                        <span class="badge bg-warning text-dark">Blocked</span>
                                    @else
                                        <span class="badge bg-success">Active</span>
                                    @endif
                                </div>

                                <div class="col-md-3">
                                    <small class="text-muted d-block">KYC Tier / Level</small>
                                    <span class="badge bg-info text-dark">
                                        {{ $user->level->name ?? 'Level ' . ($user->user_level_id ?? 1) }}
                                    </span>
                                </div>

                                <div class="col-md-3">
                                    <small class="text-muted d-block">OTP Lock Status</small>
                                    @if($user->otp_locked_until && $user->otp_locked_until->isFuture())
                                        <span class="badge bg-danger">Locked until {{ $user->otp_locked_until->format('H:i') }} ({{ $user->otp_locked_until->diffForHumans() }})</span>
                                    @else
                                        <span class="badge bg-light text-muted border">Unlocked</span>
                                    @endif
                                </div>

                                <div class="col-md-3">
                                    <small class="text-muted d-block">Password Lock Status</small>
                                    @if($user->password_locked_until && $user->password_locked_until->isFuture())
                                        <span class="badge bg-danger">Locked until {{ $user->password_locked_until->format('H:i') }} ({{ $user->password_locked_until->diffForHumans() }})</span>
                                    @else
                                        <span class="badge bg-light text-muted border">Unlocked</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Immediate Forensic Remediation Actions --}}
                            @if(($user->otp_locked_until && $user->otp_locked_until->isFuture()) || ($user->password_locked_until && $user->password_locked_until->isFuture()) || $securityAlert->type === 'otp_account_locked')
                                <hr>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    @can('manage_security_alerts')
                                        <form method="POST" action="{{ route('admin.user-security.unlock', $user) }}" class="d-inline-flex gap-2">
                                            @csrf
                                            <input name="reason" required minlength="10" maxlength="1000" class="form-control form-control-sm" placeholder="Reason for unlocking (10+ characters)" style="min-width: 280px;">
                                            <button class="btn btn-sm btn-warning text-nowrap" type="submit">Unlock User</button>
                                        </form>
                                    @endcan

                                    @can('revoke_user_sessions')
                                        <form method="POST" action="{{ route('admin.user-security.revoke', $user) }}" class="d-inline" onsubmit="return confirm('Revoke all active tokens/sessions for this user?');">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Revoke Active Sessions</button>
                                        </form>
                                    @endcan
                                </div>
                            @endif
                        </div>
                    </div>
                @elseif($affectedAdmin)
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h5 class="card-title mb-0 fw-bold">Subject Intelligence (Admin Staff)</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Admin Username</small>
                                    <strong>{{ $affectedAdmin->username ?? '—' }}</strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Email Address</small>
                                    <strong>{{ $affectedAdmin->email }}</strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Created At</small>
                                    <strong>{{ $affectedAdmin->created_at->format('M d, Y H:i') }}</strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Admin ID</small>
                                    <span class="badge bg-secondary">#{{ $affectedAdmin->id }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Financial & Transfer Analysis (if applicable) --}}
                @if(!empty($metadata['amount']) || !empty($metadata['recipient']))
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h5 class="card-title mb-0 fw-bold">Financial Transaction Forensics</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Transaction Amount</small>
                                    <strong class="text-danger fs-5">&#8358;{{ number_format((float) ($metadata['amount'] ?? 0), 2) }}</strong>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Service / Transfer Channel</small>
                                    <strong>{{ $metadata['service'] ?? 'Fund Transfer' }}</strong>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Beneficiary / Recipient</small>
                                    <strong class="text-dark">{{ $metadata['recipient'] ?? 'N/A' }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Correlated Security Audit Trail --}}
                @if(isset($recentEvents) && $recentEvents->isNotEmpty())
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0 fw-bold">Correlated Security Event Timeline</h5>
                            <span class="badge bg-light text-dark border">Recent {{ $recentEvents->count() }} events</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Timestamp</th>
                                            <th>Event</th>
                                            <th>Status</th>
                                            <th>IP Address</th>
                                            <th>Correlation ID</th>
                                            <th>Details / Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentEvents as $event)
                                            <tr>
                                                <td><small>{{ $event->created_at->format('Y-m-d H:i:s') }}</small></td>
                                                <td><code class="fw-bold">{{ $event->event }}</code></td>
                                                <td>
                                                    <span class="badge bg-{{ $event->status === 'success' ? 'success' : ($event->status === 'blocked' ? 'danger' : 'warning') }}">
                                                        {{ strtoupper($event->status) }}
                                                    </span>
                                                </td>
                                                <td><small class="font-monospace">{{ $event->ip_address ?? '—' }}</small></td>
                                                <td>
                                                    @if($event->request_id)
                                                        <small class="font-monospace text-muted">{{ Str::limit($event->request_id, 16) }}</small>
                                                    @else
                                                        <small class="text-muted">—</small>
                                                    @endif
                                                </td>
                                                <td><small>{{ $event->reason ?? '—' }}</small></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Recent Financial Transactions Table (if user transactions exist) --}}
                @if(isset($recentTransactions) && $recentTransactions->isNotEmpty())
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h5 class="card-title mb-0 fw-bold">Recent Account Transactions</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Time</th>
                                            <th>Reference</th>
                                            <th>Service</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentTransactions as $tx)
                                            <tr>
                                                <td><small>{{ $tx->created_at->format('Y-m-d H:i') }}</small></td>
                                                <td><small class="font-monospace">{{ $tx->reference }}</small></td>
                                                <td>{{ $tx->service_type ?? $tx->service }}</td>
                                                <td><strong>&#8358;{{ number_format((float) $tx->amount, 2) }}</strong></td>
                                                <td>
                                                    <span class="badge bg-{{ $tx->status === 'success' ? 'success' : ($tx->status === 'failed' || $tx->status === 'unsuccessful' ? 'danger' : 'secondary') }}">
                                                        {{ ucfirst($tx->status) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Technical & Environmental Forensics --}}
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="card-title mb-0 fw-bold">Network & Environmental Forensics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <small class="text-muted d-block">Correlation / Request ID (<code class="text-dark">X-Request-ID</code>)</small>
                                <span class="badge bg-dark fs-6 px-3 py-2 font-monospace">
                                    {{ $metadata['request_id'] ?? $securityAlert->metadata['request_id'] ?? 'Not Recorded' }}
                                </span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Origin IP Address</small>
                                <span class="badge bg-secondary fs-6 px-3 py-2 font-monospace">
                                    {{ $securityAlert->ip_address ?? ($metadata['ip_address'] ?? 'Unknown') }}
                                </span>
                            </div>
                            <div class="col-12">
                                <small class="text-muted d-block">User-Agent Header</small>
                                <div class="p-2 bg-light rounded border font-monospace text-break small">
                                    {{ $metadata['user_agent'] ?? 'No user-agent captured' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Full Metadata Explorer --}}
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 fw-bold">Complete Forensic Payload (Metadata JSON)</h5>
                    </div>
                    <div class="card-body">
                        <pre class="bg-dark text-light p-3 rounded mb-0" style="max-height: 320px; overflow-y: auto;"><code>{{ json_encode($securityAlert->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                    </div>
                </div>

                <div class="mb-5 text-end">
                    <a href="{{ route('admin.security-alerts.index') }}" class="btn btn-secondary">
                        &larr; Back to All Alerts
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
