<!DOCTYPE html>
<html class="no-js" lang="">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>@yield('title') {{ getSiteSettings('title') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ getSiteSettings('description') }}">
    <meta property="og:title" content="{{ getSiteSettings('title') }}">
    <meta property="og:description" content="{{ getSiteSettings('description') }}">
    <meta property="og:image" content="{{ asset(getSiteSettings('logo')) }}">
    <meta name="format-detection" content="telephone=no">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="site-url" content="{{ url('/') }}" />
    <!-- favicon -->
    <link rel="shortcut icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">


    <link rel="stylesheet" href="{{ asset('assets/user/css/bootstrap-5.0.0-beta1.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/user/css/LineIcons.2.0.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/user/css/tiny-slider.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/user/css/animate.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/user/css/main.css') }}">
</head>

<body>


    <header class="header">
        <div class="navbar-area">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-12">
                        <nav class="navbar navbar-expand-lg">
                            <a class="navbar-brand" href="{{ url('/') }}">
                                <img src="{{ asset(getSiteSettings('full_logo')) }}" alt="Logo">
                            </a>
                            {{-- <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                                aria-expanded="false" aria-label="Toggle navigation">
                                <span class="toggler-icon"></span>
                                <span class="toggler-icon"></span>
                                <span class="toggler-icon"></span>
                            </button> --}}
                            <div class="collapse navbar-collapse sub-menu-bar" id="navbarSupportedContent">
                                <ul id="nav" class="navbar-nav me-auto">
                                    <li class="nav-item">
                                        <a class="page-scroll active" href="{{ url('/') }}#home">Home</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="page-scroll" href="{{ url('/') }}#features">Features</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="page-scroll" href="{{ url('/') }}#about">About</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="page-scroll" href="{{ url('/') }}#how">How It Works</a>
                                    </li>

                                </ul>
                            </div>

                            <div class="header-btn">
                                <ul class="d-flex align-items-center justify-content-end">
                                    <li><a href="https://app.mydupay.com" class="page-scroll main-btn btn-hover">Get
                                            Started</a></li>
                                </ul>
                            </div>
                        </nav>

                    </div>
                </div>

            </div>

        </div>

    </header>


    @yield('content')



    <footer id="footer" class="footer">
        <div class="container">
            <div class="cta-section pt-120">
                <div class="cta-wrapper">
                    <div class="row justify-content-center">
                        <div class="col-xl-6 col-lg-8">
                            <div class="cta-content-wrapper">
                                <div class="section-title text-center mb-30">
                                    <span>{{ getSiteSettings('title') }}</span>
                                    <h1>Get Our Apps</h1>
                                    <p>Experience Seamless Payments, Download Now!</p>
                                </div>
                                <ul class="d-flex flex-wrap justify-content-center">
                                    <li class="m-1"><a href="#0"> <img
                                                src="{{ asset('assets/user/img/hero/download-1.svg') }}"
                                                alt="play store"> </a></li>
                                    <li class="m-1"><a href="#0"> <img
                                                src="{{ asset('assets/user/img/hero/download-2.svg') }}"
                                                alt="app store"> </a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-content">
            <div class="container">
                <div class="widget-wrapper">
                    <div class="row">
                        <div class="col-xl-4 col-lg-3 col-md-4">
                            <div class="footer-widget widget-info">
                                <div class="logo">
                                    <a href="#0"> <img src="{{ asset(getSiteSettings('full_logo')) }}"
                                            alt="logo" style="height: 200px;">
                                    </a>
                                </div>

                                <p>{{ getSiteSettings('description') }}
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="footer-widget">
                                <h6>Products</h6>
                                <ul class="links">
                                    @php
                                        $pages = App\Models\ExtraPage::where('status', '1')->get();
                                    @endphp
                                    @foreach ($pages as $page)
                                        <li><a href="{{ route('getPages', $page->url) }}">{{ $page->title }}</a>
                                        </li>
                                    @endforeach
                                    {{-- <li><a href="#0">About Us</a></li>
                                    <li><a href="#0">Terms and Condition</a></li>
                                    <li><a href="#0">Privacy Policy</a></li> --}}

                                </ul>
                            </div>
                        </div>

                        <div class="col-md-4 col-sm-6">
                            <div class="footer-widget">
                                <h6>Connect</h6>
                                <ul class="links">
                                    <li><a href="#0"> {{ getSiteSettings('email') }}</a></li>
                                    <li><a href="#0"> {{ getSiteSettings('phone') }}</a></li>
                                </ul>
                                <h6 class="mt-20">Follow Us</h6>
                                <ul class="socials">
                                    <li><a href="{{ getSiteSettings('twitter') }}"><i
                                                class="lni lni-twitter-filled"></i></a></li>
                                    <li><a href="{{ getSiteSettings('facebook') }}"><i
                                                class="lni lni-facebook"></i></a></li>

                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="copyright-wrapper">
                    <p>Copyright © {{ date('Y') }} {{ getSiteSettings('title') }}. All rights
                        reserved.</p>
                </div>
            </div>
        </div>
    </footer>


    <a href="#" class="scroll-top btn-hover">
        <i class="lni lni-chevron-up"></i>
    </a>


    <script src="{{ asset('assets/user/assets/js/bootstrap-5.0.0-beta1.min.js') }}"></script>
    <script src="{{ asset('assets/user/assets/js/tiny-slider.js') }}"></script>
    <script src="{{ asset('assets/user/assets/js/wow.min.js') }}"></script>
    <script src="{{ asset('assets/user/assets/js/main.js') }}"></script>

</body>

</html>
