@extends('admin.layouts.app')
@section('title', $page_title)
@section('style')

<style>
/* Fix collapsed sections showing as grey bars */
.collapse:not(.show) {
    display: none !important;
}
.collapsing {
    display: block !important;
}

/* Fix negative zero display */
</style>

<link href="{{ asset('assets/admin/src/assets/css/light/dashboard/dash_2.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ asset('assets/admin/src/assets/css/dark/dashboard/dash_2.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ asset('assets/admin/src/assets/css/light/users/user-profile.css') }}" rel="stylesheet" type="text/css">
<link href="{{ asset('assets/admin/src/assets/css/dark/users/user-profile.css') }}" rel="stylesheet" type="text/css">
<link href="{{ asset('assets/admin/src/assets/css/light/components/modal.css') }}" rel="stylesheet" type="text/css">
<link href="{{ asset('assets/admin/src/assets/css/dark/components/modal.css') }}" rel="stylesheet" type="text/css">
{{--
<link rel="stylesheet" type="text/css"
	href="{{ asset('assets/admin/src/plugins/src/vanillaSelectBox/vanillaSelectBox.css') }}">
--}}
<link rel="stylesheet" type="text/css"
	href="{{ asset('assets/admin/src/plugins/css/dark/vanillaSelectBox/custom-vanillaSelectBox.css') }}">
<link rel="stylesheet" type="text/css"
	href="{{ asset('assets/admin/src/plugins/css/light/vanillaSelectBox/custom-vanillaSelectBox.css') }}">
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

