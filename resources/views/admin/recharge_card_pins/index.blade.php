@extends('admin.layouts.app')
@section('content')
<div class="container-fluid py-4">
    <h4>Recharge Card PIN Inventory</h4>
    @if(session('message')) <div class="alert alert-success">{{ session('message') }}</div> @endif
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
    @foreach($pins as $pin)<tr><td>{{ $pin->network->name }}</td><td>₦{{ number_format($pin->value, 2) }}</td><td>{{ $pin->serial ?: '—' }}</td><td>{{ ucfirst($pin->status) }}</td><td>{{ $pin->created_at }}</td><td>@if($pin->status === 'available')<form method="POST" action="{{ route('admin.recharge_card_pins.destroy', $pin) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Void</button></form>@endif</td></tr>@endforeach
    </tbody></table></div>{{ $pins->links() }}
</div>
@endsection
