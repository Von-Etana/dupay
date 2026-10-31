@extends('admin.layouts.head')

@section('body')



<body class="layout-boxed">
<style>
/* ── Scroll Fix — force native scroll everywhere ─────────────── */
html {
    overflow: auto !important;
    overflow-x: hidden !important;
    height: auto !important;
    -webkit-overflow-scrolling: touch !important;
}
body,
body.layout-boxed {
    overflow: auto !important;
    overflow-x: hidden !important;
    height: auto !important;
    min-height: 100vh !important;
    position: relative !important;
    -webkit-overflow-scrolling: touch !important;
    touch-action: pan-y !important;
}
/* Kill perfect-scrollbar container locks */
.ps,
.ps--active-y,
.ps--active-x {
    overflow: auto !important;
    height: auto !important;
    max-height: none !important;
    position: relative !important;
}
/* Main layout containers */
.main-container,
#container,
body.layout-boxed .main-container {
    overflow: visible !important;
    overflow-y: visible !important;
    height: auto !important;
    max-height: none !important;
    position: relative !important;
    display: flex !important;
}
/* Content area */
#content,
.main-content,
#content.main-content {
    overflow: visible !important;
    overflow-y: visible !important;
    height: auto !important;
    max-height: none !important;
    min-height: 100vh;
    flex: 1;
    -webkit-overflow-scrolling: touch !important;
    touch-action: pan-y !important;
}
/* Sidebar fixed — must not block body scroll */
.sidebar-wrapper {
    position: fixed !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
    -webkit-overflow-scrolling: touch !important;
    touch-action: pan-y !important;
    height: 100vh !important;
}
/* Mac 2013 / older Safari — explicit scroll permission */
body, html, #content, .main-content, .main-container {
    -webkit-overflow-scrolling: touch !important;
    -ms-overflow-style: auto !important;
}
/* Remove any transform that kills GPU scroll on old Mac */
.main-container, #container, #content {
    -webkit-transform: none !important;
    transform: none !important;
    will-change: auto !important;
}
</style>
    <!-- BEGIN LOADER -->
    <div id="load_screen"> <div class="loader"> <div class="loader-content">
        <div class="spinner-grow align-self-center"></div>
    </div></div></div>
    <!--  END LOADER -->

    <!--  BEGIN NAVBAR  -->
    <div class="header-container container-xxl">
        <header class="header navbar navbar-expand-sm expand-header">

            <a href="javascript:void(0);" class="sidebarCollapse">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewbox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-menu"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
            </a>

            {{--  <div class="search-animated toggle-search">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewbox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-search"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <form class="form-inline search-full form-inline search" role="search">
                    <div class="search-bar">
                        <input type="text" class="form-control search-form-control  ml-lg-auto" placeholder="Search...">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewbox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x search-close"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </div>
                </form>
                <span class="badge badge-secondary">Ctrl + /</span>
            </div>  --}}

            <ul class="navbar-item flex-row ms-lg-auto ms-0">



                <li class="nav-item theme-toggle-item">
                    <a href="javascript:void(0);" class="nav-link theme-toggle">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewbox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-moon dark-mode"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewbox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-sun light-mode"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                    </a>
                </li>



                <li class="nav-item dropdown user-profile-dropdown  order-lg-0 order-1">
                    <a href="javascript:void(0);" class="nav-link dropdown-toggle user" id="userProfileDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <div class="avatar-container">
                            <div class="avatar avatar-sm avatar-indicators avatar-online">
                                <img alt="avatar" src="{{asset('assets/admin/src/assets/img/profile-30-2.png')}}" class="rounded-circle">
                            </div>
                        </div>
                    </a>

                    <div class="dropdown-menu position-absolute" aria-labelledby="userProfileDropdown">
                        <div class="user-profile-section">
                            <div class="media mx-auto">
                                <div class="emoji me-2">
                                    &#x1F44B;
                                </div>
                                <div class="media-body">
                                 <h5>Hello, {{ Auth::guard("admin")->user()->username }}</h5>
                                    <p>{{ Auth::guard("admin")->user()->email }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="dropdown-item">
                            <a href="{{ route('admin.security.2fa') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-shield"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg> <span>2FA Security</span>
                            </a>
                        </div>

                        <div class="dropdown-item">
                            <a href="{!! route('AdminLogout') !!}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewbox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-log-out"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg> <span>Log Out</span>
                            </a>
                        </div>
                    </div>

                </li>
            </ul>
        </header>
    </div>
    <!--  END NAVBAR  -->

    <!--  BEGIN MAIN CONTAINER  -->
    <div class="main-container" id="container">

        <div class="overlay"></div>
        <div class="search-overlay"></div>

        @include('admin.layouts.sidebar')

        <div id="content" class="main-content">
        @yield('content')


        <!--  BEGIN FOOTER  -->
        <div class="footer-wrapper mt-0">
            <div class="footer-section f-section-1">
                <p class=""> Copyright © <span class="dynamic-year">2022</span> {{ getSiteSettings('title') }} All rights reserved.  Designed by <a target="_blank" href="https://wa.me/+2349060341324">iSmart Technology</a></p>
            </div>

        </div>
        <!--  END FOOTER  -->

    </div>

    </div>
    <!-- END MAIN CONTAINER -->

@endsection