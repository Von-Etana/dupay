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
					<li class="breadcrumb-item active" aria-current="page">Foreign Account Request Details</li>
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
    						<h2>Request #{{ $request->id }}</h2>
    
                            <p><strong>User:</strong> {{ $request->user->profile->first_name . ' ' . $request->user->profile->last_name ?? 'N/A' }}</p>

                            <p><strong>Currency:</strong> {{ $request->currency }}</p>
                            <p><strong>BVN:</strong> {{ $request->bvn ?? 'N/A' }}</p>
                            
                            <p><strong>Address Line 1:</strong> {{ $request->address_line1 }}</p>
                            <p><strong>Address Line 2:</strong> {{ $request->address_line2 ?? 'N/A' }}</p>
                            <p><strong>City:</strong> {{ $request->city }}</p>
                            <p><strong>State:</strong> {{ $request->state }}</p>
                            <p><strong>Country:</strong> {{ $request->country }}</p>
                            <p><strong>Postal Code:</strong> {{ $request->postal_code }}</p>
                            
                            <p><strong>Employment Status:</strong> {{ $request->employment_status }}</p>
                            <p><strong>Occupation:</strong> {{ $request->occupation }}</p>
                            <p><strong>Primary Purpose:</strong> {{ $request->primary_purpose }}</p>
                            <p><strong>Source of Funds:</strong> {{ $request->source_of_funds }}</p>
                            <p><strong>Expected Monthly Inflow:</strong> {{ number_format($request->expected_monthly_inflow, 2) }}</p>
                            
                            <hr>
                            
                            {{-- Document 1 --}}
                            <p><strong>Document 1 Type:</strong> {{ $request->document_type ?? 'N/A' }}</p>
                            <p><strong>Document 1 Issue Date:</strong> {{ $request->document_issue_date ?? 'N/A' }}</p>
                            <p><strong>Document 1 Expiry Date:</strong> {{ $request->document_expiry_date ?? 'N/A' }}</p>
                            <p><strong>Document 1 File:</strong>
                                @if($request->document)
                                    @php
                                        $ext1 = pathinfo($request->document, PATHINFO_EXTENSION);
                                    @endphp
                                
                                    @if(in_array(strtolower($ext1), ['jpg', 'jpeg', 'png', 'gif']))
                                        <img 
                                            src="{{ asset('storage/'.$request->document) }}" 
                                            alt="Document 1" 
                                            width="300"
                                            onerror="this.onerror=null;this.src='{{ asset($request->document) }}';"
                                        ><br>
                                    @else
                                        <i class="fa fa-file-pdf text-danger"></i> File Uploaded<br>
                                    @endif
                                
                                    <a href="{{ asset('storage/'.$request->document) }}" 
                                       class="btn btn-success btn-sm mt-1" 
                                       target="_blank"
                                       onclick="this.href='{{ asset('storage/'.$request->document) }}';">
                                       View File
                                    </a>
                                @else
                                    N/A
                                @endif
                            </p>
                            
                            {{-- Document 2 --}}
                            <p><strong>Document 2 Type:</strong> {{ $request->document2_type ?? 'N/A' }}</p>
                            <p><strong>Document 2 Issue Date:</strong> {{ $request->document2_issue_date ?? 'N/A' }}</p>
                            <p><strong>Document 2 Expiry Date:</strong> {{ $request->document2_expiry_date ?? 'N/A' }}</p>
                            <p><strong>Document 2 File:</strong>
                                @if($request->document2)
                                    @php
                                        $ext2 = pathinfo($request->document2, PATHINFO_EXTENSION);
                                    @endphp
                                
                                    @if(in_array(strtolower($ext2), ['jpg', 'jpeg', 'png', 'gif']))
                                        <img 
                                            src="{{ asset('storage/'.$request->document2) }}" 
                                            alt="Document 2" 
                                            width="300"
                                            onerror="this.onerror=null;this.src='{{ asset($request->document2) }}';"
                                        ><br>
                                    @else
                                        <i class="fa fa-file-pdf text-danger"></i> File Uploaded<br>
                                    @endif
                                
                                    <a href="{{ asset('storage/'.$request->document2) }}" 
                                       class="btn btn-success btn-sm mt-1" 
                                       target="_blank"
                                       onclick="this.href='{{ asset('storage/'.$request->document2) }}';">
                                       View File
                                    </a>
                                @else
                                    N/A
                                @endif
                            </p>
                            
                            <hr>
                            
                            <p><strong>Status:</strong>
                                <span class="badge badge-{{ $request->status == 'pending' ? 'warning' : ($request->status == 'approved' ? 'success' : 'info') }}">
                                    {{ ucfirst($request->status) }}
                                </span>
                            </p>
                            
                            <p><strong>Grahp Person ID:</strong>
                                <span class="badge badge-{{ $request->user->graph_person_id == '' ? 'warning' : 'info' }}">
                                    {{ ucfirst($request->user->graph_person_id ?? 'Pending Approval') }}
                                </span>
                            </p>

                        
                            <hr>
                            @can('approve_foreign_account_requests')
                            <form action="{{ route('admin.foreign-accounts.approve', $request->id) }}" method="POST" style="display:inline;">
                                @csrf
                                <button class="btn btn-success">Approve and Create Account</button>
                            </form>
                            @endcan
                            
                            <form action="{{ route('admin.foreign-accounts.create.bank.account', $request->id) }}" method="POST" style="display:inline;">
                                @csrf
                                <button class="btn btn-primary">Create Bank Account</button>
                            </form>
                        
                            @can('decline_foreign_account_requests')
                            <button class="btn btn-danger" onclick="document.getElementById('declineForm').style.display='block'">Decline</button>
                            @endcan
                        
                            <div id="declineForm" style="display:none; margin-top:15px;">
                                <form action="{{ route('admin.foreign-accounts.decline', $request->id) }}" method="POST">
                                    @csrf
                                    <textarea name="reason" class="form-control" placeholder="Enter reason for decline" required></textarea>
                                    <button class="btn btn-danger mt-2">Submit Decline</button>
                                </form>
                            </div>
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