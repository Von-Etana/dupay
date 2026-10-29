@extends('admin.layouts.app')
@section('style')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/src/table/datatable/datatables-2.css') }}">

    <link rel="stylesheet" type="text/css"
        href="{{ asset('assets/admin/src/plugins/css/light/table/datatable/dt-global_style-2.css') }}">
    <link rel="stylesheet" type="text/css"
        href="{{ asset('assets/admin/src/plugins/css/light/table/datatable/custom_dt_custom-2.css') }}">

    <link rel="stylesheet" type="text/css"
        href="{{ asset('assets/admin/src/plugins/css/dark/table/datatable/dt-global_style-2.css') }}">
    <link rel="stylesheet" type="text/css"
        href="{{ asset('assets/admin/src/plugins/css/dark/table/datatable/custom_dt_custom-2.css') }}">
@endsection
@section('title', $page_title)

@section('content')
<div class="layout-px-spacing">
	<div class="middle-content container-xxl p-0">
		<!-- BREADCRUMB -->
		<div class="page-meta">
			<nav class="breadcrumb-style-one" aria-label="breadcrumb">
				<ol class="breadcrumb">
					<li class="breadcrumb-item"><a href="/">Home</a></li>
					<li class="breadcrumb-item active" aria-current="page">Recharge Card Pin Rate</li>
				</ol>
			</nav>
		</div>
		<!-- /BREADCRUMB -->
		<div class="row">
        	<div id="flStackForm" class="col-lg-12 layout-spacing layout-top-spacing">
        		<div class="statbox widget box box-shadow">
        			<div class="widget-content widget-content-area" style="padding:20px;">
        				<form method="POST" action="{{ route('admin.epin_rates.update', $epin_rate->id) }}">
        					
        					@csrf
        					@method('PUT')
                          <label class="label">Network:</label>
                          <select name="network_id" class="form-control rounded-xs">
                                @foreach ($networks as $network)
                                    <option value="{{ $network->id }}" {{ $epin_rate->network_id == $network->id ? 'selected' : '' }}>
                                        {{ $network->name }} ({{ $network->id }})
                                    </option>
                                @endforeach
                            </select>
                          @error('network_id')
                            <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                        @enderror
                        <br>
                          <label class="label">Amount:</label>
                          <input type="number" class=" form-control rounded-xs " name="amount" value="{{ old('amount', $epin_rate->amount) }}">
                          @error('amount')
                            <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                            @enderror
                        <br>
                          <label class="label">Rate:</label>
                          <input type="number" class=" form-control rounded-xs " name="rate" value="{{ old('rate', $epin_rate->rate) }}" required>
                          @error('rate')
                            <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                        @enderror
                        <br>
        					<button type="submit" class="btn btn-primary"> Update </button>
        				</form>
        			</div>
        		</div>
        	</div>
        </div>
	</div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('assets/admin/js/general.js') }}"></script>
    

@endsection