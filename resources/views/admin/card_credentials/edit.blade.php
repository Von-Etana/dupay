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
					<li class="breadcrumb-item active" aria-current="page">Edit Assigned Card Credential</li>
				</ol>
			</nav>
		</div>
		<!-- /BREADCRUMB -->
		<div class="row">
        	<div id="flStackForm" class="col-lg-12 layout-spacing layout-top-spacing">
        		<div class="statbox widget box box-shadow">
        			<div class="widget-content widget-content-area" style="padding:20px;">
        				<form method="POST" action="{{ route('admin.card_credentials.update', $cardCredential->id) }}">
        					
        					@csrf
        					@method('PUT')
                          <label class="label">User:</label>
                          <select name="user_id" class=" form-control rounded-xs">
                            @foreach($cards as $card)
                              <option value="{{ $card->user->id }}"  {{ $cardCredential->user_id == $card->user->id ? 'selected' : '' }}>{{ $card->user->profile->first_name.' '.$card->user->profile->last_name }} ({{ $card->user->email }})</option>
                            @endforeach
                          </select>
                          @error('user_id')
                            <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                        @enderror
                        <br>
                          <label class="label">Card PAN (Full Card Number):</label>
                          <input type="text" class=" form-control rounded-xs " name="card_pan" required  value="{{ old('card_pan', $cardCredential->card_pan) }}">
                          @error('card_pan')
                            <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                        @enderror
                        <br>
                          <label class="label">Card PAN (Last 6):</label>
                          <input type="text" class=" form-control rounded-xs " name="card_pan_last6" required maxlength="6" value="{{ old('card_pan_last6', $cardCredential->card_pan_last6) }}">
                          @error('card_pan_last6')
                            <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                        @enderror
                        <br>
                          <label class="label">CVV:</label>
                          <input type="text" class=" form-control rounded-xs " name="cvv" value="{{ old('cvv', $cardCredential->cvv) }}" required maxlength="3">
                          @error('cvv')
                            <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                        @enderror
                        <br>
                          <label for="rand" class="label">Brand</label>
                        <select name="brand" id="brand" class=" form-control rounded-xs ">
                            <option value="Verve" {{ $cardCredential->brand == 'Verve' ? 'selected' : '' }}>Verve</option>
                            <option value="AfriGo" {{ $cardCredential->brand == 'AfriGo' ? 'selected' : '' }}>AfriGo</option>
                        </select>
                        @error('brand')
                            <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                        @enderror
                        <br>
                        <label for="used" class="label">Used</label>
                        <select name="used" id="used" class=" form-control rounded-xs ">
                            <option value="0" {{ $cardCredential->used == 0 ? 'selected' : '' }}>No</option>
                            <option value="1" {{ $cardCredential->used == 1 ? 'selected' : '' }}>Yes</option>
                        </select>
                        @error('used')
                            <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                        @enderror
                        <label for="delivery_status" class="label">Delivery Status</label>
                        <select name="delivery_status" id="delivery_status" class=" form-control rounded-xs ">
                            <option value="pending" {{ $cardCredential->delivery_status == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="delivered" {{ $cardCredential->delivery_status == 'delivered' ? 'selected' : '' }}>Delivered</option>
                        </select>
                        @error('delivery_status')
                            <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                        @enderror
                        <br>
        					<button type="submit" class="btn btn-primary"> Update </button>
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