<style>
        .sales-dashboard {
            background: #f8f9fa;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-left: 4px solid #007bff;
            overflow: hidden;
            height: 100%;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        .stat-card.primary { border-left-color: #007bff; }
        .stat-card.success { border-left-color: #28a745; }
        .stat-card.danger { border-left-color: #dc3545; }
        .stat-card.warning { border-left-color: #ffc107; }
        .stat-card.info { border-left-color: #17a2b8; }
        .stat-card.dark { border-left-color: #343a40; }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-meta {
            font-size: 0.8rem;
            color: #868e96;
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            border: 1px solid #e9ecef;
        }
        
        .table-custom {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .table-custom thead th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            font-weight: 600;
            padding: 15px 20px;
        }
        .table-custom tbody td {
            padding: 12px 20px;
            border-color: #f8f9fa;
            vertical-align: middle;
        }
        .table-custom tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .period-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-group-custom .btn {
            border-radius: 25px;
            margin: 0 5px;
            padding: 8px 20px;
            font-weight: 600;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        .btn-group-custom .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .network-badge {
            background: #e9ecef;
            color: #495057;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin: 2px;
        }
        
        .loader-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 300px;
            flex-direction: column;
        }
        .loader-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .chart-legend {
            display: flex;
            align-items: center;
        }
        .legend-item {
            display: flex;
            align-items: center;
            margin-right: 15px;
        }
        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 2px;
            margin-right: 5px;
        }
    </style>
@endsection
@section('content')
<div class="layout-px-spacing">
	<div class="middle-content container-xxl p-0">
		<!-- BREADCRUMB -->
		<div class="page-meta">
			<nav class="breadcrumb-style-one" aria-label="breadcrumb">
				<ol class="breadcrumb">
					@foreach ($breadcrumb as $nav)
					@if ($nav['url'] != null)
					<li class="breadcrumb-item"><a href="{{ $nav['url'] }}">{{ $nav['title'] }}</a></li>
					@else
					<li class="breadcrumb-item active" aria-current="page">{{ $nav['title'] }}</li>
					@endif
					@endforeach
				</ol>
			</nav>
		</div>
		<!-- /BREADCRUMB -->
		<div class="row layout-spacing ">
			<!-- Content -->
			<div class="col-xl-4 col-lg-12 col-md-12 col-sm-12 layout-top-spacing">
				<div class="user-profile">
					<div class="widget-content widget-content-area p-3">
						<div class="d-flex justify-content-between">
							<h3 class="">Profile</h3>
							{{--  @can('edit_user')
							<a href="{{ route('admin.user.edit', $data->id) }}" class="mt-2 edit-profile">
								<svg
									xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
									fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
									stroke-linejoin="round" class="feather feather-edit-3">
									<path d="M12 20h9"></path>
									<path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
								</svg>
							</a>
							@endcan  --}}
						</div>
						<div class="text-center user-info">
							<img src="{{ $data->profile->getProfileImage() }}" alt="avatar">
							<p class="">{{ $data->profile->getName() }}</p>
						</div>
						<div class="user-info-list">
							<div class="">
								<ul class="contacts-block list-unstyled">
									<li class="contacts-block__item">
										<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
											viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
											stroke-linecap="round" stroke-linejoin="round"
											class="feather feather-user me-3">
											<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
											<circle cx="12" cy="7" r="4"></circle>
										</svg>
										<a href="javascript:void(0)" class="iSmartInPlace" id="username" data-column="username"
										data-model="profile->username" data-type="text" data-id="{{ $data->id }}" data-url="{{ route('admin.UpdateItemApi',["user", $data->id]) }}" data-title="enter username"> {{ $data->profile->username }}</a>
									</li>
									<li class="contacts-block__item">
										<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
											viewBox="0 0 24 24" fill="none" stroke="currentColor"
											stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
											class="feather feather-mail me-3">
											<path
												d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z">
											</path>
											<polyline points="22,6 12,13 2,6"></polyline>
										</svg>
										<a href="javascript:void(0)" class="iSmartInPlace" id="email" data-column="email"
										data-type="text" data-id="{{ $data->id }}" data-url="{{ route('admin.UpdateItemApi',["user", $data->id]) }}" data-title="enter email">{{ $data->email }}</a>
									</li>
									<li class="contacts-block__item">
										<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
											viewBox="0 0 24 24" fill="none" stroke="currentColor"
											stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
											class="feather feather-phone me-3">
											<path
												d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
											</path>
										</svg>
										<a href="javascript:void(0)" class="iSmartInPlace" id="phone_number" data-column="phone_number"
										data-type="text" data-id="{{ $data->id }}" data-url="{{ route('admin.UpdateItemApi',["user", $data->id]) }}" data-title="enter phone number">{{ $data->phone_number }}</a>
									</li>
									<li class="contacts-block__item">
										<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
											viewBox="0 0 24 24" fill="none" stroke="currentColor"
											stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
											class="feather feather-trending-up me-3">
											<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
											<polyline points="17 6 23 6 23 12"></polyline>
										</svg>
										{{ $data->level->name }}
									</li>
									<li class="contacts-block__item">
										<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
											viewBox="0 0 24 24" fill="none" stroke="currentColor"
											stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
											class="feather feather-watch me-3">
											<circle cx="12" cy="12" r="7"></circle>
											<polyline points="12 9 12 12 13.5 13.5"></polyline>
											<path
												d="M16.51 17.35l-.35 3.83a2 2 0 0 1-2 1.82H9.83a2 2 0 0 1-2-1.82l-.35-3.83m.01-10.7l.35-3.83A2 2 0 0 1 9.83 1h4.35a2 2 0 0 1 2 1.82l.35 3.83">
											</path>
										</svg>
										{{ \Carbon\Carbon::parse($data->login_time)->format('d M Y h:i A') }}
									</li>
									<li class="contacts-block__item">
										<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-user-share me-3">
											<circle cx="6" cy="8" r="3"></circle>
											<path d="M6 11c-2.5 0-4 1.5-4 3v2"></path>
										</svg>
										Ref ID: {{ $data->profile->ref_id }}
									</li>
									<li class="contacts-block__item">
										<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-user-share me-3">
											<circle cx="6" cy="8" r="3"></circle>
											<path d="M6 11c-2.5 0-4 1.5-4 3v2"></path>
											<circle cx="18" cy="8" r="3"></circle>
											<path d="M18 11c2.5 0 4 1.5 4 3v2"></path>
											<line x1="10" y1="8" x2="14" y2="8"></line>
											<polyline points="13 6 15 8 13 10"></polyline>
										</svg>
										Referred By: {{ $data->referrer_name }}
									</li>
								</ul>
								@canany(['ban_user', 'delete_user'])
								<ul class="list-inline mt-4">
									@can('ban_user')
									@if ($data->ban_status == '0')
									<li class="list-inline-item mb-0">
										<a class="btn btn-warning  mb-2 me-4" data-bs-toggle="modal"
											data-bs-target="#BlockModal{{ $data->profile->username ?? $data->profile->ref_id }}">
											<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
												viewBox="0 0 24 24" fill="none" stroke="currentColor"
												stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
												class="feather feather-lock">
												<rect x="3" y="11" width="18"
													height="11" rx="2" ry="2"></rect>
												<path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
											</svg>
											<span class="btn-text-inner">Ban</span>
										</a>
									</li>
									@else
									<li class="list-inline-item mb-0">
										<a class="btn btn-success  mb-2 me-4" data-bs-toggle="modal"
											data-bs-target="#BlockModal{{ $data->profile->username ?? $data->profile->ref_id }}">
											<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
												viewBox="0 0 24 24" fill="none" stroke="currentColor"
												stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
												class="feather feather-unlock">
												<rect x="3" y="11" width="18"
													height="11" rx="2" ry="2"></rect>
												<path d="M7 11V7a5 5 0 0 1 9.9-1"></path>
											</svg>
											<span class="btn-text-inner">Unban</span>
										</a>
									</li>
									@endif
									@endcan
									@can('delete_user')
									<li class="list-inline-item mb-0">
										<a class="btn btn-danger  mb-2 me-4" data-bs-toggle="modal"
											data-bs-target="#DeleteModal{{ $data->profile->username ?? $data->profile->ref_id }}">
											<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
												viewBox="0 0 24 24" fill="none" stroke="currentColor"
												stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
												class="feather feather-user-x">
												<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
												<circle cx="8.5" cy="7" r="4"></circle>
												<line x1="18" y1="8" x2="23" y2="13">
												</line>
												<line x1="23" y1="8" x2="18" y2="13">
												</line>
											</svg>
											<span class="btn-text-inner">Delete</span>
										</a>
									</li>
									@endcan
								</ul>
									<div class="pt-3">
                                        <form method="POST"
                                            action="{{ route('admin.UserUpdateJsAction', $data->profile->username) }}"
                                            class="d-inline">
                                            <input type="hidden" name="field" value="email_verify">
                                            {{ getOtherFormInput('checkToggle', 'Email', 'email_verify', $data->email_verify == '1' ? 'Verified' : 'Unverify', 'Verified,Unverify', 'required', 'iSmartCheckBox') }}
                                        </form>
                                    </div>
                                    <div class="pt-3">
                                        <form method="POST"
                                            action="{{ route('admin.UserUpdateJsAction', $data->profile->username) }}"
                                            class="d-inline">
                                            <input type="hidden" name="field" value="phone_verify">
                                            {{ getOtherFormInput('checkToggle', 'Phone Number', 'phone_verify', $data->phone_verify == '1' ? 'Verified' : 'Unverify', 'Verified,Unverify', 'required', 'iSmartCheckBox') }}
                                        </form>
                                    </div>
                                    <div class="pt-3">
                                        <form method="POST"
                                            action="{{ route('admin.UserUpdateJsAction', $data->profile->username) }}"
                                            class="d-inline">
                                            <input type="hidden" name="field" value="account_activation">
                                            {{ getOtherFormInput('checkToggle', 'Account', 'account_activation', $data->account_activation == '1' ? 'Activated' : 'Inactive', 'Activated,Inactive', 'required', 'iSmartCheckBox') }}
                                        </form>
                                    </div>
                                    <div class="pt-3">
                                        <form method="POST"
                                            action="{{ route('admin.UserUpdateJsAction', $data->profile->username) }}"
                                            class="d-inline">
                                            <input type="hidden" name="field" value="receive_sms">
                                            {{ getOtherFormInput('checkToggle', 'Receive SMS', 'receive_sms', $data->profile->receive_sms == '1' ? 'On' : 'Off', 'On,Off', 'required', 'iSmartCheckBox') }}
                                        </form>
                                    </div>

                                    @can('ban_user')
									@if ($data->blocked_status == '0')
								<ul class="list-inline">
									<li class="list-inline-item mb-0">
										<a class="btn btn-warning  mb-2 me-4" data-bs-toggle="modal"
											data-bs-target="#BlockUserModal{{ $data->profile->username ?? $data->profile->ref_id }}">
											<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
												viewBox="0 0 24 24" fill="none" stroke="currentColor"
												stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
												class="feather feather-lock">
												<rect x="3" y="11" width="18"
													height="11" rx="2" ry="2"></rect>
												<path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
											</svg>
											<span class="btn-text-inner">BLock</span>
										</a>
									</li>
									@else
									<li class="list-inline-item mb-0">
										<a class="btn btn-success  mb-2 me-4" data-bs-toggle="modal"
											data-bs-target="#BlockUserModal{{ $data->profile->username ?? $data->profile->ref_id }}">
											<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
												viewBox="0 0 24 24" fill="none" stroke="currentColor"
												stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
												class="feather feather-unlock">
												<rect x="3" y="11" width="18"
													height="11" rx="2" ry="2"></rect>
												<path d="M7 11V7a5 5 0 0 1 9.9-1"></path>
											</svg>
											<span class="btn-text-inner">Unblock</span>
										</a>
									</li>
									@endif
									@endcan


								</ul>
								@endcan
							</div>
						</div>
					</div>
				</div>
				<div class="widget widget-wallet-one mt-2">
					<div class="wallet-info text-center mb-3">
						<p class="wallet-title mb-3">Daily Transaction Limit</p>
						<p class="total-amount mb-3"> {{ '₦' . number_format($data->daily_transaction_limit, 2) }}</p>
					</div>
					<div class="wallet-action text-center">
						<a class="btn btn-info collapsed" role="menu" data-bs-toggle="collapse"
							data-bs-target="#UpdateDailyLimit" aria-expanded="false" aria-controls="UpdateDailyLimit">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
								viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
								stroke-linecap="round" stroke-linejoin="round" class="feather feather-plus-circle">
								<circle cx="12" cy="12" r="10"></circle>
								<line x1="12" y1="8" x2="12" y2="16"></line>
								<line x1="8" y1="12" x2="16" y2="12"></line>
							</svg>
							<span class="btn-text-inner">Update</span>
						</a>
					</div>
					<div id="UpdateDailyLimit" class="collapse" aria-labelledby="UpdateDailyLimitLabel"
						data-bs-parent="#toggleAccordion">
						<div class="card-body">
							<form action="{{ route('admin.user.updateDailyTransactionLimit', $data->profile->username) }}" method="POST">
								@csrf
								<div class="form-custom form-label mt-3">
									<label for="amount" class="label">Amount</label>
									<input type="number" name="amount" id="amount" class="form-control rounded-xs" placeholder="Enter Amount" required step="0.01" min="0">
								</div>
								<div class="mb-3">
									<label for="reason" class="label">Note</label>
									<textarea name="reason" id="reason" class="form-control" placeholder="Enter Note" cols="30" rows="3"></textarea>
								</div>
								<div class="row -mb2">
									<label class="col-form-label col-md-3">Action</label>
									<div class="col-md-9">
										<div class="switch form-switch-custom switch-inline form-switch-primary form-switch-custom inner-label-toggle show">
											<div class="dw-switch-wrap">
												<span class="switch-chk-label label-left">Decrease</span>
												<input class="switch-input" name="action" id="action" type="checkbox" role="switch"
													onchange="this.checked ? this.closest('.inner-label-toggle').classList.add('show') : this.closest('.inner-label-toggle').classList.remove('show')" checked>
												<span class="switch-chk-label label-right">Increase</span>
											</div>
										</div>
									</div>
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
								<button type="submit" class="btn btn-success w-100">Update Transaction Limit</button>
							</form>
						</div>
					</div>
				</div>
			</div>
			<div class="col-xl-4 col-lg-6 col-md-6 col-sm-12 col-12">
				@canany(['view_user_wallet_balance', 'edit_user_wallet', 'view_user_transaction', 'reset_user_pin',
				'view_user_bonus_balance', 'view_user_bonus_history'], $data)
				<div class="col-12 layout-top-spacing">
					@canany(['view_user_wallet_balance', 'edit_user_wallet', 'view_user_transaction', 'reset_user_pin'],
					$data)
					<div class="widget widget-wallet-one">
						@can('view_user_wallet_balance', $data)
						<div class="wallet-info text-center mb-3">
							<p class="wallet-title mb-3">Total Balance</p>
							<p class="total-amount mb-3"> {{ getPrice($data->balance) }}</p>
						</div>
						<div class="wallet-action text-center d-flex justify-content-around">
							<span>
								<p class="wallet-title mb-0">Total Funding</p>
								<p class="btn-text-inner mb-3"> {{ getPrice(
									App\Models\Transaction::whereYear('created_at', now()->year)
									->whereMonth('created_at', now()->month)
									->where('type', '1')
									->where('user_id', $data->id)
									->where('status', 'success')
									->where('currency', 'NGN')
									->sum('amount')
									) }}
								</p>
							</span>
							<span>
								<p class="wallet-title mb-0">Total Spent</p>
								<p class="btn-text-inner mb-3"> -{{ getPrice(
									App\Models\Transaction::whereYear('created_at', now()->year)
									->whereMonth('created_at', now()->month)
									->where('type', '0')
									->where('user_id', $data->id)
									->where('status', 'success')
									->where('currency', 'NGN')
									->sum('amount')
									) }}
								</p>
							</span>
						</div>
						@endcan
						<div class="wallet-action text-center d-flex justify-content-around">
							@can('edit_user_wallet', $data)
							<a class="btn btn-info collapsed" role="menu" data-bs-toggle="collapse"
								data-bs-target="#TopupWallet" aria-expanded="false" aria-controls="TopupWallet">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
									viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
									stroke-linecap="round" stroke-linejoin="round" class="feather feather-plus-circle">
									<circle cx="12" cy="12" r="10"></circle>
									<line x1="12" y1="8" x2="12" y2="16"></line>
									<line x1="8" y1="12" x2="16" y2="12"></line>
								</svg>
								<span class="btn-text-inner">Topup</span>
							</a>
							@endcan
							@can('view_user_transaction', $data)
							<a class="btn btn-success"
								href="{{ route('admin.showTransaction', [$data->profile->username ?? $data->profile->ref_id, 1]) }}">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
									viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
									stroke-linecap="round" stroke-linejoin="round" class="feather feather-trello">
									<rect x="3" y="3" width="18" height="18" rx="2"
										ry="2"></rect>
									<rect x="7" y="7" width="3" height="9"></rect>
									<rect x="14" y="7" width="3" height="5"></rect>
								</svg>
								<span class="btn-text-inner">Summary</span>
							</a>
							@endcan
						</div>
						@can('edit_user_wallet', $data)
						<div id="TopupWallet" class="collapse" aria-labelledby="TopupWalletLabel"
							data-bs-parent="#toggleAccordion">
							<div class="card-body">
								@include('admin.user.wallet_action', [
								'data' => $data,
								'wallet_id' => 1,
								])
							</div>
						</div>
						@endcan
						@can('reset_user_pin', $data)
						<hr>
						<a href="#" data-bs-toggle="modal"
							data-bs-target="#TransactionPinModal{{ $data->profile->username ?? $data->profile->ref_id }}"
							class="btn btn-secondary w-100 mt-3">Reset Transaction Pin</a>
						@endcan
						<hr>
						<a href="#" data-bs-toggle="modal"
							data-bs-target="#SendNotificationModal{{ $data->profile->username ?? $data->profile->ref_id }}"
							class="btn btn-primary w-100 mt-2">
							<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-bell me-2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
							Send Notification
						</a>
					</div>
					@endcan
					<!-- today balance --->
					@can('view_user_wallet_balance', $data)
					<div class="widget widget-wallet-one mt-2">
						<div class="wallet-info text-center mb-3">
							<p class="wallet-title mb-3">Today Deposit</p>
							<p class="total-amount mb-3"> 
								{{ getPrice(
								App\Models\Transaction::whereDate('created_at', now())
								->where('type', '1')
								->where('user_id', $data->id)
								->where('status', 'success')
								->where('currency', 'NGN')
								->sum('amount')
								) }}
							</p>
						</div>
						<div class="wallet-action text-center d-flex justify-content-around">
							<span>
								<p class="wallet-title mb-0">Total Spent</p>
								<p class="btn-text-inner mb-3"> 
									{{ getPrice(
									App\Models\Transaction::whereDate('created_at', now())
									->where('type', '0')
									->where('user_id', $data->id)
									->where('status', 'success')
									->where('currency', 'NGN')
									->sum('amount')
									) }}
								</p>
							</span>
							<span>
								<p class="wallet-title mb-0">Transaction</p>
								<p class="btn-text-inner mb-3">
									{{ number_format(calTotalTransaction($data, '1', '0', true, 'count')) }}
								</p>
							</span>
						</div>
					</div>
					@endcan
					@canany(['reset_user_password', 'upgrade_user_level'], $data)
					<div class="widget widget-wallet-one mt-2">
						@can('reset_user_password', $data)
						<a href="#" data-bs-toggle="modal"
							data-bs-target="#ResetPassword{{ $data->profile->username ?? $data->profile->ref_id }}"
							class="btn btn-info mt-2 w-100">Reset Password</a>
						@endcan
						@can('change_user_password', $data)
						<button class="btn btn-dark mt-2 collapsed w-100" role="menu" data-bs-toggle="collapse"
							data-bs-target="#ChangeUserPassword" aria-expanded="false"
							aria-controls="ChangeUserPassword">Change Password</button>
						<div id="ChangeUserPassword" class="collapse" aria-labelledby="ChangeUserPasswordLabel"
							data-bs-parent="#toggleAccordion">
							<div class="card-body">
								@include('admin.user.changePassword_action', [
								'data' => $data
								])
							</div>
						</div>
						@endcan
						@can('upgrade_user_level', $data)
						<button class="btn btn-success mt-2 collapsed w-100" role="menu" data-bs-toggle="collapse"
							data-bs-target="#UpgradeUserLevel" aria-expanded="false"
							aria-controls="UpgradeUserLevel">Upgrade Level</button>
						<div id="UpgradeUserLevel" class="collapse" aria-labelledby="UpgradeUserLevelLabel"
							data-bs-parent="#toggleAccordion">
							<div class="card-body">
								@include('admin.user.level_action', [
								'data' => $data,
								'levels' => $levels,
								])
							</div>
						</div>
						@endcan
					</div>
					@endcanany
				</div>
				@endcanany
				<div class="col-12">
				    @can('view_user_usd_balance', $data)
					<div class="widget widget-wallet-one mt-2">
						<div class="wallet-info text-center mb-3">
							<p class="wallet-title mb-3">USD Balance</p>
							<p class="total-amount mb-3"> {{ '$' . number_format($data->usd_balance, 2) }}</p>
						</div>
						<div class="wallet-action text-center d-flex justify-content-around">
							<span>
								<p class="wallet-title mb-0">Total Funding</p>
								<p class="btn-text-inner mb-3"> {{ '$' . number_format(
									App\Models\Transaction::whereYear('created_at', now()->year)
									->whereMonth('created_at', now()->month)
									->where('type', '1')
									->where('user_id', $data->id)
									->where('status', 'success')
									->where('currency', 'USD')
									->sum('amount'), 2
									) }}
								</p>
							</span>
							<span>
								<p class="wallet-title mb-0">Total Spent</p>
								<p class="btn-text-inner mb-3">
									$-{{ number_format(
									App\Models\Transaction::whereYear('created_at', now()->year)
									->whereMonth('created_at', now()->month)
									->where('type', '0')
									->where('user_id', $data->id)
									->where('status', 'success')
									->where('currency', 'USD')
									->sum('amount'), 2
									) }}
								</p>
							</span>
						</div>
						<div class="wallet-action text-center d-flex justify-content-around">
							<a class="btn btn-info collapsed" role="menu" data-bs-toggle="collapse"
								data-bs-target="#TopupUSDWallet" aria-expanded="false" aria-controls="TopupWallet">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
									viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
									stroke-linecap="round" stroke-linejoin="round" class="feather feather-plus-circle">
									<circle cx="12" cy="12" r="10"></circle>
									<line x1="12" y1="8" x2="12" y2="16"></line>
									<line x1="8" y1="12" x2="16" y2="12"></line>
								</svg>
								<span class="btn-text-inner">Topup</span>
							</a>
							@if($data->usdForeignAccount)
							<a class="btn btn-success"
								href="{{ route('admin.foreign.accounts.show', $data->usdForeignAccount->id) }}">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
									viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
									stroke-linecap="round" stroke-linejoin="round" class="feather feather-trello">
									<rect x="3" y="3" width="18" height="18" rx="2"
										ry="2"></rect>
									<rect x="7" y="7" width="3" height="9"></rect>
									<rect x="14" y="7" width="3" height="5"></rect>
								</svg>
								<span class="btn-text-inner">Details</span>
							</a>
							@endif
						</div>
						<div id="TopupUSDWallet" class="collapse" aria-labelledby="TopupUSDWalletLabel"
							data-bs-parent="#toggleAccordion">
							<div class="card-body">
								<form class="currency-topup-form" action="{{ route('admin.user.topup', ['id' => $data->id, 'currency' => 'USD']) }}" method="POST">
									@csrf
									<div class="form-custom form-label mt-3">
										<label for="amount" class="label">Amount</label>
										<input type="number" name="amount" id="amount" min="0.01" step="0.01" class="form-control rounded-xs" placeholder="Enter Amount" required>
									</div>
									<div class="mb-3">
										<label for="reason" class="label">Reason</label>
										<textarea name="reason" id="reason" class="form-control" placeholder="Enter Reason" cols="30" rows="10"></textarea>
									</div>
									<div class="row -mb2">
										<label class="col-form-label col-md-3">Action</label>
										<div class="col-md-9">
											<div class="switch form-switch-custom switch-inline form-switch-primary form-switch-custom inner-label-toggle show">
												<div class="dw-switch-wrap">
													<span class="switch-chk-label label-left">Debit</span>
													<input class="switch-input" name="action" id="action" type="checkbox" role="switch"
														onchange="this.checked ? this.closest('.inner-label-toggle').classList.add('show') : this.closest('.inner-label-toggle').classList.remove('show')" checked>
													<span class="switch-chk-label label-right">Credit</span>
												</div>
											</div>
										</div>
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
									<button type="button" class="btn btn-success w-100 currency-topup-btn">Submit</button>
								</form>
							</div>
						</div>
					</div>
					@endcan
					
					@can('view_today_usd_deposit', $data)
					<div class="widget widget-wallet-one mt-2">
						<div class="wallet-info text-center mb-3">
							<p class="wallet-title mb-3">Today USD Deposit</p>
							<p class="total-amount mb-3"> 
								&#36;{{ number_format(
								App\Models\Transaction::whereDate('created_at', now())
								->where('type', '1')
								->where('user_id', $data->id)
								->where('status', 'success')
								->where('currency', 'USD')
								->sum('amount'), 2
								) }}
							</p>
						</div>
						<div class="wallet-action text-center d-flex justify-content-around">
							<span>
								<p class="wallet-title mb-0">Total USD Spent</p>
								<p class="btn-text-inner mb-3"> 
									&#36;{{ number_format(
									App\Models\Transaction::whereDate('created_at', now())
									->where('type', '0')
									->where('user_id', $data->id)
									->where('status', 'success')
									->where('currency', 'USD')
									->sum('amount'), 2
									) }}
								</p>
							</span>
							<span>
								<p class="wallet-title mb-0">Transaction</p>
								<p class="btn-text-inner mb-3">
									{{ number_format(App\Models\Transaction::whereDate('created_at', now())
									->where('user_id', $data->id)
									->where('status', 'success')
									->where('currency', 'USD')->count()) }}
								</p>
							</span>
						</div>
					</div>
					@endcan
				</div>
			</div>
			@canany(['view_user_commission_balance', 'update_user_commission', 'view_user_commission_history',
			'view_user_refferal'], $data)
			<div class="col-xl-4 col-lg-6 col-md-6 col-sm-12 col-12 layout-top-spacing">
				<div class="widget widget-wallet-one">
					@can('view_user_commission_balance', $data)
					<div class="wallet-info text-center mb-3">
						<p class="wallet-title mb-3">Commission Balance</p>
						<p class="total-amount mb-3"> {{ getPrice($data->profile->commission) }}</p>
					</div>
					<div class="wallet-action text-center d-flex justify-content-around">
						<span>
							<p class="wallet-title mb-0">Total Earning</p>
							<p class="btn-text-inner mb-3"> {{ getPrice(calTotalFunding($data, '3')) }}</p>
						</span>
						<span>
							<p class="wallet-title mb-0">Total Spent</p>
							<p class="btn-text-inner mb-3">
								{{ getPrice(calTotalFunding($data, '3') - $data->profile->commission) }}
							</p>
						</span>
					</div>
					@endcan
					@canany(['update_user_commission', 'view_user_commission_history'], $data)
					<div class="wallet-action text-center d-flex justify-content-around">
						@can('update_user_commission', $data)
						<a class="btn btn-warning collapsed" role="menu" data-bs-toggle="collapse"
							data-bs-target="#TopupCommission" aria-expanded="false" aria-controls="TopupCommission">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
								viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
								stroke-linecap="round" stroke-linejoin="round" class="feather feather-plus-circle">
								<circle cx="12" cy="12" r="10"></circle>
								<line x1="12" y1="8" x2="12" y2="16"></line>
								<line x1="8" y1="12" x2="16" y2="12"></line>
							</svg>
							<span class="btn-text-inner">Topup</span>
						</a>
						@endcan
						@can('view_user_commission_history', $data)
						<a href="{{ route('admin.showTransaction', [$data->profile->username ?? $data->profile->ref_id, 3]) }}" class="btn btn-success">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
								viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
								stroke-linecap="round" stroke-linejoin="round" class="feather feather-trello">
								<rect x="3" y="3" width="18" height="18" rx="2"
									ry="2"></rect>
								<rect x="7" y="7" width="3" height="9"></rect>
								<rect x="14" y="7" width="3" height="5"></rect>
							</svg>
							<span class="btn-text-inner">Summary</span>
						</a>
						@endcan
					</div>
					@can('update_user_commission', $data)
					<div id="TopupCommission" class="collapse" aria-labelledby="TopupCommissionLabel"
						data-bs-parent="#toggleAccordion">
						<div class="card-body">
							@include('admin.user.wallet_action', [
							'data' => $data,
							'wallet_id' => 3,
							])
						</div>
					</div>
					@endcan
					@endcanany
					@can('view_user_refferal', $data)
					<hr>
					<button class="btn btn-secondary w-100 mt-3">Rerrals</button>
					@endcan
				</div>
				<!-- bonus --->
				@canany(['view_user_bonus_balance', 'view_user_bonus_history', 'update_user_bonus'], $data)
				<div class="widget widget-wallet-one mt-2">
					@can('view_user_bonus_balance', $data)
					<div class="wallet-info text-center mb-3">
						<p class="wallet-title mb-3">Bonus Balance</p>
						<p class="total-amount mb-3"> {{ getPrice($data->bonus) }}</p>
					</div>
					<div class="wallet-action text-center d-flex justify-content-around">
						<span>
							<p class="wallet-title mb-0">Total Earning</p>
							<p class="btn-text-inner mb-3"> {{ getPrice(calTotalFunding($data, '2')) }}</p>
						</span>
						<span>
							<p class="wallet-title mb-0">Total Spent</p>
							<p class="btn-text-inner mb-3"> {{ getPrice(calTotalFunding($data, '2') - $data->bonus) }}
							</p>
						</span>
					</div>
					@endcan
					@canany(['update_user_bonus', 'view_user_bonus_history'], $data)
					<div class="wallet-action text-center d-flex justify-content-around">
						@can('update_user_bonus', $data)
						<a class="btn btn-success collapsed" role="menu" data-bs-toggle="collapse"
							data-bs-target="#TopupBonus" aria-expanded="false" aria-controls="TopupBonus">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
								viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
								stroke-linecap="round" stroke-linejoin="round" class="feather feather-plus-circle">
								<circle cx="12" cy="12" r="10"></circle>
								<line x1="12" y1="8" x2="12" y2="16"></line>
								<line x1="8" y1="12" x2="16" y2="12"></line>
							</svg>
							<span class="btn-text-inner">Topup</span>
						</a>
						@endcan
						@can('view_user_bonus_history', $data)
						<a href="{{ route('admin.showTransaction', [$data->profile->username ?? $data->profile->ref_id, 2]) }}" class="btn btn-danger">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
								viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
								stroke-linecap="round" stroke-linejoin="round" class="feather feather-trello">
								<rect x="3" y="3" width="18" height="18" rx="2"
									ry="2"></rect>
								<rect x="7" y="7" width="3" height="9"></rect>
								<rect x="14" y="7" width="3" height="5"></rect>
							</svg>
							<span class="btn-text-inner">Summary</span>
						</a>
						@endcan
					</div>
					@endcan
					@can('update_user_bonus', $data)
					<div id="TopupBonus" class="collapse" aria-labelledby="TopupBonusLabel"
						data-bs-parent="#toggleAccordion">
						<div class="card-body">
							@include('admin.user.wallet_action', [
							'data' => $data,
							'wallet_id' => 2,
							])
						</div>
					</div>
					@endcan
				</div>
				@endcanany
				@can('view_eur_balance', $data)
				<div class="widget widget-wallet-one mt-lg-4">
					<div class="wallet-info text-center mb-3">
						<p class="wallet-title mb-3">EUR Balance</p>
						<p class="total-amount mb-3"> &#8364;{{ number_format($data->eur_balance, 2) }}</p>
					</div>
					<div class="wallet-action text-center d-flex justify-content-around">
						<span>
							<p class="wallet-title mb-0">Total Funding</p>
							<p class="btn-text-inner mb-3"> &#8364;{{ number_format(
								App\Models\Transaction::whereYear('created_at', now()->year)
								->whereMonth('created_at', now()->month)
								->where('type', '1')
								->where('user_id', $data->id)
								->where('status', 'success')
								->where('currency', 'EUR')
								->sum('amount'), 2
								) }}
							</p>
						</span>
						<span>
							<p class="wallet-title mb-0">Total Spent</p>
							<p class="btn-text-inner mb-3">
								&#8364;{{ '-' . number_format(
								App\Models\Transaction::whereYear('created_at', now()->year)
								->whereMonth('created_at', now()->month)
								->where('type', '0')
								->where('user_id', $data->id)
								->where('status', 'success')
								->where('currency', 'EUR')
								->sum('amount'), 2
								) }}
							</p>
						</span>
					</div>
					<div class="wallet-action text-center d-flex justify-content-around">
						<a class="btn btn-info collapsed" role="menu" data-bs-toggle="collapse"
							data-bs-target="#TopupEURWallet" aria-expanded="false" aria-controls="TopupEURWallet">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
								viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
								stroke-linecap="round" stroke-linejoin="round" class="feather feather-plus-circle">
								<circle cx="12" cy="12" r="10"></circle>
								<line x1="12" y1="8" x2="12" y2="16"></line>
								<line x1="8" y1="12" x2="16" y2="12"></line>
							</svg>
							<span class="btn-text-inner">Topup</span>
						</a>
						@if($data->eurForeignAccount)
						<a class="btn btn-success"
							href="{{ route('admin.foreign.accounts.show', $data->eurForeignAccount->id) }}">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
								viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
								stroke-linecap="round" stroke-linejoin="round" class="feather feather-trello">
								<rect x="3" y="3" width="18" height="18" rx="2"
									ry="2"></rect>
								<rect x="7" y="7" width="3" height="9"></rect>
								<rect x="14" y="7" width="3" height="5"></rect>
							</svg>
							<span class="btn-text-inner">Details</span>
						</a>
						@endif
					</div>
					<div id="TopupEURWallet" class="collapse" aria-labelledby="TopupEURWalletLabel"
						data-bs-parent="#toggleAccordion">
						<div class="card-body">
								<form class="currency-topup-form" action="{{ route('admin.user.topup', ['id' => $data->id, 'currency' => 'EUR']) }}" method="POST">
								@csrf
								<div class="form-custom form-label mt-3">
									<label for="amount" class="label">Amount</label>
									<input type="number" name="amount" id="amount" min="0.01" step="0.01" class="form-control rounded-xs" placeholder="Enter Amount" required>
								</div>
								<div class="mb-3">
									<label for="reason" class="label">Reason</label>
									<textarea name="reason" id="reason" class="form-control" placeholder="Enter Reason" cols="30" rows="10"></textarea>
								</div>
								<div class="row -mb2">
									<label class="col-form-label col-md-3">Action</label>
									<div class="col-md-9">
										<div class="switch form-switch-custom switch-inline form-switch-primary form-switch-custom inner-label-toggle show">
											<div class="dw-switch-wrap">
												<span class="switch-chk-label label-left">Debit</span>
												<input class="switch-input" name="action" id="action" type="checkbox" role="switch"
													onchange="this.checked ? this.closest('.inner-label-toggle').classList.add('show') : this.closest('.inner-label-toggle').classList.remove('show')" checked>
												<span class="switch-chk-label label-right">Credit</span>
											</div>
										</div>
									</div>
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
								<button type="button" class="btn btn-success w-100 currency-topup-btn">Submit</button>
							</form>
						</div>
					</div>
				</div>
				@endcan
				
				
				<div class="widget widget-wallet-one mt-2">
						<div class="wallet-info text-center mb-3">
							<p class="wallet-title mb-3">Today EUR Deposit</p>
							<p class="total-amount mb-3"> 
								&#8364;{{ number_format(
								App\Models\Transaction::whereDate('created_at', now())
								->where('type', '1')
								->where('user_id', $data->id)
								->where('status', 'success')
								->where('currency', 'EUR')
								->sum('amount'), 2
								) }}
							</p>
						</div>
						<div class="wallet-action text-center d-flex justify-content-around">
							<span>
								<p class="wallet-title mb-0">Total EUR Spent</p>
								<p class="btn-text-inner mb-3"> 
									&#8364;{{ number_format(
									App\Models\Transaction::whereDate('created_at', now())
									->where('type', '0')
									->where('user_id', $data->id)
									->where('status', 'success')
									->where('currency', 'EUR')
									->sum('amount'), 2
									) }}
								</p>
							</span>
							<span>
								<p class="wallet-title mb-0">Transaction</p>
								<p class="btn-text-inner mb-3">
									{{ number_format(App\Models\Transaction::whereDate('created_at', now())
									->where('user_id', $data->id)
									->where('status', 'success')
									->where('currency', 'EUR')->count()) }}
								</p>
							</span>
						</div>
					</div>
				
			</div>
			@endcanany
		</div>
		<!-- chart-->
		@can('view_user_refferals', $data)
		<div class="row layout-top-spacing">
			<div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
				<div class="card mt-4">
					<div class="card-header">
						<h5>Referred Users</h5>
					</div>
					<div class="card-body">
						@if($referrals->count() > 0)
						<div class="table-responsive">
							<table id="referralsTable" class="table table-bordered table-striped">
								<thead>
									<tr>
										<th>#</th>
										<th>Full Name</th>
										<th>Username</th>
										<th>Email</th>
										<th>Phone</th>
										<th>Date Joined</th>
									</tr>
								</thead>
								<tbody>
									@foreach($referrals as $key => $ref)
									<tr>
										<td>{{ $key + 1 }}</td>
										<td>{{ $ref->profile->first_name ?? '' }} {{ $ref->profile->last_name ?? '' }}</td>
										<td>{{ $ref->profile->username ?? '' }}</td>
										<td>{{ $ref->email }}</td>
										<td>{{ $ref->phone_number }}</td>
										<td>{{ $ref->created_at->format('d M, Y') }}</td>
									</tr>
									@endforeach
								</tbody>
							</table>
						</div>
						@else
						<p class="text-muted">This user has not referred anyone yet.</p>
						@endif
					</div>
				</div>
			</div>
			<div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
				<div class="card">
					<div class="card-body">
						<h5 class="card-title">
							Sales &amp; Accounting
						</h5>
						<form id="salesCheckForm" method="POST" action="{{ route('admin.salesCalculate') }}">
							@csrf
							<div class="row">
								<div class="col-md-6 mb-3">
									<label class="form-label">Start Date</label>
									<input name="start_date" id="dateTimeFlatpickr"  class="form-control flatpickr flatpickr-input datepicker-default start_date" type="text" placeholder="Select Date.." readonly="readonly">
								</div>
								<div class="col-md-6 mb-3">
									<label class="form-label">End Date</label>
									<input name="end_date" id="dateTimeFlatpickr"  class="form-control flatpickr flatpickr-input datepicker-default end_date" type="text" placeholder="Select Date.." readonly="readonly">
								</div>
								<input type="hidden" name="user_id" value="{{ $data->id }}">
								<div class="text-center">
									<button type="submit" class="btn btn-primary submitRequest" id="calculate">
									<span class="btn-label">
									<i class="fa fa-calculator"></i>
									</span>
									Calculate
									</button>
								</div>
							</div>
						</form>
					</div>
				</div>
				<!-- Results Output -->
                <div id="SalesResultOutput">
                    <div class="loader-container">
                        <div class="loader-spinner"></div>
                        <p class="mt-3 text-muted">Loading sales analytics data...</p>
                    </div>
                </div>
				{{--  
				<div class="widget widget-chart-one">
					<div class="widget-heading">
						<h5 class="">Summary</h5>
						<div class="task-action">
							<div class="dropdown">
								<a class="dropdown-toggle" href="#" role="button" id="renvenue"
									data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
										viewbox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
										stroke-linecap="round" stroke-linejoin="round"
										class="feather feather-more-horizontal">
										<circle cx="12" cy="12" r="1"></circle>
										<circle cx="19" cy="12" r="1"></circle>
										<circle cx="5" cy="12" r="1"></circle>
									</svg>
								</a>
								<div class="dropdown-menu left" aria-labelledby="renvenue"
									style="will-change: transform;">
									<a class="dropdown-item" href="javascript:void(0);">Weekly</a>
									<a class="dropdown-item" href="javascript:void(0);">Monthly</a>
									<a class="dropdown-item" href="javascript:void(0);">Yearly</a>
								</div>
							</div>
						</div>
					</div>
					<div class="widget-content">
						<div id="iSmartMonthlyChart"></div>
					</div>
				</div>
				--}}
			</div>
			{{--  
			<div class="col-xl-4 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
				<div class="widget widget-chart-two">
					<div class="widget-heading">
						<h5 class="">Sales by Category</h5>
					</div>
					<div class="widget-content">
						<div id="chart-2" class=""></div>
					</div>
				</div>
			</div>
			--}}
		</div>
		@endcan
		<!-- transaction--->
		<div class="row">
			<div class="col-lg-12">
				<div class="widget-content widget-content-area">
					<table id="item_table" class="table style-3 dt-table-hover">
						<thead>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<!--modal---->
		@can('reset_user_pin', $data)
		<div class="modal fade" id="TransactionPinModal{{ $data->profile->username ?? $data->profile->ref_id }}"
			tabindex="-1" role="dialog" aria-hidden="true">
			<div class="modal-dialog modal-sm">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">Reset Pin</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal">
						</button>
					</div>
					<div class="modal-body">Are you sure you want to do this?</div>
					<form method="post"
						action="{{ route('admin.AdminResetUserTransactionPin', $data->profile->username ?? $data->profile->ref_id) }}"
						class="form-inline">
						<div class="modal-footer">
							{!! csrf_field() !!}
							<button type="button" class="btn" data-bs-dismiss="modal">Close</button>
							<button type="submit" class="btn btn-primary">Yes
							Reset Pin</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		@endcan
		@can('delete_user')
		<div class="modal fade" id="DeleteModal{{ $data->profile->username ?? $data->profile->ref_id }}"
			tabindex="-1" role="dialog" aria-hidden="true">
			<div class="modal-dialog modal-sm">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">Delete</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal">
						</button>
					</div>
					<div class="modal-body">Are you sure you want to do this?</div>
					<form method="post"
						action="{{ route('admin.AdminDeleteUser', $data->profile->username ?? $data->profile->ref_id) }}"
						class="form-inline">
						<div class="modal-footer">
							{!! csrf_field() !!}
							@method('delete')
							<button type="button" class="btn" data-bs-dismiss="modal">Close</button>
							<button type="submit" class="btn btn-primary">Yes
							Delete</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		@endcan
		<!-- ban and unban user-->
		@can('ban_user')
		<div class="modal  fade animated rotateInDownLeft"
			id="BlockModal{{ $data->profile->username ?? $data->profile->ref_id }}" tabindex="-1" role="dialog"
			aria-hidden="true">
			<div class="modal-dialog modal-sm">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">Block/Unblock User</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal">
						</button>
					</div>
					<div class="modal-body">Are you sure you want to do this?</div>
					<form method="post"
						action="{{ route('admin.AdminBanUnbanUser', $data->profile->username ?? $data->profile->ref_id) }}"
						class="form-inline">
						<div class="modal-footer">
							{!! csrf_field() !!}
							<button type="button" class="btn" data-bs-dismiss="modal"><i
								class="flaticon-cancel-12"></i> Discard</button>
							<button type="submit" class="btn btn-primary">Yes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		@endcan
		@can('reset_user_password', $data)
		<div class="modal fade" id="ResetPassword{{ $data->profile->username ?? $data->profile->ref_id }}"
			tabindex="-1" role="dialog" aria-hidden="true">
			<div class="modal-dialog modal-sm">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">Reset Password</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal">
						</button>
					</div>
					<div class="modal-body">Are you sure you want to do this?</div>
					<form method="post"
						action="{{ route('admin.AdminResetUserPassword', $data->profile->username ?? $data->profile->ref_id) }}"
						class="form-inline">
						<div class="modal-footer">
							{!! csrf_field() !!}
							<button type="button" class="btn" data-bs-dismiss="modal">Close</button>
							<button type="submit" class="btn btn-primary">Yes
							Reset Password</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		@endcan
		@can('ban_user')
		<div class="modal  fade animated rotateInDownLeft"
			id="BlockUserModal{{ $data->profile->username ?? $data->profile->ref_id }}" tabindex="-1" role="dialog"
			aria-hidden="true">
			<div class="modal-dialog modal-sm">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">Block/Unblock User</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal">
						</button>
					</div>
					<div class="modal-body">Are you sure you want to do this?</div>
					<form method="post"
						action="{{ route('admin.AdminBlockUnblockUser', $data->profile->username ?? $data->profile->ref_id) }}"
						class="form-inline">
						<div class="modal-footer">
							{!! csrf_field() !!}
							<button type="button" class="btn" data-bs-dismiss="modal"><i
								class="flaticon-cancel-12"></i> Discard</button>
							<button type="submit" class="btn btn-primary">Yes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		@endcan
	</div>
