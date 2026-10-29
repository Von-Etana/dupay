@extends('admin.layouts.head')
@section('title', "Login")

@section('style')
<link href="{{ asset('assets/admin/src/assets/css/light/authentication/auth-boxed.css')}}" rel="stylesheet" type="text/css" />
<link href="{{ asset('assets/admin/src/assets/css/dark/authentication/auth-boxed.css')}}" rel="stylesheet" type="text/css" />
@endsection

@section('body')

<body class="form">

    <!-- BEGIN LOADER -->
    <div id="load_screen"> <div class="loader"> <div class="loader-content">
        <div class="spinner-grow align-self-center"></div>
    </div></div></div>
    <!--  END LOADER -->

    <div class="auth-container d-flex">

        <div class="container mx-auto align-self-center">

            <div class="row">

                <div class="col-xxl-4 col-xl-5 col-lg-5 col-md-8 col-12 d-flex flex-column align-self-center mx-auto">
                    @include('layouts.errorCheck')
                    <div class="card mt-3 mb-3">
                        <div class="card-body">
                            <form class="loginForm"  method="POST" action="{{ route('admin.pin.reset') }}">
                                    @csrf
                                    <input type="hidden" name="token" value="{{ $token }}">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <div class="text-center">
                                    <h2>{{ getSiteSettings('title') }} Admin Panel</h2>
                                </div>
                                    <p>Enter your admin in to access account</p>

                                </div>

                                <div class="form-custom form-label mt-3">
                                    <label for="pin" class="label">New PIN</label>
                                    <input type="password" name="pin" class="form-control rounded-xs" placeholder="Please Enter your New PIN" required>
                                </div>
                                
                                <div class="form-custom form-label mt-3">
                                    <label for="pin" class="label">Confirm PIN</label>
                                    <input type="password" name="pin_confirmation" class="form-control rounded-xs" placeholder="Please Enter your Confirm PIN" required>
                                </div>

                                <div class="col-12">
                                    <div class="mb-4">
                                        <button type="submit" class="btn btn-secondary w-100 submitRequest"> Reset PIN </button>
                                    </div>
                                </div>


                            </div>
                            </form>

                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>


@endsection


{{--  @section('scripts')

<script>

         $(document).ready(function() {
            checkSubmitForm("loginForm", ["remember_me"]);
         });

</script>

@endsection  --}}

