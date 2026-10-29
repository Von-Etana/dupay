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
					<li class="breadcrumb-item active" aria-current="page">View Physical Card </li>
				</ol>
			</nav>
		</div>
		<!-- /BREADCRUMB -->
		<div class="container-fluid my-4">
        	<div class="row g-4">
        		{{-- Left column: Card summary --}}
        		<div class="col-xl-4 col-lg-5">
        			<div class="card shadow-sm">
        				<div class="card-body">
        					<div class="d-flex align-items-start">
        						<div class="me-3">
        							{{-- Simple card visual --}}
        							<div class="border rounded p-3 text-center" style="min-width:170px;">
        								<h5 class="mb-1">{{ strtoupper($physical_card->brand ?? 'CARD') }}</h5>
        								<p class="mb-1 fw-semibold">{{ $physical_card->type ?? 'Physical' }}</p>
        								<div class="fs-5 mt-2">
        									<span>{{ $physical_card->masked_pan ?? '•••• •••• •••• ••••' }}</span>
        								</div>
        								<div class="small text-muted mt-2">
        									Exp: {{ str_pad($physical_card->expiry_month ?? '--', 2, '0', STR_PAD_LEFT) }}/{{ $physical_card->expiry_year ?? '--' }}
        								</div>
        							</div>
        						</div>
        						<div class="flex-grow-1">
        							<h5 class="card-title mb-1">Card #{{ $physical_card->id }}</h5>
        							<p class="text-muted mb-2">Assigned to
        								{{ $physical_card->user->name ?? 'N/A' }}
        							</p>
        							<div class="mb-2">
        								<span class="badge bg-{{ $physical_card->status ? 'success' : 'secondary' }}">
        								{{ $physical_card->status ? 'Active' : 'Inactive' }}
        								</span>
        								<span class="badge bg-info text-dark">{{ $physical_card->currency ?? 'USD' }}</span>
        							</div>
        						</div>
        					</div>
        					<hr>
        					{{-- Spending limit --}}
        					<div class="mb-2">
        						<h6 class="mb-1">Spending limit</h6>
        						<p class="mb-0">
        							@if($physical_card->spending_limit_amount)
        							{{ number_format($physical_card->spending_limit_amount, 2) }} {{ $physical_card->currency }}
        							<span class="text-muted">/ {{ $physical_card->spending_limit_interval ?? 'month' }}</span>
        							@else
        							<span class="text-muted">No limit set</span>
        							@endif
        						</p>
        					</div>
        					<hr>
        					<div class="small text-muted">
        						Created: {{ optional($physical_card->created_at)->toDayDateTimeString() ?? 'N/A' }}<br>
        						Last updated: {{ optional($physical_card->updated_at)->diffForHumans() ?? 'N/A' }}
        					</div>
        				</div>
        			</div>
        		</div>
        		{{-- Right column: Details & actions --}}
        		<div class="col-xl-8 col-lg-7">
        			<div class="card">
        				<div class="card-body">
        					<h5 class="card-title">Card Details</h5>
        					<div class="row gy-2">
        						<div class="col-md-6">
        							<label class="form-label small">Card ID</label>
        							<div class="fw-bold">{{ $physical_card->card_id ?? 'N/A' }}</div>
        						</div>
        						<div class="col-md-6">
        							<label class="form-label small">BIN Check</label>
        							<div>{{ $physical_card->bin_check_name ?? '—' }}</div>
        						</div>
        						<div class="col-md-6">
        							<label class="form-label small">Card hash</label>
        							<div class="text-monospace small">{{ $physical_card->card_hash ?? '—' }}</div>
        						</div>
        						<div class="col-md-6">
        							<label class="form-label small">PAN (masked)</label>
        							<div class="d-flex align-items-center gap-2">
        								<div class="fw-bold">{{ $physical_card->masked_pan ?? '•••• •••• •••• ••••' }}</div>
        							</div>
        						</div>
        						<div class="col-md-6">
        							<label class="form-label small">CVV</label>
        							<div class="d-flex align-items-center gap-2">
        								<div id="cvvField" class="fw-bold">{{ $physical_card->cvv ?? '•••' }}</div>
        							</div>
        						</div>
        						<div class="col-md-12">
        							<label class="form-label small">Send To</label>
        							<div>{{ $physical_card->send_to ?? '—' }}</div>
        						</div>
        						<div class="col-md-12">
        							<label class="form-label small">Setup step</label>
        							<div>{{ $physical_card->setup_step ?? '—' }}</div>
        						</div>
        						<div class="col-md-12">
        							<label class="form-label small">Status flags</label>
        							<pre class="small bg-light p-2 rounded" style="white-space:pre-wrap;">{{ json_encode([
                                        'atm' => (bool)$physical_card->atm,
                                        'pos' => (bool)$physical_card->pos,
                                        'web' => (bool)$physical_card->web,
                                        'mobile' => (bool)$physical_card->mobile,
                                    ], JSON_PRETTY_PRINT) }}</pre>
        						</div>
        						<div class="col-md-12">
        							<label class="form-label small">Shipping details</label>
        							@if($physical_card->shipping_details)
        							<button class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#shippingCollapse">View shipping details</button>
        							<div id="shippingCollapse" class="collapse mt-2">
        								<pre class="small bg-light p-2 rounded">{{ is_string($physical_card->shipping_details) ? $physical_card->shipping_details : json_encode($physical_card->shipping_details, JSON_PRETTY_PRINT) }}</pre>
        							</div>
        							@else
        							<div class="text-muted">No shipping info</div>
        							@endif
        						</div>
        					</div>
        					{{-- end row --}}
        					<hr>
        					{{-- Admin actions --}}
        					<div class="d-flex gap-2">
        					    <a href="{{ route('admin.physical_cards.edit', $physical_card) }}" class="btn btn-primary">Edit card</a>
        						<button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#changePinModal">Change PIN</button>
        					</div>
        				</div>
        			</div>
        		</div>
        	</div>
        </div>
        {{-- Delete confirmation modal --}}
        <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        	<div class="modal-dialog modal-dialog-centered">
        		<div class="modal-content">
        			<form method="POST" action="">
        				@csrf
        				@method('DELETE')
        				<div class="modal-header">
        					<h5 class="modal-title">Delete Card</h5>
        					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        				</div>
        				<div class="modal-body">
        					<p>Are you sure you want to permanently delete this physical card record? This action cannot be undone.</p>
        					<p class="small text-muted">Card: <strong>#{{ $physical_card->id }}</strong> — Assigned to: <strong>{{ $physical_card->user->name ?? 'N/A' }}</strong></p>
        				</div>
        				<div class="modal-footer">
        					<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        					<button type="submit" class="btn btn-danger btn-sm">Delete</button>
        				</div>
        			</form>
        		</div>
        	</div>
        </div>
        {{-- Change PIN modal (simple) --}}
        <div class="modal fade" id="changePinModal" tabindex="-1" aria-hidden="true">
        	<div class="modal-dialog">
        		<div class="modal-content">
        			<form method="POST" action="{{ route('admin.physical_cards.reset.pin', $physical_card) }}" class="pin-protected-form">
        				@csrf
        				<div class="modal-header">
        					<h5 class="modal-title">Change Card PIN</h5>
        					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        				</div>
        				<div class="modal-body">
        					<div class="mb-3">
        						<label class="form-label small">New PIN</label>
        						<input name="new_pin" type="password" class="form-control" minlength="4" maxlength="4" required>
        						<div class="text-muted small">Min 4 digits. PIN will be stored securely (hashed).</div>
        					</div>
        					<div class="mb-3">
        						<label class="form-label small">Confirm PIN</label>
        						<input name="confirm_pin" type="password" class="form-control" minlength="4" maxlength="4" required>
        					</div>
        				</div>
        				<div class="modal-footer">
        					<button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
        					<button type="submit" class="btn btn-primary btn-sm">Save PIN</button>
        				</div>
        			</form>
        		</div>
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