</div>

<div class="modal fade" id="adminPinModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Confirm Admin PIN</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="password" id="adminPinInput" class="form-control" placeholder="Enter PIN">
                <small class="text-danger d-none" id="pinError"></small>
            </div>

            <div class="modal-footer">
                <!--<button class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>-->
                <button class="btn btn-primary" id="confirmPinBtn">Confirm</button>
            </div>

        </div>
    </div>
</div>

<!-- USD/EUR PIN Modal -->
<div class="modal fade" id="currencyPinModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Confirm Admin PIN</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Enter PIN</label>
                    <input type="password" id="currencyPinInput" class="form-control" autocomplete="off">
                    <small class="text-danger d-none" id="currencyPinError">Invalid PIN</small>
                </div>
            </div>

            <div class="modal-footer">
                <!--<button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>-->
                <button type="button" class="btn btn-primary" id="currencyPinConfirmBtn">Confirm</button>
            </div>

        </div>
    </div>
</div>



{{-- Send Custom Notification Modal --}}
<div class="modal fade" id="SendNotificationModal{{ $data->profile->username ?? $data->profile->ref_id }}"
    tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-bell me-2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                    Send Notification to {{ $data->profile->getName() }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.AdminSendUserNotification', $data->profile->username ?? $data->profile->ref_id) }}">
                @csrf
                <div class="modal-body">
                    @if(!$data->fcmToken)
                    <div class="alert alert-warning">
                        <strong>Note:</strong> This user has no device token. The notification may not be delivered if they haven't logged in recently.
                    </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label fw-bold">Notification Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control"
                            placeholder="e.g. Account Update, Special Offer..."
                            required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Message <span class="text-danger">*</span></label>
                        <textarea name="message" class="form-control" rows="4"
                            placeholder="Type your message to {{ $data->profile->getName() }}..."
                            required></textarea>
                    </div>
                    <small class="text-muted">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-info me-1"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                        This notification will be sent directly to {{ $data->profile->getName() }}'s mobile device.
                    </small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-send me-1"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                        Send Notification
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
@section('scripts')
<script src="{{ asset('assets/admin/src/plugins/src/apex/apexcharts.min.js') }}"></script>
<script src="{{ asset('assets/admin/src/plugins/src/flatpickr/flatpickr.js') }}"></script>
<script src="{{ asset('assets/admin/src/plugins/src/flatpickr/custom-flatpickr.js') }}"></script>
<script src="{{ asset('assets/admin/js/general.js') }}"></script>
<script>
	$(document).ready(function () {
	    $('#referralsTable').DataTable({
	        pageLength: 10,
	        order: [[0, 'asc']],
	        responsive: true
	    });
	});
</script>
<script>
	(function($) {
	    "use strict";
	    $('.select2').select2();
	
	    var f2 = flatpickr(document.getElementsByClassName('datepicker-default'), {
	        enableTime: true,
	        dateFormat: "Y-m-d H:i",
	    });
	
	
	
	})(jQuery);
</script>

<script>
    let activeForm = null;
    const pinModal = new bootstrap.Modal(document.getElementById('adminPinModal'));

    // When ANY wallet submit button is clicked
    document.querySelectorAll('.wallet-action-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            activeForm = this.closest('form'); // 🔥 remember the correct form
            document.getElementById('adminPinInput').value = '';
            document.getElementById('pinError').classList.add('d-none');
            pinModal.show();
        });
    });

    // When PIN is confirmed
    document.getElementById('confirmPinBtn').addEventListener('click', function () {
        const pin = document.getElementById('adminPinInput').value.trim();
        const errorEl = document.getElementById('pinError');

        if (!pin) {
            errorEl.textContent = 'PIN is required';
            errorEl.classList.remove('d-none');
            return;
        }

        fetch("{{ route('admin.verify.pin') }}", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ pin })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === true) {
                pinModal.hide();
                if (activeForm) {
                    activeForm.requestSubmit();
                }
            } else {
                errorEl.textContent = 'Invalid PIN';
                errorEl.classList.remove('d-none');
            }
        })
        .catch(() => {
            errorEl.textContent = 'PIN verification failed. Please try again.';
            errorEl.classList.remove('d-none');
        });
    });
