@extends('admin.layouts.app')
@section('content')
<div class="container-fluid py-4">
    <h4>Recharge Card PIN Inventory</h4>
    @if(session('message')) <div class="alert alert-success">{{ session('message') }}</div> @endif
    <div class="row g-3 mb-3">@foreach(['available','reserved','sold','refunded','void'] as $status)<div class="col-6 col-md-2"><div class="card p-3"><small class="text-muted text-capitalize">{{ $status }}</small><h4>{{ $summary[$status] ?? 0 }}</h4></div></div>@endforeach</div>
    <form method="GET" class="card p-3 mb-3"><div class="row g-2"><div class="col-md-3"><select name="network_id" class="form-control"><option value="">All networks</option>@foreach($networks as $network)<option value="{{ $network->id }}" {{ request('network_id')==$network->id?'selected':'' }}>{{ $network->name }}</option>@endforeach</select></div><div class="col-md-2"><select name="status" class="form-control"><option value="">All statuses</option>@foreach(['available','reserved','sold','refunded','void'] as $s)<option value="{{ $s }}" {{ request('status')===$s?'selected':'' }}>{{ ucfirst($s) }}</option>@endforeach</select></div><div class="col-md-2"><select name="sort" class="form-control"><option value="created_at">Newest</option><option value="value">Denomination</option><option value="status">Status</option></select></div><div class="col-md-2"><select name="direction" class="form-control"><option value="desc">Descending</option><option value="asc">Ascending</option></select></div><div class="col-md-2"><select name="per_page" class="form-control"><option>25</option><option>50</option><option>100</option></select></div><div class="col-md-1"><button class="btn btn-outline-primary w-100">Filter</button></div></div></form>
    <form method="POST" action="{{ route('admin.recharge_card_pins.store') }}" class="card p-3 mb-4">
        @csrf
        <div class="row g-2 align-items-end">
            <div class="col-md-3"><label>Network</label><select name="network_id" class="form-control" required>@foreach($networks as $network)<option value="{{ $network->id }}">{{ $network->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label>Denomination</label><input name="value" type="number" min="1" step="0.01" class="form-control" required></div>
            <div class="col-md-3"><label>PIN</label><input name="pin" class="form-control" required></div>
            <div class="col-md-2"><label>Serial (optional)</label><input name="serial" class="form-control"></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Add PIN</button></div>
        </div>
    </form>
    <div class="table-responsive"><table class="table"><thead><tr><th>Network</th><th>Value</th><th>Serial</th><th>Status</th><th>Added</th><th></th></tr></thead><tbody>
    @forelse($pins as $pin)<tr><td>{{ $pin->network->name }}</td><td>₦{{ number_format($pin->value, 2) }}</td><td>{{ $pin->serial ?: '—' }}</td><td><span class="badge bg-secondary">{{ ucfirst($pin->status) }}</span></td><td>{{ $pin->created_at }}</td><td>@if($pin->status === 'available')<form method="POST" action="{{ route('admin.recharge_card_pins.destroy', $pin) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Void</button></form>@endif</td></tr>@empty<tr><td colspan="6" class="text-center py-4">No recharge card PINs found.</td></tr>@endforelse
    </tbody></table></div>{{ $pins->links() }}
</div>
@endsection
