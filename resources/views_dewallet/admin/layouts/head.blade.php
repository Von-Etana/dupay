<!DOCTYPE html>
<html lang="en">
<head>
    {{-- Theme bootstrap: apply saved light/dark preference before any CSS paints (no flash). Default = dark. --}}
    <script>
    (function () {
        try {
            var t = localStorage.getItem('dupay-admin-theme');
            if (t === 'light') { document.documentElement.setAttribute('data-theme', 'light'); }
        } catch (e) {}
    })();
    </script>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="{{ getSiteSettings('description') }}">
    <meta property="og:title" content="{{ getSiteSettings('title') }}">
    <meta property="og:description" content="{{ getSiteSettings('description') }}">
    <meta property="og:image" content="{{ asset(getSiteSettings('logo')) }}">
    <meta name="format-detection" content="telephone=no">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="site-url" content="{{ url('/') }}" />
    <meta name="theme-color" content="#070A12">
    <title>@yield('title') | {{ getSiteSettings('title') }} </title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset(getSiteSettings('favicon')) }}">
    <link href="{{ asset('assets/admin/layouts/css/light/loader.css')}}" rel="stylesheet" type="text/css" />
    <script src="{{ asset('assets/admin/layouts/loader.js')}}"></script>
    <link href="{{ asset('assets/admin/src/bootstrap/css/bootstrap.min.css')}}" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/sweetalert.css') }}">
    <link href="{{ asset('assets/admin/src/plugins/src/apex/apexcharts.css')}}" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="{{ asset('assets/css/select2.css')}}" type="text/css">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/src/table/datatable/datatables-2.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/css/dark/table/datatable/dt-global_style-2.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/src/vanillaSelectBox/vanillaSelectBox.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/css/dark/vanillaSelectBox/custom-vanillaSelectBox.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/assets/css/light/forms/switches-2.css')}}">
    @yield('style')
    @include('admin.layouts.theme')
</head>
@yield('body')
@include('admin.layouts.bottom')