</script>

<script>
    let activeCurrencyForm = null;
    const currencyPinModalEl = document.getElementById('currencyPinModal');
    const currencyPinModal = new bootstrap.Modal(currencyPinModalEl);

    // Open modal when ANY currency-topup-btn is clicked
    document.querySelectorAll('.currency-topup-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            activeCurrencyForm = this.closest('form');

            if (!activeCurrencyForm) return;

            // Reset PIN input and error
            document.getElementById('currencyPinInput').value = '';
            document.getElementById('currencyPinError').classList.add('d-none');

            // Show PIN modal
            currencyPinModal.show();
        });
    });

    // Confirm PIN
    document.getElementById('currencyPinConfirmBtn').addEventListener('click', function() {
        const pin = document.getElementById('currencyPinInput').value.trim();
        const errorEl = document.getElementById('currencyPinError');

        if (!pin) {
            errorEl.textContent = 'PIN is required';
            errorEl.classList.remove('d-none');
            return;
        }

        // AJAX call to verify PIN
        fetch("{{ route('admin.verify.pin') }}", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ pin })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === true) {
                // PIN correct, submit form
                currencyPinModal.hide();
                if (activeCurrencyForm) {
                    activeCurrencyForm.requestSubmit();
                }
            } else {
                errorEl.textContent = 'Invalid PIN';
                errorEl.classList.remove('d-none');
            }
        })
        .catch(() => {
            errorEl.textContent = 'PIN verification failed. Please try again.';
            errorEl.classList.remove('d-none');
        });
    });
