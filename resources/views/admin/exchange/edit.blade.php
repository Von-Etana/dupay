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
                            <h2>Edit Exchange Rate &mdash; {{ $rateData->currency }}</h2>
                        
                            <form action="{{ route('admin.exchange.rates.update', $rateData->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                            
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label> Currency</label>
                                        <input type="text" name="currency" value="{{ old('currency', $rateData->currency) }}" class="form-control" readonly>
                                    </div>
                            
                                    {{-- The stored `rate` is a MULTIPLIER (amount in FROM x rate =
                                         amount in TO), which for NGN-USD is 0.00061728. Showing that
                                         raw value with a bare "Rate" label is what led to 1620 being
                                         typed here twice, each time taking all currency conversion
                                         offline. Pre-fill the figure that was actually quoted and
                                         spell out what the field expects. --}}
                                    @php
                                        [$fromCur, $toCur] = array_pad(explode('-', $rateData->currency), 2, '');
                                    @endphp
                                    <div class="col-md-6 mb-3">
                                        <label>Rate</label>
                                        <input type="text" name="rate"
                                               value="{{ old('rate', $rateData->quoted_rate ?? $rateData->rate) }}"
                                               class="form-control">
                                        <small style="opacity:.75; display:block; margin-top:4px; line-height:1.5">
                                            Enter the rate the way you say it out loud — e.g.
                                            <strong>1620</strong> for &ldquo;1,620 {{ $fromCur }} to the {{ $toCur }}&rdquo;,
                                            or <strong>0.82</strong> for &ldquo;1 {{ $fromCur }} = 0.82 {{ $toCur }}&rdquo;.
                                            Either way round is accepted: the system works out which you meant
                                            and stores the correct multiplier.
                                            <br>
                                            Currently: <strong>{{ $rateData->human_quote }}</strong>
                                            <span style="opacity:.7">(stored multiplier {{ $rateData->rate }})</span>
                                        </small>
                                    </div>
                            
                                    <div class="col-md-6 mb-3">
                                        <label>Charge (%)</label>
                                        <input type="number" step="any" min="0" name="fee" value="{{ old('fee', $rateData->fee) }}" class="form-control">
                                    </div>
                            
                                    <div class="col-md-6 mb-3">
                                        <label>Cap (max charge amount)</label>
                                        <input type="number" step="any" min="0" name="cap" value="{{ old('cap', $rateData->cap ?? 0) }}" class="form-control" placeholder="0 = no cap">
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