@extends('admin.layouts.head')
@section('title', "Login")

@section('style')
<style>
.auth-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;
  background:radial-gradient(1200px 600px at 50% -10%,rgba(0,229,160,.10),transparent 60%),var(--bg);}
.auth-card{width:100%;max-width:410px;background:var(--panel);border:1px solid var(--line);
  border-radius:18px;padding:34px 30px;box-shadow:0 30px 80px -24px rgba(0,0,0,.7);}
.auth-brand{display:flex;align-items:center;justify-content:center;gap:11px;margin-bottom:24px;}
.auth-brand img{width:38px;height:38px;border-radius:11px;}
.auth-brand .wm{font-family:'Space Grotesk',sans-serif;font-weight:600;font-size:20px;color:var(--text);letter-spacing:-.4px;}
.auth-head{text-align:center;margin-bottom:24px;}
.auth-head h2{font-family:'Space Grotesk',sans-serif;font-size:20px;font-weight:600;margin:0 0 6px;color:var(--text);}
.auth-head p{font-size:13px;color:var(--muted);margin:0;}
.auth-card .btn-secondary{margin-top:6px;letter-spacing:.06em;padding:12px!important;}
.auth-foot{text-align:center;margin-top:20px;font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--faint);}
</style>
@endsection

@section('body')

<body class="form">

    <!-- BEGIN LOADER -->
    <div id="load_screen"><div class="loader"><div class="loader-content">
        <div class="spinner-grow align-self-center"></div>
    </div></div></div>
    <!-- END LOADER -->

    <div class="auth-wrap">
        <div class="auth-card">
            <div class="auth-brand">
                <img src="{{ asset(getSiteSettings('favicon')) }}" alt="logo">
                <span class="wm">{{ getSiteSettings('short_title') }}</span>
            </div>

            @include('layouts.errorCheck')

            <div class="auth-head">
                <h2>Sign in to Admin</h2>
                <p>Enter your email and password to continue</p>
            </div>

            <form class="loginForm" method="POST" action="{{ route("adminAuth.Login") }}" id="loginForm">
                @csrf
                <div class="row">
                    {!! getTextFormInput("text", "Email", "email", "", "Enter your email", "required") !!}
                    {!! getTextFormInput("password", "Password", "password", "", "Enter your password", "required") !!}

                    <div class="col-12">
                        <button class="btn btn-secondary w-100 submitRequest">SIGN IN</button>
                    </div>
                </div>
            </form>

            <div class="auth-foot">{{ getSiteSettings('title') }} · Secure admin access</div>
        </div>
    </div>

@endsection
