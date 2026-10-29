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
					<li class="breadcrumb-item active" aria-current="page">Edit Foreign Account Request</li>
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
                            <h2>Edit Foreign Account Request</h2>
                        
                            <form action="{{ route('admin.foreign-accounts.update', $requestData->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf 
                                @method('PUT')
                            
                                <div class="form-group">
                                    <label>Currency</label>
                                    <input type="text" name="currency" value="{{ old('currency', $requestData->currency) }}" class="form-control">
                                </div>
                            
                                <div class="form-group">
                                    <label>BVN</label>
                                    <input type="text" name="bvn" value="{{ old('bvn', $requestData->bvn) }}" class="form-control">
                                </div>
                            
                                <div class="form-group">
                                    <label>Address Line 1</label>
                                    <input type="text" name="address_line1" value="{{ old('address_line1', $requestData->address_line1) }}" class="form-control">
                                </div>
                            
                                <div class="form-group">
                                    <label>Address Line 2</label>
                                    <input type="text" name="address_line2" value="{{ old('address_line2', $requestData->address_line2) }}" class="form-control">
                                </div>
                            
                                <div class="form-group">
                                    <label>City</label>
                                    <input type="text" name="city" value="{{ old('city', $requestData->city) }}" class="form-control">
                                </div>
                            
                                <div class="form-group">
                                    <label>State</label>
                                    <input type="text" name="state" value="{{ old('state', $requestData->state) }}" class="form-control">
                                </div>
                            
                                <div class="form-group">
                                    <label>Country</label>
                                    <input type="text" name="country" value="{{ old('country', $requestData->country) }}" class="form-control">
                                </div>
                            
                                <div class="form-group">
                                    <label>Postal Code</label>
                                    <input type="text" name="postal_code" value="{{ old('postal_code', $requestData->postal_code) }}" class="form-control">
                                </div>
                            
                                <div class="form-group">
                                    <label>Employment Status</label>
                                    <input type="text" name="employment_status" value="{{ old('employment_status', $requestData->employment_status) }}" class="form-control">
                                </div>
                            
                                <div class="form-group">
                                    <label>Occupation</label>
                                    <input type="text" name="occupation" value="{{ old('occupation', $requestData->occupation) }}" class="form-control">
                                </div>
                            
                                <div class="form-group">
                                    <label>Primary Purpose</label>
                                    <input type="text" name="primary_purpose" value="{{ old('primary_purpose', $requestData->primary_purpose) }}" class="form-control">
                                </div>
                            
                                <div class="form-group">
                                    <label>Source of Funds</label>
                                    <input type="text" name="source_of_funds" value="{{ old('source_of_funds', $requestData->source_of_funds) }}" class="form-control">
                                </div>
                            
                                <div class="form-group">
                                    <label>Expected Monthly Inflow</label>
                                    <input type="number" step="0.01" name="expected_monthly_inflow" value="{{ old('expected_monthly_inflow', $requestData->expected_monthly_inflow) }}" class="form-control">
                                </div>
                            
                                <hr>
                            
                                <div class="form-group">
                                    <label>Upload Document</label>
                                    <input type="file" name="document" class="form-control">
                                    @if($requestData->document)
                                        <a href="{{ asset('storage/'.$requestData->document) }}" target="_blank">Current File</a>
                                    @endif
                                </div>
                            
                                <div class="form-group">
                                    <label>Document Type</label>
                                    <input type="text" name="document_type" value="{{ old('document_type', $requestData->document_type) }}" class="form-control">
                                </div>
                            
                                <div class="form-group">
                                    <label>Document Issue Date</label>
                                    <input type="date" name="document_issue_date" value="{{ old('document_issue_date', $requestData->document_issue_date) }}" class="form-control">
                                </div>
                            
                                <div class="form-group">
                                    <label>Document Expiry Date</label>
                                    <input type="date" name="document_expiry_date" value="{{ old('document_expiry_date', $requestData->document_expiry_date) }}" class="form-control">
                                </div>
                            
                                <hr>
                            
                                <div class="form-group">
                                    <label>Upload Secondary Document</label>
                                    <input type="file" name="document2" class="form-control">
                                    @if($requestData->document2)
                                        <a href="{{ asset('storage/'.$requestData->document2) }}" target="_blank">Current File</a>
                                    @endif
                                </div>
                            
                                <div class="form-group">
                                    <label>Secondary Document Type</label>
                                    <input type="text" name="document2_type" value="{{ old('document2_type', $requestData->document2_type) }}" class="form-control">
                                </div>
                            
                                <div class="form-group">
                                    <label>Secondary Document Issue Date</label>
                                    <input type="date" name="document2_issue_date" value="{{ old('document2_issue_date', $requestData->document2_issue_date) }}" class="form-control">
                                </div>
                            
                                <div class="form-group">
                                    <label>Secondary Document Expiry Date</label>
                                    <input type="date" name="document2_expiry_date" value="{{ old('document2_expiry_date', $requestData->document2_expiry_date) }}" class="form-control">
                                </div>
                            
                                <hr>
                            
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status" class="form-control">
                                        <option value="pending" {{ $requestData->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="approved" {{ $requestData->status == 'approved' ? 'selected' : '' }}>Approved</option>
                                        <option value="declined" {{ $requestData->status == 'declined' ? 'selected' : '' }}>Declined</option>
                                    </select>
                                </div>
                            
                                <button type="submit" class="btn btn-primary mt-3">Update Request</button>
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