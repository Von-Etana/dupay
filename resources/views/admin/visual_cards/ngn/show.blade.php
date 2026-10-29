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
					<li class="breadcrumb-item active" aria-current="page">Virtual Card</li>
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
							<h4 class="">Card Details</h4>
						</div>
						<div class="user-info">
							@if(isset($cardData['status']) && $cardData['status'] === true && isset($cardData['data']))
                                @php
                                    $cardDetail = $cardData['data'];
                                @endphp
                            
                                <ul class="list-disc pl-5 space-y-2">
                                    <li><strong>Card Number:</strong> {{ $cardDetail['card_number'] ?? 'N/A' }}</li>
                                    <li><strong>CVV:</strong> {{ $cardDetail['cvv'] ?? 'N/A' }}</li>
                                    <li><strong>Expiry Month:</strong> {{ $cardDetail['expiry_month'] ?? 'N/A' }}</li>
                                    <li><strong>Expiry Year:</strong> {{ $cardDetail['expiry_year'] ?? 'N/A' }}</li>
                                    <li><strong>Card Type:</strong> {{ $cardDetail['card_type'] ?? 'N/A' }}</li>
                                    <li><strong>Card Brand:</strong> {{ $cardDetail['card_brand'] ?? 'N/A' }}</li>
                                    <li><strong>Default PIN:</strong> {{ $cardDetail['default_pin'] ?? 'N/A' }}</li>
                                    <li><strong>Status:</strong> {{ ucfirst($cardDetail['status'] ?? 'N/A') }}</li>
                                    <li><strong>Customer Name:</strong> {{ $cardDetail['customer_name'] ?? 'N/A' }}</li>
                                    <li><strong>Customer Email:</strong> {{ $cardDetail['customer_email'] ?? 'N/A' }}</li>
                                    <li><strong>Customer Phone:</strong> {{ $cardDetail['customer_phone'] ?? 'N/A' }}</li>
                                    <li><strong>Billing Address:</strong>
                                        @if(!empty($cardDetail['billing_address']))
                                            {{ $cardDetail['billing_address']['line1'] ?? '' }},
                                            {{ $cardDetail['billing_address']['city'] ?? '' }},
                                            {{ $cardDetail['billing_address']['state'] ?? '' }},
                                            {{ $cardDetail['billing_address']['country'] ?? '' }}
                                        @else
                                            N/A
                                        @endif
                                    </li>
                                </ul>
                            @else
                                <div class="alert alert-danger">
                                    {{ $cardData['message'] ?? 'Unable to load card details.' }}
                                </div>
                            @endif

						</div>
					    <hr>
					    @can('update_naira_virtual_card_status')
					    <ul class="list-inline mt-4">
							<li class="list-inline-item mb-0">
								<a class="btn btn-{{ $card->status == 'active' ? 'success' : 'warning' }}  mb-2 me-4" data-bs-toggle="modal"
									data-bs-target="#BlockModaladedayooluwafemiaderuku">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
										viewBox="0 0 24 24" fill="none" stroke="currentColor"
										stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
										class="feather feather-lock">
										<rect x="3" y="11" width="18"
											height="11" rx="2" ry="2"></rect>
										<path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
									</svg>
									<span class="btn-text-inner">{{ $card->status == 'active' ? 'Deactivate' : 'Activate' }}</span>
								</a>
							</li>
						</ul>
						@endcan
					    <!-- ban and unban user-->
						<div class="modal  fade animated rotateInDownLeft"
							id="BlockModaladedayooluwafemiaderuku" tabindex="-1" role="dialog"
							aria-hidden="true">
							<div class="modal-dialog modal-sm">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title">Acivate/Deactivate Card</h5>
										<button type="button" class="btn-close" data-bs-dismiss="modal">
										</button>
									</div>
									<div class="modal-body">Are you sure you want to do this?</div>
									<form method="post"
										action="{{ route('admin.visual_cards.ngn.freeze') }}"
										class="form-inline pin-protected-form">
										<div class="modal-footer">
											@csrf
											<input type="hidden" name="card_id" value="{{ $card->id }}">
											<button type="button" class="btn" data-bs-dismiss="modal"><i
												class="flaticon-cancel-12"></i> Discard</button>
											<button type="submit" class="btn btn-primary">Yes</button>
										</div>
									</form>
								</div>
							</div>
						</div>
						<hr>
						@can('reset_naira_virtual_card_pin')
						<div class="wallet-action text-center d-flex justify-content-around">
							<a class="btn btn-info collapsed" role="menu" data-bs-toggle="collapse"
								data-bs-target="#reset" aria-expanded="false" aria-controls="fund">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
									viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
									stroke-linecap="round" stroke-linejoin="round" class="feather feather-plus-circle">
									<circle cx="12" cy="12" r="10"></circle>
									<line x1="12" y1="8" x2="12" y2="16"></line>
									<line x1="8" y1="12" x2="16" y2="12"></line>
								</svg>
								<span class="btn-text-inner">Reset Pin</span>
							</a>
						</div>
						@endcan
						<div id="reset" class="collapse" aria-labelledby="resetLabel"
							data-bs-parent="#toggleAccordion">
							<div class="card-body">
							    <h4>Fund card</h4>
								<form action="{{ route('admin.visual_cards.ngn.reset') }}" method="POST" class="pin-protected-form">
								    @csrf
									<input type="hidden" name="id" value="{{ $card->id }}">
									<div class="form-custom form-label mt-3">
										<label for="amount" class="label">New Pin</label>
										<input type="number" name="pin" id="pin" class=" form-control rounded-xs " value="" placeholder="Enter new pin" required >
									</div>
									<button type="submit" class="btn btn-success w-100">Submit</button>
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-xl-8 col-lg-12 col-md-12 col-sm-12 layout-top-spacing">
			    @can('view_naira_virtual_card_transactions')
				<div class="widget-content widget-content-area">
					<div class="table-responsive">
					    <!-- Search Input -->
                        <div class="m-2">
                            <input type="text" id="tableSearch" class="form-control" placeholder="Search cards..." style="width:300px">
                        </div>
                    	<table id="item_table" class="table style-3 dt-table-hover dataTable no-footer" role="grid" aria-describedby="item_table_info" style="width: 1835px;">
                            <thead>
                                <tr role="row">
                                    <th>Amount</th>
                                    <th>Fee</th>
                                    <th>Location</th>
                                    <th>Withdraw ID</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(is_array($transactions) && count($transactions))
                                    @foreach($transactions as $transaction)
                                        <tr>
                                            <td>{{ $transaction['amount'] ?? 'N/A' }}</td>
                                            <td>{{ $transaction['fee'] ?? 'N/A' }}</td>
                                            <td>{{ $transaction['location'] ?? 'N/A' }}</td>
                                            <td>{{ $transaction['withdraw_id'] ?? 'N/A' }}</td>
                                            <td>{{ \Carbon\Carbon::parse($transaction['created_at'])->format('Y-m-d H:i') ?? '' }}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="11" class="text-center">No data found</td>
                                    </tr>
                                @endif

                            </tbody>
                        </table>
                        <script>
                            document.getElementById('tableSearch').addEventListener('keyup', function() {
                                let filter = this.value.toLowerCase();
                                let rows = document.querySelectorAll("#item_table tbody tr");
                            
                                rows.forEach(row => {
                                    let text = row.innerText.toLowerCase();
                                    row.style.display = text.includes(filter) ? "" : "none";
                                });
                            });
                            </script>
			        </div>
				</div>
				@endcan
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="adminPinModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-3">
            <h5 class="mb-3">Enter Admin PIN</h5>

            <input type="password" id="admin_pin_input"
                   class="form-control"
                   placeholder="Enter PIN" inputmode="numeric" pattern="\d{4}" maxlength="4">

            <button class="btn btn-success w-100 mt-3" id="confirmPinBtn">
                Confirm
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('assets/admin/js/general.js') }}"></script>
    
    <script>
    document.addEventListener("DOMContentLoaded", function () {
    
        let pendingForm = null;
        const modal = new bootstrap.Modal(document.getElementById('adminPinModal'));
    
        // Intercept ALL pin forms
        document.querySelectorAll(".pin-protected-form").forEach(form => {
    
            form.addEventListener("submit", function(e){
    
                // already verified? allow submit
                if(form.dataset.pinVerified === "true") return;
    
                e.preventDefault(); // STOP normal submit
                pendingForm = form;
                modal.show();
            });
    
        });
    
        // When admin clicks confirm PIN
        document.getElementById("confirmPinBtn").addEventListener("click", function(){
    
            let pinInput = document.getElementById("admin_pin_input");
            let pin = pinInput.value;
    
            if(!pin){
                alert("Enter admin PIN");
                return;
            }
    
            fetch("{{ route('admin.verify.pin') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({ pin: pin })
            })
            .then(res => res.json())
            .then(data => {
    
                if(data.status === true){
    
                    // attach pin to form
                    let hidden = document.createElement("input");
                    hidden.type = "hidden";
                    hidden.name = "admin_pin";
                    hidden.value = pin;
    
                    pendingForm.appendChild(hidden);
    
                    pendingForm.dataset.pinVerified = "true";
    
                    modal.hide();
    
                    // NOW submit form
                    pendingForm.submit();
    
                }else{
                    alert("Incorrect PIN");
                }
    
            });
    
        });
    
    });
    </script>
    

@endsection