</script>



<script>
document.addEventListener("DOMContentLoaded", function() {
    const toggles = document.querySelectorAll('.js-toggle');

    toggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const field = this.dataset.field;
            const type = this.dataset.type;
            const username = this.dataset.username;
            const action = this.checked ? 'on' : 'off';

            fetch(`/users/update-js/${username}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    field: field,
                    [field]: action,
                    type: type
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success){
                    alert(data.message);
                } else {
                    alert("Error: " + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert("Something went wrong!");
            });
        });
    });
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const toggles = document.querySelectorAll('.js-toggle');

    toggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const field = this.dataset.field;
            const type = this.dataset.type;
            const username = this.dataset.username;
            const action = this.checked ? 'on' : 'off';

            fetch(`/users/update-js/${username}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    field: field,
                    [field]: action,
                    type: type
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success){
                    console.log("Updated Successfully");
                } else {
                    console.error("Error:", data.message);
                }
            })
            .catch(err => {
                console.error("AJAX Error:", err);
            });
        });
    });
});
</script>

<script>
    var Columns = [
        {
            data: "bulk_checkbox",
            searchable: false,
            sortable: false,
            orderable: false,
            className: "checkbox-column dt-no-sorting"
        },

        {{-- Dynamic Transaction Columns --}}
        @foreach ($TransactionColumn as $item)
            @if (!isset($item->view))
                {
                    data: "{{ $item->column_name }}",
                    title: "{{ $item->name }}",
                    sortable: {{ isset($item->model) ? 'false' : 'true' }},
                    searchable: {{ isset($item->model) ? 'false' : 'true' }},
                    orderable: {{ isset($item->model) ? 'false' : 'true' }}
                },
            @endif
        @endforeach

        {
            data: "action",
            title: "Action",
            sortable: false,
            searchable: false,
            orderable: false,
            className: "text-center"
        }
    ];

    $(document).ready(function () {

        // Initialize table
        rederTable("{{ $transactionAjaxUrl }}", Columns);

        // Delete item module
        deleteItem("{{ $deleteTitle }}");

        // Inline editing
        iSmartPlaceEdit();

        // Checkbox status update
        checkboxStatusUpdate();

        // Load analysis
        loadSalesAnalysis(
            'daily',
            "{{ $data->profile->username ?? $data->profile->ref_id }}"
        );

        // Submit sales calculator
        submitSalesCalculator(
            'daily',
            "{{ $data->profile->username ?? $data->profile->ref_id }}"
        );

        // Chart
        // chartList("iSmartMonthlyChart", "{{ route('admin.showTransactionChart', $data->profile->username ?? $data->profile->ref_id) }}");

    });
</script>



<script>
    $(document).ready(function() {
    $('.iSmartCheckBox').on('change', function() {
        const $checkbox = $(this);
        const $form = $checkbox.closest('form');
        const originalState = $checkbox.prop('checked');
        
        // Show loading state
        $checkbox.prop('disabled', true);
        
        const field = $form.find('input[name="field"]').val();
        const formData = new FormData();
        formData.append('field', field);
        formData.append(field, $checkbox.prop('checked') ? 'on' : 'off');
        
        fetch($form.attr('action'), {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.message);
            }
            console.log('Update successful:', data);
            // Optional: Show success message
            showNotification('success', data.message);
        })
        .catch(error => {
            console.error('Update failed:', error);
            // Revert to original state on error
            $checkbox.prop('checked', originalState);
            showNotification('error', 'Update failed: ' + error.message);
        })
        .finally(() => {
            $checkbox.prop('disabled', false);
        });
    });
});

function showNotification(type, message) {
    // Use your preferred notification method
    if (typeof toastr !== 'undefined') {
        toastr[type](message);
    } else {
        alert(message);
    }
}
</script>



