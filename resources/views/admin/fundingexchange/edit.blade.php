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
					<li class="breadcrumb-item active" aria-current="page">Edit Exchange Rate</li>
				</ol>
			</nav>
		</div>
		<!-- /BREADCRUMB -->
		<div class="row layout-spacing layout-top-spacing" id="cancel-row">
		    
			<div class="col-lg-12">
				<div class="statbox widget box box-shadow">
					<div class="widget-content widget-content-area">
					    <div class="container">
					        <br><br>
                            <h2>Edit Foreign Account</h2>
                        
                            <form action="{{ route('admin.virtual.cad.exchange.rates.update', $rateData->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                            
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label> Currency</label>
                                        <input type="text" name="currency" value="{{ old('currency', $rateData->currency) }}" class="form-control" readonly>
                                    </div>
                            
                                    <div class="col-md-6 mb-3">
                                        <label>Rate</label>
                                        <input type="text" name="rate" value="{{ old('rate', $rateData->rate) }}" class="form-control">
                                    </div>
                            
                                    <div class="col-md-6 mb-3">
                                        <label>Charge (%)</label>
                                        <input type="text" name="fee" value="{{ old('fee', $rateData->fee) }}" class="form-control">
                                    </div>
                            
                                    <div class="col-md-6 mb-3">
                                        <label>Cap (max charge amount)</label>
                                        <input type="number" name="cap" value="{{ old('cap', $rateData->cap ?? 0) }}" class="form-control" placeholder="0 = no cap">
                                        <small style="opacity:.7">The charge will never exceed this. Enter 0 for no cap.</small>
                                    </div>
                            
                                </div>
                            
                                <button type="submit" class="btn btn-primary mt-3"> Update </button>
                            </form>
                            <br><br>
                        </div>
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