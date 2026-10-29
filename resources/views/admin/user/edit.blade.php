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
					<li class="breadcrumb-item active" aria-current="page">Edit User Profile</li>
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
                            <h2>Edit User Profile</h2>
                        
                            <form action="{{ route('admin.user.update', $data->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                            
                                <div class="row">
                                    {{-- Email --}}
                                    <div class="col-md-6 mb-3">
                                        <label>Email</label>
                                        <input 
                                            type="email" 
                                            name="email" 
                                            value="{{ old('email', $data->email) }}" 
                                            class="form-control @error('email') is-invalid @enderror"
                                        >
                                        @error('email')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                            
                                    {{-- Phone Number --}}
                                    <div class="col-md-6 mb-3">
                                        <label>Phone Number</label>
                                        <input 
                                            type="text" 
                                            name="phone_number" 
                                            value="{{ old('phone_number', $data->phone_number) }}" 
                                            class="form-control @error('phone_number') is-invalid @enderror"
                                        >
                                        @error('phone_number')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                            
                                    {{-- First Name --}}
                                    <div class="col-md-6 mb-3">
                                        <label>First Name</label>
                                        <input 
                                            type="text" 
                                            name="first_name" 
                                            value="{{ old('first_name', $data->profile->first_name ?? '') }}" 
                                            class="form-control @error('first_name') is-invalid @enderror"
                                        >
                                        @error('first_name')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                            
                                    {{-- Last Name --}}
                                    <div class="col-md-6 mb-3">
                                        <label>Last Name</label>
                                        <input 
                                            type="text" 
                                            name="last_name" 
                                            value="{{ old('last_name', $data->profile->last_name ?? '') }}" 
                                            class="form-control @error('last_name') is-invalid @enderror"
                                        >
                                        @error('last_name')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                            
                                    {{-- Username --}}
                                    <div class="col-md-6 mb-3">
                                        <label>Username</label>
                                        <input 
                                            type="text" 
                                            name="username" 
                                            value="{{ old('username', $data->profile->username ?? '') }}" 
                                            class="form-control @error('username') is-invalid @enderror"
                                        >
                                        @error('username')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>
                            
                                <button type="submit" class="btn btn-primary mt-3">Update User</button>
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