<script>
    // Chart instances
    let inflowOutflowChart = null;
    let airtimeChart = null;
    let dataChart = null;
    let conversionChart = null;
    let conversionFlowChart = null;
    let usdFlowChart = null;
    let eurFlowChart = null;

    // Initialize date pickers
    var f2 = flatpickr(document.getElementsByClassName('datepicker-default'), {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        time_24hr: true
    });

    // Load default sales analysis
    loadSalesAnalysis();

    function loadSalesAnalysis(range = 'daily') {
    console.log('Loading sales analysis for range:', range);
    showLoader();
    
    $.ajax({
        url: "{{ route('admin.salesCalculate') }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            range: range,
            user_id: {{ $data->id }} // This will always send the current user's ID
        },
        success: function(response) {
            console.log('AJAX Success Response:', response);
            hideLoader();
            if (response.success) {
                $('#SalesResultOutput').html(generateSalesHTML(response.data, response.period, {{ $data->id }}));
                initializeCharts(response.data.charts_data, response.data.currency_conversion);
            } else {
                showError('Error Loading Data', response.message || 'Unknown error occurred');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', xhr, status, error);
            hideLoader();
            showError('Request Failed', `Status: ${status}, Error: ${error}`);
        }
    });
}

    function submitSalesCalculator() {
        $('#salesCheckForm').on('submit', function(e) {
            e.preventDefault();
            console.log('Form submitted');
            showLoader();
            
            const formData = new FormData(this);
            
            $.ajax({
                url: $(this).attr('action'),
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('Form AJAX Success:', response);
                    hideLoader();
                    if (response.success) {
                        $('#SalesResultOutput').html(generateSalesHTML(response.data, response.period));
                        initializeCharts(response.data.charts_data, response.data.currency_conversion);
                    } else {
                        showError('Calculation Error', response.message || 'Unknown error occurred');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Form AJAX Error:', xhr, status, error);
                    hideLoader();
                    showError('Submission Failed', `Status: ${status}, Error: ${error}`);
                }
            });
        });
    }

    function generateSalesHTML(data, period) {
        console.log('Generating HTML with data:', data);
        
        if (!data) {
            return `
                <div class="empty-state">
                    <i class="fa fa-chart-bar"></i>
                    <h4>No Data Available</h4>
                    <p>No sales data found for the selected period.</p>
                </div>
            `;
        }

        return `
            <div class="row layout-top-spacing">
                <!-- Period Info -->
                <div class="col-12 mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="period-badge">
                            <i class="fa fa-calendar"></i> 
                            ${new Date(period.start).toLocaleDateString()} ${new Date(period.start).toLocaleTimeString()} 
                            - 
                            ${new Date(period.end).toLocaleDateString()} ${new Date(period.end).toLocaleTimeString()}
                        </div>
                    </div>
                </div>

                <!-- Main Financial Cards -->
                ${generateFinancialCards(data)}

                <!-- Foreign Accounts Summary -->
                ${generateForeignAccountsHTML(data.foreign_accounts)}

                <!-- Topups Summary -->
                <div class="col-12 layout-spacing">
                    <div class="table-custom">
                        <div class="widget widget-table-three">
                            <div class="widget-heading">
                                <h5 class="p-4 mb-0"><i class="fa fa-mobile-alt text-primary"></i> Topups & Services Summary</h5>
                            </div>
                            <div class="widget-content p-0">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Service Type</th>
                                                <th>Transactions</th>
                                                <th>Amount</th>
                                                <th>Percentage</th>
                                                <th>Avg. Transaction</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${generateTopupRow('Airtime', data.topups?.airtime, data.topups?.total_amount)}
                                            ${generateTopupRow('Data', data.topups?.data, data.topups?.total_amount)}
                                            ${generateTopupRow('TV Subscription', data.topups?.tv, data.topups?.total_amount)}
                                            ${generateTopupRow('Electricity', data.topups?.electricity, data.topups?.total_amount)}
                                            <tr class="table-primary fw-bold">
                                                <td>TOTAL</td>
                                                <td>${data.topups?.total_count || 0}</td>
                                                <td>₦ ${formatNumber(data.topups?.total_amount || 0)}</td>
                                                <td>100%</td>
                                                <td>₦ ${formatNumber((data.topups?.total_amount || 0) / (data.topups?.total_count || 1))}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="col-12 layout-spacing">
                    <div class="chart-container">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0"><i class="fa fa-chart-line text-primary"></i> Cash Flow Analysis</h5>
                            <div class="chart-legend">
                                <span class="legend-item"><span class="legend-color" style="background:#00E396"></span> Inflow</span>
                                <span class="legend-item ms-3"><span class="legend-color" style="background:#FF4560"></span> Outflow</span>
                            </div>
                        </div>
                        <div id="inflowOutflowChart" style="height: 400px;"></div>
                    </div>
                </div>

                <!-- Foreign Account Flow Charts -->
                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                    <div class="chart-container">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0"><i class="fa fa-dollar-sign text-success"></i> USD Account Flow</h5>
                            <div class="chart-legend">
                                <span class="legend-item"><span class="legend-color" style="background:#00E396"></span> Inflow</span>
                                <span class="legend-item ms-3"><span class="legend-color" style="background:#FF4560"></span> Outflow</span>
                            </div>
                        </div>
                        <div id="usdFlowChart" style="height: 350px;"></div>
                    </div>
                </div>

                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                    <div class="chart-container">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0"><i class="fa fa-euro-sign text-warning"></i> EUR Account Flow</h5>
                            <div class="chart-legend">
                                <span class="legend-item"><span class="legend-color" style="background:#00E396"></span> Inflow</span>
                                <span class="legend-item ms-3"><span class="legend-color" style="background:#FF4560"></span> Outflow</span>
                            </div>
                        </div>
                        <div id="eurFlowChart" style="height: 350px;"></div>
                    </div>
                </div>

                <!-- Network Charts -->
                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                    <div class="chart-container">
                        <h5 class="mb-4"><i class="fa fa-signal text-success"></i> Airtime Topup by Network</h5>
                        <div id="airtimeChart" style="height: 350px;"></div>
                    </div>
                </div>

                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                    <div class="chart-container">
                        <h5 class="mb-4"><i class="fa fa-wifi text-info"></i> Data Topup by Network</h5>
                        <div id="dataChart" style="height: 350px;"></div>
                    </div>
                </div>

                <!-- Currency Conversion Section -->
                ${generateCurrencyConversionHTML(data.currency_conversion)}

                <!-- Additional Cards -->
                ${generateAdditionalCards(data)}
            </div>
        `;
    }

    function generateFinancialCards(data) {
        return `
            <!-- NGN Transactions -->
            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-12 layout-spacing">
                <div class="widget widget-card-four stat-card success">
                    <div class="widget-content">
                        <div class="w-content">
                            <div class="w-info text-center p-4">
                                <h6 class="stat-value text-success">₦ ${formatNumber(data.ngn_deposits?.total_amount || 0)}</h6>
                                <p class="stat-label">NGN Deposits</p>
                                <div class="stat-meta">
                                    <span class="badge bg-light text-dark">${data.ngn_deposits?.total_count || 0} TXNs</span>
                                    <span class="badge bg-light text-dark">₦ ${formatNumber(data.ngn_deposits?.total_charges || 0)} Fees</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-12 layout-spacing">
                <div class="widget widget-card-four stat-card danger">
                    <div class="widget-content">
                        <div class="w-content">
                            <div class="w-info text-center p-4">
                                <h6 class="stat-value text-danger">₦ ${formatNumber(data.ngn_withdrawals?.total_amount || 0)}</h6>
                                <p class="stat-label">NGN Withdrawals</p>
                                <div class="stat-meta">
                                    <span class="badge bg-light text-dark">${data.ngn_withdrawals?.total_count || 0} TXNs</span>
                                    <span class="badge bg-light text-dark">₦ ${formatNumber(data.ngn_withdrawals?.total_charges || 0)} Fees</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Virtual Cards -->
            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-12 layout-spacing">
                <div class="widget widget-card-four stat-card info">
                    <div class="widget-content">
                        <div class="w-content">
                            <div class="w-info text-center p-4">
                                <h6 class="stat-value text-info">₦ ${formatNumber(data.virtual_card_ngn_withdrawals?.total_amount || 0)}</h6>
                                <p class="stat-label">Virtual Card NGN</p>
                                <div class="stat-meta">
                                    <span class="badge bg-light text-dark">${data.virtual_card_ngn_withdrawals?.total_count || 0} TXNs</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-12 layout-spacing">
                <div class="widget widget-card-four stat-card warning">
                    <div class="widget-content">
                        <div class="w-content">
                            <div class="w-info text-center p-4">
                                <h6 class="stat-value text-warning">$ ${formatNumber(data.virtual_card_usd_withdrawals?.total_amount || 0)}</h6>
                                <p class="stat-label">Virtual Card USD</p>
                                <div class="stat-meta">
                                    <span class="badge bg-light text-dark">${data.virtual_card_usd_withdrawals?.total_count || 0} TXNs</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function generateForeignAccountsHTML(foreignData) {
        if (!foreignData || (!foreignData.usd.deposits?.amount && !foreignData.eur.deposits?.amount)) {
            return `
                <div class="col-12 layout-spacing">
                    <div class="table-custom">
                        <div class="widget widget-table-three">
                            <div class="widget-heading">
                                <h5 class="p-4 mb-0"><i class="fa fa-globe text-info"></i> Foreign Accounts Summary</h5>
                            </div>
                            <div class="widget-content p-0">
                                <div class="text-center py-4">
                                    <p class="text-muted">No foreign account activity for the selected period.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        return `
            <div class="col-12 layout-spacing">
                <div class="table-custom">
                    <div class="widget widget-table-three">
                        <div class="widget-heading">
                            <h5 class="p-4 mb-0"><i class="fa fa-globe text-info"></i> Foreign Accounts Summary</h5>
                        </div>
                        <div class="widget-content p-0">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Currency</th>
                                            <th>Type</th>
                                            <th>Transactions</th>
                                            <th>Amount</th>
                                            <th>Charges</th>
                                            <th>Net Flow</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${generateForeignAccountRow('USD', 'Deposits', foreignData.usd.deposits)}
                                        ${generateForeignAccountRow('USD', 'Withdrawals', foreignData.usd.withdrawals)}
                                        <tr class="table-info">
                                            <td><strong>USD</strong></td>
                                            <td><strong>Net Flow</strong></td>
                                            <td></td>
                                            <td><strong>$${formatNumber(foreignData.usd.net_flow)}</strong></td>
                                            <td></td>
                                            <td>
                                                <span class="badge ${foreignData.usd.net_flow >= 0 ? 'bg-success' : 'bg-danger'}">
                                                    ${foreignData.usd.net_flow >= 0 ? 'Positive' : 'Negative'}
                                                </span>
                                            </td>
                                        </tr>
                                        ${generateForeignAccountRow('EUR', 'Deposits', foreignData.eur.deposits)}
                                        ${generateForeignAccountRow('EUR', 'Withdrawals', foreignData.eur.withdrawals)}
                                        <tr class="table-warning">
                                            <td><strong>EUR</strong></td>
                                            <td><strong>Net Flow</strong></td>
                                            <td></td>
                                            <td><strong>€${formatNumber(foreignData.eur.net_flow)}</strong></td>
                                            <td></td>
                                            <td>
                                                <span class="badge ${foreignData.eur.net_flow >= 0 ? 'bg-success' : 'bg-danger'}">
                                                    ${foreignData.eur.net_flow >= 0 ? 'Positive' : 'Negative'}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Foreign Account Cards -->
            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-12 layout-spacing">
                <div class="widget widget-card-four stat-card primary">
                    <div class="widget-content">
                        <div class="w-content">
                            <div class="w-info text-center p-4">
                                <h6 class="stat-value text-primary">$${formatNumber(foreignData.usd.deposits?.amount || 0)}</h6>
                                <p class="stat-label">USD Deposits</p>
                                <div class="stat-meta">
                                    <span class="badge bg-light text-dark">${foreignData.usd.deposits?.count || 0} TXNs</span>
                                    <span class="badge bg-light text-dark">$${formatNumber(foreignData.usd.deposits?.charges || 0)} Fees</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-12 layout-spacing">
                <div class="widget widget-card-four stat-card danger">
                    <div class="widget-content">
                        <div class="w-content">
                            <div class="w-info text-center p-4">
                                <h6 class="stat-value text-danger">$${formatNumber(foreignData.usd.withdrawals?.amount || 0)}</h6>
                                <p class="stat-label">USD Withdrawals</p>
                                <div class="stat-meta">
                                    <span class="badge bg-light text-dark">${foreignData.usd.withdrawals?.count || 0} TXNs</span>
                                    <span class="badge bg-light text-dark">$${formatNumber(foreignData.usd.withdrawals?.charges || 0)} Fees</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-12 layout-spacing">
                <div class="widget widget-card-four stat-card warning">
                    <div class="widget-content">
                        <div class="w-content">
                            <div class="w-info text-center p-4">
                                <h6 class="stat-value text-warning">€${formatNumber(foreignData.eur.deposits?.amount || 0)}</h6>
                                <p class="stat-label">EUR Deposits</p>
                                <div class="stat-meta">
                                    <span class="badge bg-light text-dark">${foreignData.eur.deposits?.count || 0} TXNs</span>
                                    <span class="badge bg-light text-dark">€${formatNumber(foreignData.eur.deposits?.charges || 0)} Fees</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 col-12 layout-spacing">
                <div class="widget widget-card-four stat-card dark">
                    <div class="widget-content">
                        <div class="w-content">
                            <div class="w-info text-center p-4">
                                <h6 class="stat-value text-dark">€${formatNumber(foreignData.eur.withdrawals?.amount || 0)}</h6>
                                <p class="stat-label">EUR Withdrawals</p>
                                <div class="stat-meta">
                                    <span class="badge bg-light text-dark">${foreignData.eur.withdrawals?.count || 0} TXNs</span>
                                    <span class="badge bg-light text-dark">€${formatNumber(foreignData.eur.withdrawals?.charges || 0)} Fees</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function generateForeignAccountRow(currency, type, data) {
        if (!data || !data.amount) {
            return `
                <tr>
                    <td>${currency}</td>
                    <td>${type}</td>
                    <td>0</td>
                    <td>${currency === 'USD' ? '$' : '€'}0.00</td>
                    <td>${currency === 'USD' ? '$' : '€'}0.00</td>
                    <td></td>
                </tr>
            `;
        }

        const symbol = currency === 'USD' ? '$' : '€';
        
        return `
            <tr>
                <td>${currency}</td>
                <td>${type}</td>
                <td>${data.count || 0}</td>
                <td>${symbol}${formatNumber(data.amount)}</td>
                <td>${symbol}${formatNumber(data.charges || 0)}</td>
                <td></td>
            </tr>
        `;
    }

    function generateTopupRow(serviceName, serviceData, totalAmount) {
        if (!serviceData || !serviceData.amount) {
            return `
                <tr>
                    <td>${serviceName}</td>
                    <td>0</td>
                    <td>₦ 0.00</td>
                    <td>0%</td>
                    <td>₦ 0.00</td>
                </tr>
            `;
        }

        const percentage = totalAmount ? ((serviceData.amount / totalAmount) * 100).toFixed(1) : '0';
        const avgTransaction = serviceData.count ? (serviceData.amount / serviceData.count) : 0;
        
        return `
            <tr>
                <td>${serviceName}</td>
                <td>${serviceData.count || 0}</td>
                <td>₦ ${formatNumber(serviceData.amount)}</td>
                <td>${percentage}%</td>
                <td>₦ ${formatNumber(avgTransaction)}</td>
            </tr>
        `;
    }

    function generateCurrencyConversionHTML(conversionData) {
        if (!conversionData || conversionData.total_conversions === 0) {
            return `
                <div class="col-12 layout-spacing">
                    <div class="table-custom">
                        <div class="widget widget-table-three">
                            <div class="widget-heading">
                                <h5 class="p-4 mb-0"><i class="fa fa-exchange-alt text-warning"></i> Currency Conversion Summary</h5>
                            </div>
                            <div class="widget-content p-0">
                                <div class="text-center py-4">
                                    <p class="text-muted">No currency conversion data available for the selected period.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        return `
            <div class="col-12 layout-spacing">
                <div class="table-custom">
                    <div class="widget widget-table-three">
                        <div class="widget-heading">
                            <h5 class="p-4 mb-0"><i class="fa fa-exchange-alt text-warning"></i> Currency Conversion Summary</h5>
                        </div>
                        <div class="widget-content p-0">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Metric</th>
                                            <th>Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Total Conversions</td>
                                            <td><strong>${conversionData.total_conversions}</strong></td>
                                        </tr>
                                        <tr>
                                            <td>Total Conversion Volume</td>
                                            <td><strong>${formatNumber(conversionData.total_volume)}</strong> (across all currencies)</td>
                                        </tr>
                                        <tr>
                                            <td>Total Charges</td>
                                            <td><strong>${formatNumber(conversionData.total_charges)}</strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Currency Conversion Matrix -->
            <div class="col-12 layout-spacing">
                <div class="chart-container">
                    <h5 class="mb-4"><i class="fa fa-project-diagram text-info"></i> Currency Conversion Flow</h5>
                    <div id="conversionFlowChart" style="height: 500px;"></div>
                </div>
            </div>

            <!-- Conversion Matrix Table -->
            <div class="col-12 layout-spacing">
                <div class="table-custom">
                    <div class="widget widget-table-three">
                        <div class="widget-heading">
                            <h5 class="p-4 mb-0"><i class="fa fa-table text-primary"></i> Currency Conversion Matrix</h5>
                        </div>
                        <div class="widget-content p-0">
                            <div class="table-responsive">
                                ${generateConversionMatrixTable(conversionData.conversion_matrix)}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Breakdown by Source Currency -->
            ${generateSourceCurrencyBreakdown(conversionData.breakdown_by_from_currency)}

            <!-- Recent Conversion Pairs -->
            <div class="col-12 layout-spacing">
                <div class="table-custom">
                    <div class="widget widget-table-three">
                        <div class="widget-heading">
                            <h5 class="p-4 mb-0"><i class="fa fa-list text-primary"></i> Recent Conversion Pairs</h5>
                        </div>
                        <div class="widget-content p-0">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>From → To</th>
                                            <th>Amount</th>
                                            <th>Rate</th>
                                            <th>Charge</th>
                                            <th>Reference</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${generateConversionPairsRows(conversionData.conversion_pairs)}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function generateConversionMatrixTable(conversionMatrix) {
        if (!conversionMatrix || Object.keys(conversionMatrix).length === 0) {
            return '<div class="text-center py-4"><p class="text-muted">No conversion matrix data available</p></div>';
        }

        // Get all unique currencies
        const allCurrencies = new Set();
        const fromCurrencies = Object.keys(conversionMatrix);
        
        fromCurrencies.forEach(from => {
            allCurrencies.add(from);
            Object.keys(conversionMatrix[from]).forEach(to => {
                allCurrencies.add(to);
            });
        });

        const currencies = Array.from(allCurrencies).sort();
        
        let tableHTML = `
            <table class="table">
                <thead>
                    <tr>
                        <th>From → To</th>
                        ${currencies.map(currency => `<th class="text-center">${currency}</th>`).join('')}
                    </tr>
                </thead>
                <tbody>
        `;

        currencies.forEach(fromCurrency => {
            tableHTML += `<tr>`;
            tableHTML += `<td><strong>${fromCurrency}</strong></td>`;
            
            currencies.forEach(toCurrency => {
                if (fromCurrency === toCurrency) {
                    tableHTML += `<td class="text-center bg-light">-</td>`;
                } else if (conversionMatrix[fromCurrency] && conversionMatrix[fromCurrency][toCurrency]) {
                    const data = conversionMatrix[fromCurrency][toCurrency];
                    const volume = formatNumber(data.volume);
                    const count = data.count;
                    const avgRate = data.average_rate ? data.average_rate.toFixed(4) : 'N/A';
                    
                    tableHTML += `
                        <td class="text-center bg-success-light">
                            <div class="fw-bold">${volume} ${fromCurrency}</div>
                            <small class="text-muted">${count} conversions</small>
                            <div class="small">Rate: ${avgRate}</div>
                        </td>
                    `;
                } else {
                    tableHTML += `<td class="text-center bg-light">-</td>`;
                }
            });
            
            tableHTML += `</tr>`;
        });

        tableHTML += `</tbody></table>`;
        return tableHTML;
    }

    function generateSourceCurrencyBreakdown(breakdownData) {
        if (!breakdownData || Object.keys(breakdownData).length === 0) {
            return '';
        }

        let html = '';
        
        // Generate cards for each source currency
        for (const [currency, data] of Object.entries(breakdownData)) {
            const currencyClass = getCurrencyColorClass(currency);
            const currencySymbol = getCurrencySymbol(currency);
            
            html += `
                <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12 layout-spacing">
                    <div class="table-custom">
                        <div class="widget widget-table-three">
                            <div class="widget-heading">
                                <h5 class="p-4 mb-0 ${currencyClass}">
                                    <i class="fa fa-arrow-up ${currencyClass}"></i> ${currency} to Other Currencies
                                </h5>
                            </div>
                            <div class="widget-content p-0">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>To Currency</th>
                                                <th>Volume</th>
                                                <th>Conversions</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${generateSourceCurrencyRows(currency, data)}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        return html;
    }

    function generateSourceCurrencyRows(currency, data) {
        if (!data.to_currencies || Object.keys(data.to_currencies).length === 0) {
            return '<tr><td colspan="4" class="text-center">No conversion data</td></tr>';
        }

        let rows = '';
        const totalVolume = data.total_volume;
        
        for (const [toCurrency, conversionData] of Object.entries(data.to_currencies)) {
            const percentage = totalVolume > 0 ? ((conversionData.volume / totalVolume) * 100).toFixed(1) : '0';
            const toCurrencyClass = getCurrencyColorClass(toCurrency);
            
            rows += `
                <tr>
                    <td>
                        <span class="${toCurrencyClass} fw-bold">${toCurrency}</span>
                    </td>
                    <td>
                        <strong>${formatNumber(conversionData.volume)} ${currency}</strong>
                    </td>
                    <td>${conversionData.count}</td>
                    <td>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar ${getCurrencyColorClass(currency)}" 
                                 style="width: ${percentage}%"></div>
                        </div>
                        <small>${percentage}%</small>
                    </td>
                </tr>
            `;
        }

        // Add total row
        rows += `
            <tr class="table-primary fw-bold">
                <td>TOTAL</td>
                <td>${formatNumber(totalVolume)} ${currency}</td>
                <td>${data.count}</td>
                <td>100%</td>
            </tr>
        `;
        
        return rows;
    }

    function generateConversionPairsRows(conversionPairs) {
        if (!conversionPairs || conversionPairs.length === 0) {
            return '<tr><td colspan="6" class="text-center">No conversion pairs available</td></tr>';
        }

        // Show only recent 10 conversions
        const recentPairs = conversionPairs.slice(0, 10);
        
        let rows = '';
        recentPairs.forEach(pair => {
            const date = new Date(pair.date).toLocaleDateString();
            const fromTo = `
                <span class="${getCurrencyColorClass(pair.from_currency)}">${pair.from_currency}</span> 
                → 
                <span class="${getCurrencyColorClass(pair.to_currency)}">${pair.to_currency}</span>
            `;
            const amount = `
                ${formatNumber(pair.from_amount)} ${pair.from_currency} 
                → 
                ${formatNumber(pair.to_amount)} ${pair.to_currency}
            `;
            const rate = pair.exchange_rate > 0 ? `1 ${pair.to_currency} = ${formatNumber(pair.exchange_rate)} ${pair.from_currency}` : 'N/A';
            
            rows += `
                <tr>
                    <td>${date}</td>
                    <td>${fromTo}</td>
                    <td>${amount}</td>
                    <td>${rate}</td>
                    <td>${formatNumber(pair.charge)} ${pair.from_currency}</td>
                    <td><small class="text-muted">${pair.reference}</small></td>
                </tr>
            `;
        });
        
        if (conversionPairs.length > 10) {
            rows += `
                <tr>
                    <td colspan="6" class="text-center text-muted">
                        Showing 10 of ${conversionPairs.length} conversions
                    </td>
                </tr>
            `;
        }
        
        return rows;
    }

    function getCurrencyColorClass(currency) {
        const colors = {
            'NGN': 'text-success',
            'USD': 'text-primary', 
            'EUR': 'text-warning',
            'GBP': 'text-danger'
        };
        return colors[currency] || 'text-secondary';
    }

    function getCurrencySymbol(currency) {
        const symbols = {
            'NGN': '₦',
            'USD': '$',
            'EUR': '€',
            'GBP': '£'
        };
        return symbols[currency] || currency;
    }

    function generateAdditionalCards(data) {
        let html = '';
        
        // Currency Conversion
        if (data.currency_conversion && data.currency_conversion.total_volume > 0) {
            html += `
                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6 col-12 layout-spacing">
                    <div class="widget widget-card-four stat-card primary">
                        <div class="widget-content">
                            <div class="w-content">
                                <div class="w-info text-center p-4">
                                    <h6 class="stat-value text-primary">${formatNumber(data.currency_conversion.total_volume || 0)}</h6>
                                    <p class="stat-label">Currency Conversion</p>
                                    <div class="stat-meta">
                                        <span class="badge bg-light text-dark">${data.currency_conversion.total_conversions || 0} TXNs</span>
                                        <span class="badge bg-light text-dark">${formatNumber(data.currency_conversion.total_charges || 0)} Fees</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Physical Card Withdrawals
        if (data.physical_card_withdrawals && data.physical_card_withdrawals.total_amount > 0) {
            html += `
                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6 col-12 layout-spacing">
                    <div class="widget widget-card-four stat-card dark">
                        <div class="widget-content">
                            <div class="w-content">
                                <div class="w-info text-center p-4">
                                    <h6 class="stat-value text-dark">₦ ${formatNumber(data.physical_card_withdrawals.total_amount || 0)}</h6>
                                    <p class="stat-label">Physical Card</p>
                                    <div class="stat-meta">
                                        <span class="badge bg-light text-dark">${data.physical_card_withdrawals.total_count || 0} TXNs</span>
                                        <span class="badge bg-light text-dark">₦ ${formatNumber(data.physical_card_withdrawals.total_charges || 0)} Fees</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Recharge Card Pins
        if (data.recharge_card_pins && data.recharge_card_pins.total_amount > 0) {
            html += `
                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6 col-12 layout-spacing">
                    <div class="widget widget-card-four stat-card warning">
                        <div class="widget-content">
                            <div class="w-content">
                                <div class="w-info text-center p-4">
                                    <h6 class="stat-value text-warning">₦ ${formatNumber(data.recharge_card_pins.total_amount || 0)}</h6>
                                    <p class="stat-label">Recharge Cards</p>
                                    <div class="stat-meta">
                                        <span class="badge bg-light text-dark">${data.recharge_card_pins.total_count || 0} TXNs</span>
                                        <span class="badge bg-light text-dark">₦ ${formatNumber(data.recharge_card_pins.total_charges || 0)} Fees</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        return html;
    }

    function formatNumber(num) {
        if (num === null || num === undefined) return '0.00';
        const number = parseFloat(num);
        if (isNaN(number)) return '0.00';
        return number.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function showLoader() {
        $('#SalesResultOutput').html(`
            <div class="loader-container">
                <div class="loader-spinner"></div>
                <p class="mt-3 text-muted">Loading sales analytics data...</p>
            </div>
        `);
    }

    function hideLoader() {
        // Loader will be replaced by content
    }

    function showError(title, message) {
        $('#SalesResultOutput').html(`
            <div class="alert alert-danger">
                <h5>${title}</h5>
                <p>${message}</p>
            </div>
        `);
    }

    function initializeCharts(chartData, conversionData) {
        console.log('Initializing charts with data:', chartData);
        
        if (!chartData) {
            console.log('No chart data available');
            return;
        }

        // Destroy existing chart instances
        if (inflowOutflowChart) inflowOutflowChart.destroy();
        if (airtimeChart) airtimeChart.destroy();
        if (dataChart) dataChart.destroy();
        if (conversionChart) conversionChart.destroy();
        if (conversionFlowChart) conversionFlowChart.destroy();
        if (usdFlowChart) usdFlowChart.destroy();
        if (eurFlowChart) eurFlowChart.destroy();

        // Inflow vs Outflow Chart
        if (chartData.labels && chartData.inflow && chartData.outflow) {
            initializeInflowOutflowChart(chartData);
        } else {
            document.getElementById('inflowOutflowChart').innerHTML = '<div class="empty-state"><p>No chart data available</p></div>';
        }

        // USD Flow Chart
        if (chartData.usd_flow && chartData.labels) {
            initializeUSDChart(chartData);
        } else {
            document.getElementById('usdFlowChart').innerHTML = '<div class="empty-state"><p>No USD flow data available</p></div>';
        }

        // EUR Flow Chart
        if (chartData.eur_flow && chartData.labels) {
            initializeEURChart(chartData);
        } else {
            document.getElementById('eurFlowChart').innerHTML = '<div class="empty-state"><p>No EUR flow data available</p></div>';
        }

        // Airtime by Network Chart
        if (chartData.airtime_networks && Object.keys(chartData.airtime_networks).length > 0) {
            initializeAirtimeChart(chartData);
        } else {
            document.getElementById('airtimeChart').innerHTML = '<div class="empty-state"><p>No airtime data available</p></div>';
        }

        // Data by Network Chart
        if (chartData.data_networks && Object.keys(chartData.data_networks).length > 0) {
            initializeDataChart(chartData);
        } else {
            document.getElementById('dataChart').innerHTML = '<div class="empty-state"><p>No data topup available</p></div>';
        }

        // Currency Conversion Flow Chart
        if (conversionData && conversionData.conversion_matrix && Object.keys(conversionData.conversion_matrix).length > 0) {
            initializeConversionFlowChart(conversionData.conversion_matrix);
        } else {
            const flowChartElement = document.getElementById('conversionFlowChart');
            if (flowChartElement) {
                flowChartElement.innerHTML = '<div class="empty-state"><p>No conversion flow data available</p></div>';
            }
        }
    }

    function initializeInflowOutflowChart(chartData) {
        const chartElement = document.getElementById("inflowOutflowChart");
        if (!chartElement) return;

        const options = {
            series: [
                {
                    name: 'Inflow',
                    data: chartData.inflow
                },
                {
                    name: 'Outflow',
                    data: chartData.outflow
                }
            ],
            chart: {
                type: 'line',
                height: 400,
                zoom: {
                    enabled: true
                },
                toolbar: {
                    show: true
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            title: {
                text: 'Cash Flow Analysis',
                align: 'left',
                style: {
                    fontSize: '16px',
                    fontWeight: 'bold'
                }
            },
            grid: {
                borderColor: '#e7e7e7',
                row: {
                    colors: ['#f3f3f3', 'transparent'],
                    opacity: 0.5
                },
            },
            markers: {
                size: 5,
            },
            xaxis: {
                categories: chartData.labels,
            },
            yaxis: {
                title: {
                    text: 'Amount (₦)'
                },
                labels: {
                    formatter: function(value) {
                        return '₦' + value.toLocaleString();
                    }
                }
            },
            colors: ['#00E396', '#FF4560'],
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                floating: true,
                offsetY: -25,
                offsetX: -5
            }
        };

        inflowOutflowChart = new ApexCharts(chartElement, options);
        inflowOutflowChart.render();
    }

    function initializeUSDChart(chartData) {
        const chartElement = document.getElementById("usdFlowChart");
        if (!chartElement) return;

        if (!chartData.usd_flow || !chartData.labels) {
            chartElement.innerHTML = '<div class="empty-state"><p>No USD flow data available</p></div>';
            return;
        }

        const options = {
            series: [
                {
                    name: 'USD Inflow',
                    data: chartData.usd_flow.inflow
                },
                {
                    name: 'USD Outflow',
                    data: chartData.usd_flow.outflow
                }
            ],
            chart: {
                type: 'line',
                height: 350,
                zoom: {
                    enabled: true
                },
                toolbar: {
                    show: true
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            title: {
                text: 'USD Account Flow',
                align: 'left',
                style: {
                    fontSize: '16px',
                    fontWeight: 'bold'
                }
            },
            grid: {
                borderColor: '#e7e7e7',
                row: {
                    colors: ['#f3f3f3', 'transparent'],
                    opacity: 0.5
                },
            },
            markers: {
                size: 5,
            },
            xaxis: {
                categories: chartData.labels,
            },
            yaxis: {
                title: {
                    text: 'Amount ($)'
                },
                labels: {
                    formatter: function(value) {
                        return '$' + value.toLocaleString();
                    }
                }
            },
            colors: ['#00E396', '#FF4560'],
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                floating: true,
                offsetY: -25,
                offsetX: -5
            }
        };

        usdFlowChart = new ApexCharts(chartElement, options);
        usdFlowChart.render();
    }

    function initializeEURChart(chartData) {
        const chartElement = document.getElementById("eurFlowChart");
        if (!chartElement) return;

        if (!chartData.eur_flow || !chartData.labels) {
            chartElement.innerHTML = '<div class="empty-state"><p>No EUR flow data available</p></div>';
            return;
        }

        const options = {
            series: [
                {
                    name: 'EUR Inflow',
                    data: chartData.eur_flow.inflow
                },
                {
                    name: 'EUR Outflow',
                    data: chartData.eur_flow.outflow
                }
            ],
            chart: {
                type: 'line',
                height: 350,
                zoom: {
                    enabled: true
                },
                toolbar: {
                    show: true
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            title: {
                text: 'EUR Account Flow',
                align: 'left',
                style: {
                    fontSize: '16px',
                    fontWeight: 'bold'
                }
            },
            grid: {
                borderColor: '#e7e7e7',
                row: {
                    colors: ['#f3f3f3', 'transparent'],
                    opacity: 0.5
                },
            },
            markers: {
                size: 5,
            },
            xaxis: {
                categories: chartData.labels,
            },
            yaxis: {
                title: {
                    text: 'Amount (€)'
                },
                labels: {
                    formatter: function(value) {
                        return '€' + value.toLocaleString();
                    }
                }
            },
            colors: ['#FEB019', '#FF4560'],
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                floating: true,
                offsetY: -25,
                offsetX: -5
            }
        };

        eurFlowChart = new ApexCharts(chartElement, options);
        eurFlowChart.render();
    }

    function initializeAirtimeChart(chartData) {
        const chartElement = document.getElementById("airtimeChart");
        if (!chartElement) return;

        const series = [];
        const colors = ['#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0'];

        for (const [network, data] of Object.entries(chartData.airtime_networks)) {
            series.push({
                name: network || 'Unknown',
                data: data
            });
        }

        const options = {
            series: series,
            chart: {
                type: 'bar',
                height: 350,
                stacked: true,
                toolbar: {
                    show: true
                }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                },
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: chartData.labels,
            },
            yaxis: {
                title: {
                    text: 'Amount (₦)'
                },
                labels: {
                    formatter: function(value) {
                        return '₦' + value.toLocaleString();
                    }
                }
            },
            fill: {
                opacity: 1
            },
            colors: colors,
            legend: {
                position: 'top'
            }
        };

        airtimeChart = new ApexCharts(chartElement, options);
        airtimeChart.render();
    }

    function initializeDataChart(chartData) {
        const chartElement = document.getElementById("dataChart");
        if (!chartElement) return;

        const series = [];
        const colors = ['#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0'];

        for (const [network, data] of Object.entries(chartData.data_networks)) {
            series.push({
                name: network || 'Unknown',
                data: data
            });
        }

        const options = {
            series: series,
            chart: {
                type: 'bar',
                height: 350,
                stacked: true,
                toolbar: {
                    show: true
                }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                },
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: chartData.labels,
            },
            yaxis: {
                title: {
                    text: 'Amount (₦)'
                },
                labels: {
                    formatter: function(value) {
                        return '₦' + value.toLocaleString();
                    }
                }
            },
            fill: {
                opacity: 1
            },
            colors: colors,
            legend: {
                position: 'top'
            }
        };

        dataChart = new ApexCharts(chartElement, options);
        dataChart.render();
    }

    function initializeConversionFlowChart(conversionMatrix) {
        const chartElement = document.getElementById("conversionFlowChart");
        if (!chartElement) return;

        if (!conversionMatrix || Object.keys(conversionMatrix).length === 0) {
            chartElement.innerHTML = '<div class="empty-state"><p>No conversion flow data available</p></div>';
            return;
        }

        // Prepare data for the chart
        const seriesData = [];
        const categories = [];

        Object.keys(conversionMatrix).forEach(from => {
            Object.keys(conversionMatrix[from]).forEach(to => {
                const data = conversionMatrix[from][to];
                categories.push(`${from} → ${to}`);
                seriesData.push({
                    x: `${from} → ${to}`,
                    y: data.volume,
                    count: data.count,
                    rate: data.average_rate
                });
            });
        });

        // Sort by volume descending
        seriesData.sort((a, b) => b.y - a.y);

        const options = {
            series: [{
                name: 'Conversion Volume',
                data: seriesData.map(item => item.y)
            }],
            chart: {
                type: 'bar',
                height: 500,
                toolbar: {
                    show: true
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    horizontal: true,
                    distributed: true,
                    dataLabels: {
                        position: 'bottom'
                    }
                }
            },
            dataLabels: {
                enabled: true,
                textAnchor: 'start',
                style: {
                    colors: ['#fff']
                },
                formatter: function(val, opt) {
                    const item = seriesData[opt.dataPointIndex];
                    return `${formatNumber(val)} ${item.x.split(' → ')[0]}`;
                },
                offsetX: 0,
                dropShadow: {
                    enabled: true
                }
            },
            colors: ['#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0', '#546E7A', '#26a69a', '#D10CE8'],
            xaxis: {
                categories: seriesData.map(item => item.x),
                labels: {
                    formatter: function(val) {
                        return formatNumber(val);
                    }
                },
                title: {
                    text: 'Conversion Volume'
                }
            },
            yaxis: {
                labels: {
                    maxWidth: 200
                }
            },
            title: {
                text: 'Currency Conversion Volumes by Pair',
                align: 'center'
            },
            tooltip: {
                y: {
                    formatter: function(val, { series, seriesIndex, dataPointIndex, w }) {
                        const item = seriesData[dataPointIndex];
                        return `
                            <div>
                                <strong>${formatNumber(val)} ${item.x.split(' → ')[0]}</strong><br/>
                                Conversions: ${item.count}<br/>
                                Avg Rate: ${item.rate ? item.rate.toFixed(4) : 'N/A'}
                            </div>
                        `;
                    }
                }
            }
        };

        conversionFlowChart = new ApexCharts(chartElement, options);
        conversionFlowChart.render();
    }

    function exportToExcel() {
        alert('Export functionality would be implemented here');
        // This would typically make an AJAX call to generate and download an Excel file
    }

    $(document).ready(function() {
        console.log('Document ready, initializing sales calculator');
        submitSalesCalculator();
    });
</script>

<script></script>
@endsection
