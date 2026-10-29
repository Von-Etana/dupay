<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <meta name="keywords" content="">
    <meta name="author" content="">
    <meta name="robots" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ getSiteSettings('description') }}">
    <meta property="og:title" content="{{ getSiteSettings('title') }}">
    <meta property="og:description" content="{{ getSiteSettings('description') }}">
    <meta property="og:image" content="{{ asset(getSiteSettings('logo')) }}">
    <meta name="format-detection" content="telephone=no">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="site-url" content="{{ url('/') }}" />

    <title>@yield('title') | {{ getSiteSettings('title') }} </title>

    {{--  @if (Auth::guest())
    @php
    visitor()->visit(); // create a visit log
    @endphp
    @else
    @php
        auth()->user()->visit(); // create a visit log
    @endphp
    @endif  --}}

    {{--  @php
    VisitLog::save();
    @endphp  --}}


    <!-- FAVICONS ICON -->
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset(getSiteSettings('favicon')) }}">

    <link href="{{ asset('assets/admin/layouts/css/light/loader.css')}}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/admin/layouts/css/dark/loader.css')}}" rel="stylesheet" type="text/css" />
    <script src="{{ asset('assets/admin/layouts/loader.js')}}"></script>

    <!-- BEGIN GLOBAL MANDATORY STYLES -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700" rel="stylesheet">
    <link href="{{ asset('assets/admin/src/bootstrap/css/bootstrap.min.css')}}" rel="stylesheet" type="text/css" />
    {{--  <link href="{{ asset('assets/admin/layouts/css/light/plugins.css')}}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/admin/layouts/css/dark/plugins.css')}}" rel="stylesheet" type="text/css" />  --}}

    <link href="https://designreset.com/cork/html/layouts/modern-light-menu/css/light/plugins.css" rel="stylesheet" type="text/css" />
    <link href="https://designreset.com/cork/html/layouts/modern-light-menu/css/dark/plugins.css" rel="stylesheet" type="text/css" />



    <!-- END GLOBAL MANDATORY STYLES -->

    <!--extra -->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/sweetalert.css') }}">


    <!-- BEGIN PAGE LEVEL PLUGINS/CUSTOM STYLES -->
    <link href="{{ asset('assets/admin/src/plugins/src/apex/apexcharts.css')}}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/admin/src/assets/css/light/dashboard/dash_1.css')}}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/admin/src/assets/css/dark/dashboard/dash_1.css')}}" rel="stylesheet" type="text/css" />


    <!-- END PAGE LEVEL PLUGINS/CUSTOM STYLES -->



    <!--end of extra -->
    @yield('style')

    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/assets/css/dark/elements/alert.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/assets/css/light/elements/alert.css')}}">
    <!-- select2 css -->
    <link rel="stylesheet" href="{{ asset('assets/css/select2.css')}}" type="text/css">



    <!-- END PAGE LEVEL PLUGINS/CUSTOM STYLES -->



    <style>
        .float-right{
            float: right;
            display: block;
        }
    </style>

    <style>
        .float-right{
            float: right;
            display: block;
        }

        #sidebar ul.menu-categories li.menu.active > .dropdown-toggle {
    background-color: {{ getSiteSettings('admin_panel_color') }};
}



#sidebar ul.menu-categories li.menu > .dropdown-toggle[aria-expanded=true] {
    background: {{ getSiteSettings('admin_panel_color') }};
}

.btn-primary {
    background-color: {{ getSiteSettings('admin_panel_color') }} !important;
    border-color: {{ getSiteSettings('admin_panel_color') }};
}

.btn-secondary {
    color: #fff !important;
    background-color: {{ getSiteSettings('admin_panel_color') }};
    border-color: {{ getSiteSettings('admin_panel_color') }};
    box-shadow: 0 10px 20px -10px rgba(92, 26, 195, 0.59);
}
    </style>
</head>

@yield('body')



@include('admin.layouts.bottom')
