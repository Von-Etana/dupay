@extends('admin.layouts.app')
@section('content')
<div class="layout-px-spacing"><div class="middle-content container-xxl p-0"><div class="row layout-top-spacing"><div class="col-12"><div class="widget-content widget-content-area br-8">
<div class="d-flex justify-content-between"><h4>Locked users</h4>@can('export_user_security')<a class="btn btn-sm btn-secondary" href="{{ route('admin.user-security.export') }}">Export audit log</a>@endcan</div>
@if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
<form method="GET" class="mb-3"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search email or phone"></form>
<div class="table-responsive"><table class="table"><thead><tr><th>User</th><th>Lock</th><th>Remaining</th><th>IP</th><th>Device</th><th>User agent</th><th>Locked at</th><th>Actions</th></tr></thead><tbody>
@forelse($users as $user)@php($event=$user->latestSecurityEvent)<tr><td>{{ $user->email }}<br><small>{{ $user->phone_number }}</small></td><td>{{ $user->otp_locked_until && $user->otp_locked_until->isFuture() ? 'OTP' : 'Password' }}</td><td>{{ optional($user->otp_locked_until && $user->otp_locked_until->isFuture() ? $user->otp_locked_until : $user->password_locked_until)->diffForHumans() }}</td><td>{{ $event->ip_address ?? '—' }}</td><td><small>{{ $event->device_hash ?? '—' }}</small></td><td><small>{{ Str::limit($event->user_agent ?? '—', 50) }}</small></td><td>{{ optional($event->created_at)->format('Y-m-d H:i:s') ?? '—' }}</td><td>@can('unlock_user_security')<form method="POST" action="{{ route('admin.user-security.unlock', $user) }}" class="d-inline">@csrf<input name="reason" required minlength="10" placeholder="Unlock reason"><button class="btn btn-sm btn-warning">Unlock</button></form>@endcan @can('revoke_user_sessions')<form method="POST" action="{{ route('admin.user-security.revoke', $user) }}" class="d-inline">@csrf<button class="btn btn-sm btn-danger">Revoke sessions</button></form>@endcan</td></tr>@empty<tr><td colspan="8">No locked users.</td></tr>@endforelse
</tbody></table></div>{{ $users->links() }}
</div></div></div></div>
@endsection
