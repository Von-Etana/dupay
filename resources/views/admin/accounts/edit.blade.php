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
                            <h2>Edit Foreign Account</h2>
                        
                            <form action="{{ route('admin.foreign.accounts.update', $accountData->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                            
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label>User ID</label>
                                        <input type="number" name="user_id" value="{{ old('user_id', $accountData->user_id) }}" class="form-control">
                                    </div>
                            
                                    <div class="col-md-6 mb-3">
                                        <label>Account ID</label>
                                        <input type="text" name="account_id" value="{{ old('account_id', $accountData->account_id) }}" class="form-control">
                                    </div>
                            
                                    <div class="col-md-6 mb-3">
                                        <label>Holder ID</label>
                                        <input type="text" name="holder_id" value="{{ old('holder_id', $accountData->holder_id) }}" class="form-control">
                                    </div>
                            
                                    <div class="col-md-6 mb-3">
                                        <label>Account Number</label>
                                        <input type="text" name="account_number" value="{{ old('account_number', $accountData->account_number) }}" class="form-control">
                                    </div>
                            
                                    <div class="col-md-6 mb-3">
                                        <label>Account Name</label>
                                        <input type="text" name="account_name" value="{{ old('account_name', $accountData->account_name) }}" class="form-control">
                                    </div>
                            
                                    <div class="col-md-6 mb-3">
                                        <label>Bank Name</label>
                                        <input type="text" name="bank_name" value="{{ old('bank_name', $accountData->bank_name) }}" class="form-control">
                                    </div>
                            
                                    <div class="col-md-6 mb-3">
                                        <label>Bank Code</label>
                                        <input type="text" name="bank_code" value="{{ old('bank_code', $accountData->bank_code) }}" class="form-control">
                                    </div>
                            
                                    <div class="col-md-6 mb-3">
                                        <label>Routing Number</label>
                                        <input type="text" name="routing_number" value="{{ old('routing_number', $accountData->routing_number) }}" class="form-control">
                                    </div>
                            
                                    <div class="col-md-6 mb-3">
                                        <label>Bank Address</label>
                                        <input type="text" name="bank_address" value="{{ old('bank_address', $accountData->bank_address) }}" class="form-control">
                                    </div>
                            
                                    <div class="col-md-6 mb-3">
                                        <label>Balance</label>
                                        <input type="number" step="0.01" name="balance" value="{{ old('balance', $accountData->balance) }}" class="form-control">
                                    </div>
                            
                                    <div class="col-md-6 mb-3">
                                        <label>Currency</label>
                                        <input type="text" name="currency" value="{{ old('currency', $accountData->currency) }}" class="form-control">
                                    </div>
                            
                                    <div class="col-md-6 mb-3">
                                        <label>Status</label>
                                        <select name="status" class="form-control">
                                            <option value="active" {{ $accountData->status == 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="inactive" {{ $accountData->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                    </div>
                            
                                    <div class="col-12 mb-3">
                                        <label>Bank Data (JSON)</label>
                                        <textarea name="bank_data" class="form-control" rows="3">{{ old('bank_data', $accountData->bank_data) }}</textarea>
                                    </div>
                                </div>
                            
                                <button type="submit" class="btn btn-primary mt-3">Update Account</button>
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