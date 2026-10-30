@extends('admin.layouts.app')

@section('content')
<div class="layout-px-spacing">
    <div class="middle-content container-xxl p-0">
        <div class="row layout-top-spacing">
            <div class="col-12">
                <div class="widget-content widget-content-area br-8">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4>Security Alerts</h4>
                            <p class="text-muted mb-0">{{ $openCount }} unacknowledged alert(s)</p>
                        </div>
                    </div>

                    @if(session('message'))
                        <div class="alert alert-success">{{ session('message') }}</div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Time</th><th>Severity</th><th>Alert</th><th>Admin</th><th>IP</th><th>Status</th><th></th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($alerts as $alert)
                                <tr>
                                    <td>{{ $alert->created_at->format('Y-m-d H:i:s') }}</td>
                                    <td><span class="badge bg-{{ $alert->severity === 'critical' ? 'danger' : ($alert->severity === 'high' ? 'warning' : 'secondary') }}">{{ strtoupper($alert->severity) }}</span></td>
                                    <td><strong>{{ $alert->title }}</strong><br><small>{{ $alert->message }}</small></td>
                                    <td>{{ $alert->admin->username ?? ($alert->admin->email ?? 'Unknown') }}</td>
                                    <td>{{ $alert->ip_address ?? 'Unknown' }}</td>
                                    <td>{{ $alert->acknowledged_at ? 'Acknowledged' : 'Open' }}</td>
                                    <td>
                                        @can('manage_security_alerts')
                                            @if(!$alert->acknowledged_at)
                                                <form method="POST" action="{{ route('admin.security-alerts.acknowledge', $alert) }}">
                                                    @csrf
                                                    <button class="btn btn-sm btn-primary" type="submit">Acknowledge</button>
                                                </form>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center">No security alerts recorded.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $alerts->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
