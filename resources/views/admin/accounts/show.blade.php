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
					<li class="breadcrumb-item active" aria-current="page">Foreign Account Request Details</li>
				</ol>
			</nav>
		</div>
		<!-- /BREADCRUMB -->
		<div class="row layout-spacing layout-top-spacing" id="cancel-row">
		    
			<div class="col-xl-6 col-lg-12 col-md-12 col-sm-12 layout-top-spacing">
				<div class="statbox widget">
					<div class="">
					    <div class="">
					        <br>
    						<h5>Foreign Account #{{ $account->id }}</h5>
    
                            @php
                                // Decode bank_data if JSON
                                $bankData = is_string($account->bank_data)
                                    ? json_decode($account->bank_data, true)
                                    : (is_array($account->bank_data) ? $account->bank_data : []);
                            
                                // Decode bank_address if JSON
                                $bankAddress = is_string($account->bank_address)
                                    ? json_decode($account->bank_address, true)
                                    : (is_array($account->bank_address) ? $account->bank_address : []);
                            @endphp
                            
                            <p><strong>User:</strong> 
                                {{ optional($account->user?->profile)->first_name ?? '' }}
                                {{ optional($account->user?->profile)->last_name ?? 'N/A' }}
                            </p>
                            
                            <p><strong>Account ID:</strong> {{ $account->account_id ?? 'N/A' }}</p>
                            <p><strong>Holder ID:</strong> {{ $account->holder_id ?? 'N/A' }}</p>
                            <p><strong>Account Number:</strong> {{ $account->account_number ?? 'N/A' }}</p>
                            <p><strong>Account Name:</strong> {{ $account->account_name ?? 'N/A' }}</p>
                            <p><strong>Bank Name:</strong> {{ $account->bank_name ?? 'N/A' }}</p>
                            <p><strong>Bank Code:</strong> {{ $account->bank_code ?? 'N/A' }}</p>
                            <p><strong>Routing Number:</strong> {{ $account->routing_number ?? 'N/A' }}</p>
                            <p><strong>Currency:</strong> {{ $account->currency ?? 'N/A' }}</p>
                            <p><strong>Balance:</strong> @if($account->currency == 'USD')
                                                    &#36;{{ number_format($account->user?->usd_balance ?? 0, 2) }}
                                                    @elseif($account->currency == 'EUR')
                                                    &#8364;{{ number_format($account->user?->eur_balance ?? 0, 2) }}
                                                    @else
                                                    UNKNOWN
                                                    @endif</p>
                            
                            {{-- 🏦 Bank Address --}}
                            @if(!empty($bankAddress))
                                <h6><strong>Bank Address:</strong></h6>
                                <ul>
                                    <li><strong>Address Line 1:</strong> {{ $bankAddress['line1'] ?? 'N/A' }}</li>
                                    <li><strong>Address Line 2:</strong> {{ $bankAddress['line2'] ?? 'N/A' }}</li>
                                    <li><strong>City:</strong> {{ $bankAddress['city'] ?? 'N/A' }}</li>
                                    <li><strong>State:</strong> {{ $bankAddress['state'] ?? 'N/A' }}</li>
                                    <li><strong>Country:</strong> {{ $bankAddress['country'] ?? 'N/A' }}</li>
                                    <li><strong>Postal Code:</strong> {{ $bankAddress['postal_code'] ?? 'N/A' }}</li>
                                </ul>
                            @else
                                <p><strong>Bank Address:</strong> N/A</p>
                            @endif
                            
                            {{--
                            @if(!empty($bankData))
                                <h6><strong>Bank Data:</strong></h6>
                                <ul>
                                    @foreach($bankData as $key => $value)
                                        <li>
                                            <strong>{{ ucwords(str_replace('_', ' ', $key)) }}:</strong>
                                            @if(is_array($value))
                                                {{ json_encode($value) }}
                                            @else
                                                {{ $value }}
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                            --}}
                            
                            <p><strong>Status:</strong>
                                <span class="badge badge-{{ $account->status == 'inactive' ? 'warning' : 'success' }}">
                                    {{ ucfirst($account->status ?? 'N/A') }}
                                </span>
                            </p>

                            <br>
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