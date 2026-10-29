@extends('admin.layouts.app')
@section('style')



    <link href="{{ asset('assets/admin/src/assets/css/light/dashboard/dash_2.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/admin/src/assets/css/dark/dashboard/dash_2.css') }}" rel="stylesheet" type="text/css" />

    <link href="{{ asset('assets/admin/src/assets/css/light/users/user-profile.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/admin/src/assets/css/dark/users/user-profile.css') }}" rel="stylesheet" type="text/css">

    <link href="{{ asset('assets/admin/src/assets/css/light/components/modal.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/admin/src/assets/css/dark/components/modal.css') }}" rel="stylesheet" type="text/css">

{{--
    <link rel="stylesheet" type="text/css"
        href="{{ asset('assets/admin/src/plugins/src/vanillaSelectBox/vanillaSelectBox.css') }}">  --}}
    <link rel="stylesheet" type="text/css"
        href="{{ asset('assets/admin/src/plugins/css/dark/vanillaSelectBox/custom-vanillaSelectBox.css') }}">
    <link rel="stylesheet" type="text/css"
        href="{{ asset('assets/admin/src/plugins/css/light/vanillaSelectBox/custom-vanillaSelectBox.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/assets/css/light/forms/switches-2.css') }}">



    <link rel="stylesheet" type="text/css"
        href="{{ asset('assets/admin/src/plugins/css/light/table/datatable/dt-global_style-2.css') }}">
    <link rel="stylesheet" type="text/css"
        href="{{ asset('assets/admin/src/plugins/css/light/table/datatable/custom_dt_custom-2.css') }}">

    <link rel="stylesheet" type="text/css"
        href="{{ asset('assets/admin/src/plugins/css/dark/table/datatable/dt-global_style-2.css') }}">
    <link rel="stylesheet" type="text/css"
        href="{{ asset('assets/admin/src/plugins/css/dark/table/datatable/custom_dt_custom-2.css') }}">


        <link href="{{ asset('assets/admin/src/plugins/src/flatpickr/flatpickr.css') }}" rel="stylesheet" type="text/css">
        <link href="{{ asset('assets/admin/src/assets/css/light/scrollspyNav.css') }}" rel="stylesheet" type="text/css">
        <link href="{{ asset('assets/admin/src/plugins/css/light/flatpickr/custom-flatpickr.css') }}" rel="stylesheet" type="text/css">
        <link href="{{ asset('assets/admin/src/assets/css/dark/scrollspyNav.css') }}" rel="stylesheet" type="text/css">
        <link href="{{ asset('assets/admin/src/plugins/css/dark/flatpickr/custom-flatpickr.css') }}" rel="stylesheet" type="text/css">


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
					<li class="breadcrumb-item active" aria-current="page">Monthly Maintenance</li>
				</ol>
			</nav>
		</div>
		<!-- /BREADCRUMB -->
		<div class="row layout-spacing layout-top-spacing" id="cancel-row">
			<div class="col-lg-12">
						<div class="row justify-content-center">
                            <div class="col-md-6">
                                <div class="card shadow-lg border-0 rounded-4">
                                    <div class="card-header text-center py-3">
                                        <h5 class="mb-0 d-flex align-items-center justify-content-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" 
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-repeat-dollar"><polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path><path d="M12 13c0-1.1.9-2 2-2h0a2 2 0 0 1 0 4h-1v1"></path>
                                            </svg>
                                            Debit Monthly Maintenance
                                        </h5>
                                    </div>
                    
                                    <div class="card-body p-4">
                                        @can('debit_maintenance')
                                        <form method="POST" action="{{ route('admin.maintenance.debit') }}">
                                            @csrf
                    
                                            <div class="mb-3">
                                                <label for="amount" class="form-label fw-semibold">
                                                    Maintenance Amount (₦)
                                                </label>
                                                <input 
                                                    type="number" 
                                                    class="form-control form-control-lg rounded-3" 
                                                    id="amount" 
                                                    name="amount" 
                                                    step="0.01" 
                                                    placeholder="Enter amount to debit (₦)" 
                                                    required>
                                            </div>
                                            
                                            <div class="row -mb2">
                                                <label class="col-form-label col-md-3">Alert</label>
                                                <div class="col-md-9">
                                                    <div class="switch form-switch-custom switch-inline form-switch-primary form-switch-custom inner-label-toggle show">
                                                        <div class="dw-switch-wrap">
                                                            <span class="switch-chk-label label-left">No</span>
                                                            <input class="switch-input" name="alert" id="alert" type="checkbox" role="switch"
                                                                onchange="this.checked ? this.closest('.inner-label-toggle').classList.add('show') : this.closest('.inner-label-toggle').classList.remove('show')" checked>
                                                            <span class="switch-chk-label label-right">Yes</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                    
                                            <div class="d-grid mt-4">
                                                <button type="submit" class="btn btn-primary btn-lg rounded-3 shadow-sm">
                                                    <i class="bi bi-cash-stack me-1"></i> Debit All Accounts
                                                </button>
                                            </div>
                                        </form>
                                        @endcan
                                    </div>
                    
                                    <div class="card-footer text-muted text-center small">
                                        This action will debit all user accounts with the specified amount.
                                        <br>
                                        <span class="text-danger fw-semibold">Use carefully.</span>
                                    </div>
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