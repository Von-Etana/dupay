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
					<li class="breadcrumb-item active" aria-current="page">Deposit Fee</li>
				</ol>
			</nav>
		</div>
		<!-- /BREADCRUMB -->
		<div class="row">
        	<div id="flStackForm" class="col-lg-12 layout-spacing layout-top-spacing">
        		<div class="statbox widget box box-shadow">
        			<div class="widget-content widget-content-area" style="padding:20px;">
        				<form method="POST" action="{{ route('admin.deposit.fee.update', $fee->id) }}">
        					
        					@csrf
        					@method('PUT')
        					<div>
                                <label class="label">Minimum Amount:</label>
                                <input type="text" class=" form-control rounded-xs " name="minimum_amount" value="{{ old('minimum_amount', $fee->minimum_amount) }}">
                                @error('minimum_amount')
                                    <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                            <br>
                            <div>
                                <label class="label">Maximum Amount:</label>
                                <input type="text" class=" form-control rounded-xs " name="maximum_amount" value="{{ old('maximum_amount', $fee->maximum_amount) }}" @if($fee->maximum_amount == 'unlimited') readonly @endif>
                                @error('maximum_amount')
                                    <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                            <br>
                              <label class="label">Fee (%) — percentage of deposit amount:</label>
                              <input type="text" class=" form-control rounded-xs " name="fee" value="{{ old('fee', $fee->fee) }}" placeholder="e.g. 1.5 for 1.5%" required>
                              @error('fee')
                                <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                            @enderror
                            <br>
                            <div>
                                <label class="label">Cap (maximum fee amount):</label>
                                <input type="text" class=" form-control rounded-xs " name="cap" value="{{ old('cap', $fee->cap ?? 0) }}" placeholder="0 = no cap">
                                <p class="text-xs italic mt-1" style="opacity:.7">The fee will never exceed this amount. Enter 0 for no cap.</p>
                                @error('cap')
                                    <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                            <br>
                            <input type="hidden" name="charge_type" value="percentage">
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