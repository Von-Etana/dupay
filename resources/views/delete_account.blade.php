<!DOCTYPE HTML>
<html lang="en" class="isPWA">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="viewport"
        content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, viewport-fit=cover" />
    <title>Account Deletion | {{ getSiteSettings('title') }} </title>

    <link rel="manifest" href="/manifest.json">
    <meta name="description" content="{{ getSiteSettings('description') }}">
    <meta property="og:title" content="{{ getSiteSettings('title') }}">
    <meta property="og:description" content="{{ getSiteSettings('description') }}">
    <meta property="og:image" content="{{ asset(getSiteSettings('logo')) }}">
    <meta name="format-detection" content="telephone=no">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="site-url" content="{{ url('/') }}" />
    @yield('headScript')
    @yield('extraCss')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/mobile/styles/bootstrap.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/mobile/fonts/bootstrap-icons.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/sweetalert.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/bootstrap-switch-button.min.css') }}">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@500;600;700&amp;family=Roboto:wght@400;500;700&amp;display=swap"
        rel="stylesheet">
    <link rel="manifest" href="{{ asset('_manifest.json') }}">
    <meta id="theme-check" name="theme-color" content="#FFFFFF">
    <!-- FAVICONS ICON -->
    <link rel="shortcut icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/images/favicon.png') }}">
<style>
    @media (min-width: 768px) {

        .page-content {
            width: 60%;
            margin: 0 auto;
          }

          .card-style{
            padding: 20px;
          }

    }
</style>
</head>

<body class="theme-light" data-highlight="blue" style="">
    <div id="preloader">
        <div class="spinner-border color-highlight" role="status"></div>
    </div>
    <div id="page">
            
        <div class="pt-5">
        <div class="card card-style">
<div class="content">
<div class="d-flex">
         <div class="col-md-12">
                                <h5 class="footer-head">Data collection and security</h5>
                                <p class="mt-4">
                                    Please note that {{ getSiteSettings("title") }} collect Username and password as means of authentication hence we provice a means of deleting your account by following the process below
                                    
                                    <u>
                                        <li>1. Login to {{ getSiteSettings("title") }} using the app downloaded on playstore</li>
                                        <li>2. Click On profile icon at homepage</li>
                                        <li>3. Scroll down to the bottom of the page and click Delete Account Button</li>
                                        
                                    </u>
                                    
                                    Your account will be deleted as per request
                                </p>
                 </div>
                            
                            </div></div></div>
                            </div>
                        
                            


 <div class="pt-5">
        <div class="card card-style">
<div class="content">
<div class="d-flex">
         <div class="col-md-12">
                                <h5 class="footer-head">Further Enquiry</h5>
                                <p class="mt-4"><a href="tel:+22349013594787">Tel: {{ getSiteSettings("phone") }}</a></p>
                                
                                <p class="mt-4"><a href="mail:{{ getSiteSettings("email") }}">Mail: {{ getSiteSettings("email") }}</a></p>
                                
                                 <p class="mt-4">Address: {{ getSiteSettings("address") }}</p>
                                
                 </div>
                            
                            </div></div></div>
                            </div>
   
   
   <div class="card card-style">
<div class="content">
<div class="d-flex flex-center items-center">
         <div class="col-md-12">
                                <h5 class="footer-head text-center">iSmart Technology</h5>
                                <p class="mt-4 text-center"><a href="https://dataapp.ng">www.dataapp.ng</a></p>
                 </div>
                            
                            </div></div></div>



    {{-- <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script> --}}
    <script src="{{ asset('assets/mobile/scripts/jquery.js') }}"></script>
    <script src="{{ asset('assets/mobile/scripts/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/mobile/scripts/custom.js') }}"></script>
    <script src="{{ asset('assets/mobile/scripts/plugins.js') }}"></script>

    <script src="{{ asset('assets/js/styleSwitcher.js') }}"></script>

    <script src="{{ asset('assets/js/bootstrap-switch-button.min.js') }}"></script>
    <script src="{{ asset('assets/js/overlay.js') }}"></script>


    <script src="{{ asset('assets/js/ismart.js') }}"></script>
    <script src="{{ asset('assets/js/sweetalert.min.js') }}"></script>

    
    
</body>

</html>


