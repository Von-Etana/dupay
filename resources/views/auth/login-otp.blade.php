@extends('layouts.app')
@section('content')
<div class="container"><div class="row justify-content-center"><div class="col-md-6"><div class="card"><div class="card-header">Verify your login</div><div class="card-body"><p>Enter the six-digit code sent to your email.</p><form method="POST" action="{{ route('login.otp.verify') }}">@csrf<label for="otp">Verification code</label><input id="otp" name="otp" class="form-control" inputmode="numeric" maxlength="6" required autofocus>@error('otp')<div class="text-danger">{{ $message }}</div>@enderror<button class="btn btn-primary mt-3">Verify</button></form></div></div></div></div></div>
@endsection
