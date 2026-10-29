@extends('admin.layouts.head')

@section('body')

<body>

    <!-- BEGIN LOADER -->
    <div id="load_screen"><div class="loader"><div class="loader-content">
        <div class="spinner-grow align-self-center"></div>
    </div></div></div>
    <!-- END LOADER -->

    <!-- SIDEBAR -->
    @include('admin.layouts.sidebar')

    <!-- TOPBAR -->
    <div class="header-container">
        <header class="header navbar navbar-expand-sm">
            <a href="javascript:void(0);" class="sidebarCollapse" aria-label="Toggle menu">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
            </a>

            <h1 style="font-family:'Space Grotesk',sans-serif;font-size:18px;font-weight:600;margin:0 0 0 6px;">@yield('title','Dashboard')</h1>

            <ul class="navbar-item flex-row ms-lg-auto ms-auto" style="align-items:center;gap:10px;list-style:none;margin:0;">
                <li class="nav-item dropdown user-profile-dropdown">
                    <a href="javascript:void(0);" class="nav-link dropdown-toggle user" id="userProfileDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <div class="avatar-container">
                            <div class="avatar avatar-sm avatar-indicators avatar-online">
                                <img alt="avatar" src="{{ asset('assets/admin/src/assets/img/profile-30-2.png') }}" class="rounded-circle" style="width:34px;height:34px;border-radius:9px;">
                            </div>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end position-absolute" aria-labelledby="userProfileDropdown">
                        <div class="user-profile-section" style="padding:14px 16px;border-bottom:1px solid var(--line);">
                            <div class="media mx-auto">
                                <div class="media-body">
                                    <h5 style="margin:0;font-size:14px;">Hello, {{ Auth::guard("admin")->user()->username }}</h5>
                                    <p style="margin:2px 0 0;font-size:12px;">{{ Auth::guard("admin")->user()->email }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="dropdown-item" style="padding:10px 16px;">
                            <a href="{!! route('AdminLogout') !!}" style="display:flex;align-items:center;gap:8px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                <span>Log Out</span>
                            </a>
                        </div>
                    </div>
                </li>
            </ul>
        </header>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-container" id="container">
        <div class="overlay"></div>
        <div id="content" class="main-content">

            @yield('content')

            <!-- FOOTER -->
            <div class="footer-wrapper">
                <div class="footer-section f-section-1">
                    <p>Copyright © <span class="dynamic-year">{{ now()->year }}</span> {{ getSiteSettings('title') }}. All rights reserved. Designed by <a target="_blank" href="https://wa.me/+2349060341324">iSmart Technology</a></p>
                </div>
            </div>
        </div>
    </div>

@endsection
