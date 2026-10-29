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
					<li class="breadcrumb-item active" aria-current="page">New Notification</li>
				</ol>
			</nav>
		</div>
		<!-- /BREADCRUMB -->
		<div class="row">
        	<div id="flStackForm" class="col-lg-12 layout-spacing layout-top-spacing">
        		<div class="statbox widget box box-shadow">
        			<div class="widget-content widget-content-area" style="padding:20px;">
        				<form method="POST" action="{{ route('admin.push.store') }}">
                        	@csrf              
                        	<div class="form-custom form-label mt-3">
                        		<label for="title" class="label">Title</label>
                        		<input type="text" name="title" id="title" class=" form-control rounded-xs " value="GREETINGS" placeholder="Enter title" required >
                        	</div>
                        	<div class="mb-3">
                        		<label for="page_content" class="label">Message</label>
                        		<textarea name="message" class="form-control" required>HAPPY NEW MONTH</textarea>
                        	</div>
                        	<div class=" mb-3  ">
                        		<label for="status" class="">Status</label>
                        		<select name="status" class=" form-control input-default  " id="status"  >
                        			<option value="" selected>Please Select</option>
                        			<option value="1" selected>Send &amp; Save</option>
                        			<option value="0">Save</option>
                        		</select>
                        	</div>
                        	<button type="submit" class="btn btn-primary">Submit</button>
                        </form>
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