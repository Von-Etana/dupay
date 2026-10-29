@extends('admin.layouts.app')

@section('style')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/src/table/datatable/datatables-2.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/css/light/table/datatable/dt-global_style-2.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/css/light/table/datatable/custom_dt_custom-2.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/css/dark/table/datatable/dt-global_style-2.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/css/dark/table/datatable/custom_dt_custom-2.css') }}">
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
                    <li class="breadcrumb-item active" aria-current="page">Edit Physical Card</li>
                </ol>
            </nav>
        </div>
        <!-- /BREADCRUMB -->

        <div class="row">
            <div class="col-lg-12 layout-spacing layout-top-spacing">
                <div class="statbox widget box box-shadow">
                    <div class="widget-content widget-content-area" style="padding: 20px;">
                        <form method="POST" action="{{ route('admin.physical_cards.update', $physical_card->id) }}">
                            @csrf
                            @method('PUT')

                            <label class="label">User:</label>
                            <select name="user_id" class="form-control rounded-xs">
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" {{ $physical_card->user_id == $user->id ? 'selected' : '' }}>
                                        {{ $user->email }}
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                            <br>

                            <label class="label">Card ID:</label>
                            <input type="text" name="card_id" class="form-control rounded-xs" value="{{ old('card_id', $physical_card->card_id) }}">
                            @error('card_id') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                            <br>

                            <label class="label">Account ID:</label>
                            <input type="text" name="account_id" class="form-control rounded-xs" value="{{ old('account_id', $physical_card->account_id) }}">
                            @error('account_id') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                            <br>

                            <label class="label">Currency:</label>
                            <input type="text" name="currency" class="form-control rounded-xs" value="{{ old('currency', $physical_card->currency) }}">
                            @error('currency') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                            <br>

                            <label class="label">Card Hash:</label>
                            <input type="text" name="card_hash" class="form-control rounded-xs" value="{{ old('card_hash', $physical_card->card_hash) }}">
                            @error('card_hash') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                            <br>

                            <label class="label">Card PAN (full):</label>
                            <input type="text" name="card_pan" class="form-control rounded-xs" value="{{ old('card_pan', $physical_card->card_pan) }}">
                            @error('card_pan') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                            <br>

                            <label class="label">CVV:</label>
                            <input type="text" name="cvv" class="form-control rounded-xs" value="{{ old('cvv', $physical_card->cvv) }}">
                            @error('cvv') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                            <br>

                            <label class="label">Send To:</label>
                            <input type="text" name="send_to" class="form-control rounded-xs" value="{{ old('send_to', $physical_card->send_to) }}">
                            @error('send_to') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                            <br>

                            <label class="label">BIN Check Name:</label>
                            <input type="text" name="bin_check_name" class="form-control rounded-xs" value="{{ old('bin_check_name', $physical_card->bin_check_name) }}">
                            @error('bin_check_name') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                            <br>

                            <label class="label">Setup Step:</label>
                            <input type="text" name="setup_step" class="form-control rounded-xs" value="{{ old('setup_step', $physical_card->setup_step) }}">
                            @error('setup_step') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                            <br>

                            <label class="label">Customer ID:</label>
                            <input type="text" name="customer_id" class="form-control rounded-xs" value="{{ old('customer_id', $physical_card->customer_id) }}">
                            @error('customer_id') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                            <br>

                            <label class="label">Type:</label>
                            <input type="text" name="type" class="form-control rounded-xs" value="{{ old('type', $physical_card->type) }}">
                            @error('type') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                            <br>

                            <label class="label">Brand:</label>
                            <input type="text" name="brand" class="form-control rounded-xs" value="{{ old('brand', $physical_card->brand) }}">
                            @error('brand') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                            <br>

                            <label class="label">Masked PAN:</label>
                            <input type="text" name="masked_pan" class="form-control rounded-xs" value="{{ old('masked_pan', $physical_card->masked_pan) }}">
                            @error('masked_pan') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                            <br>

                            <label class="label">Expiry Month:</label>
                            <input type="text" name="expiry_month" class="form-control rounded-xs" value="{{ old('expiry_month', $physical_card->expiry_month) }}">
                            @error('expiry_month') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                            <br>

                            <label class="label">Expiry Year:</label>
                            <input type="text" name="expiry_year" class="form-control rounded-xs" value="{{ old('expiry_year', $physical_card->expiry_year) }}">
                            @error('expiry_year') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                            <br>

                            <label class="label">Status:</label>
                            <select name="status" class="form-control rounded-xs">
                                <option value="active" {{ $physical_card->status === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ $physical_card->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="blocked" {{ $physical_card->status === 'blocked' ? 'selected' : '' }}>Blocked</option>
                                <option value="pending" {{ $physical_card->status === 'pending' ? 'selected' : '' }}>Pending</option>
                            </select>
                            @error('status') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                            <br>

                            <label class="label">ATM Enabled:</label>
                            <select name="atm" class="form-control rounded-xs">
                                <option value="1" {{ $physical_card->atm ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ !$physical_card->atm ? 'selected' : '' }}>No</option>
                            </select>
                            <br>

                            <label class="label">POS Enabled:</label>
                            <select name="pos" class="form-control rounded-xs">
                                <option value="1" {{ $physical_card->pos ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ !$physical_card->pos ? 'selected' : '' }}>No</option>
                            </select>
                            <br>

                            <label class="label">Web Enabled:</label>
                            <select name="web" class="form-control rounded-xs">
                                <option value="1" {{ $physical_card->web ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ !$physical_card->web ? 'selected' : '' }}>No</option>
                            </select>
                            <br>

                            <label class="label">Mobile Enabled:</label>
                            <select name="mobile" class="form-control rounded-xs">
                                <option value="1" {{ $physical_card->mobile ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ !$physical_card->mobile ? 'selected' : '' }}>No</option>
                            </select>
                            <br>

                            <label class="label">Spending Limit Amount:</label>
                            <input type="text" name="spending_limit_amount" class="form-control rounded-xs" value="{{ old('spending_limit_amount', $physical_card->spending_limit_amount) }}">
                            @error('spending_limit_amount') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                            <br>

                            <label class="label">Spending Limit Interval:</label>
                            <input type="text" name="spending_limit_interval" class="form-control rounded-xs" value="{{ old('spending_limit_interval', $physical_card->spending_limit_interval) }}">
                            @error('spending_limit_interval') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                            <br>

                            <button type="submit" class="btn btn-primary">Update Card</button>
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
