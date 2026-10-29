@extends('layouts.app')
@section('content')
    <section id="home" class="hero-section">
        <div class="container">
            <div class="row">
                <div class="col-xl-6 col-lg-6 col-md-10 align-self-center">
                    <div class="hero-content">

                        <h1>Transforming Financial Services with Ease</h1>
                        <p>Experience Seamless Payments With {{ getSiteSettings('title') }}</p>
                        <div class="download-buttons">
                            <ul class="d-flex">
                                <li><a href="{{ getSiteSettings('playstore') }}" class="me-1"><img
                                            src="{{ asset('assets/user/img/hero/download-1.svg') }}" alt=""></a>
                                </li>
                                <li><a href="{{ getSiteSettings('appstore') }}"><img
                                            src="{{ asset('assets/user/img/hero/download-2.svg') }}" alt=""></a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 col-lg-6 align-self-end">
                    <div class="hero-image text-lg-end">
                        <img src="{{ asset('assets/user/img/hero/hero-image.png') }}" alt="hero-image">
                    </div>
                </div>
            </div>
        </div>
    </section>


    <section id="about" class="feature-section-1 pt-65 gray-bg-1">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="feature-image-1">
                        <img src="{{ asset('assets/user/img/features/feature-1.png') }}" alt="feature">
                    </div>
                </div>
                <div class="col-xxl-5 col-lg-6 offset-xxl-1">
                    <div class="feature-content-wrapper">
                        <div class="section-title">
                            <h1 class="mb-50">Your money is well protected and secured</h1>
                        </div>
                        <div class="single-feature">
                            <div class="icon-style feature-icon -rotate-90">
                                <i class="lni lni-invention"></i>
                            </div>
                            <div class="content">
                                <h4>Seamless Payment</h4>
                                <p>Our app allows users to make payments smoothly and securely.
                                </p>
                            </div>
                        </div>
                        <div class="single-feature">
                            <div class="icon-style feature-icon">
                                <i class="lni lni-cup"></i>
                            </div>
                            <div class="content">
                                <h4>Real-time Transaction</h4>
                                <p>Our app provides real-time monitoring of transactions.</p>
                            </div>
                        </div>
                        <div class="single-feature">
                            <div class="icon-style feature-icon -rotate-90">
                                <i class="lni lni-target"></i>
                            </div>
                            <div class="content">
                                <h4>Secure Transaction</h4>
                                <p>The payment app employs robust security measures.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="features-section">
        <h2 class="features-title">Explore Our Services</h2>
        <div class="features-grid">

            @foreach ($services as $item)
                <div class="feature-card">
                    {{-- <div class="feature-icon">💡</div> --}}
                    <div class="feature-icon"><img src="assets/images/icon/{{ $item->image }}"
                            class="avatar avatar-small mb-3" alt="" height="40"></div>
                    <div class="feature-content">
                        <h3>{{ $item->sub_title }}</h3>
                        @php
                            $description = str_replace(
                                '{{ siteName }}',
                                getSiteSettings('title'),
                                $item->description,
                            );
                        @endphp
                        <p>{{ $description }}</p>
                    </div>
                </div>
            @endforeach


        </div>
    </div>

    <section class="feature-section-2 pt-65">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-xxl-5 col-lg-6 order-last order-lg-first">
                    <div class="feature-content-wrapper">
                        <div class="section-title mb-50">
                            <h1>Start Transacting within a sec</h1>
                        </div>
                        <div class="single-feature">
                            <div class="icon-style feature-icon -rotate-90">
                                <i class="lni lni-users"></i>
                            </div>
                            <div class="content">
                                <h4>Download The App</h4>
                                <p>Download {{ getSiteSettings('title') }} on appstore or playstore.</p>
                            </div>
                        </div>
                        <div class="single-feature">
                            <div class="icon-style feature-icon">
                                <i class="lni lni-bolt"></i>
                            </div>
                            <div class="content">
                                <h4>Create an Account</h4>
                                <p>Register a new account in a simple steps.</p>
                            </div>
                        </div>
                        <div class="single-feature">
                            <div class="icon-style feature-icon -rotate-90">
                                <i class="lni lni-crown"></i>
                            </div>
                            <div class="content">
                                <h4>Verify & Start Transaction</h4>
                                <p>Verify your KYC and start transacting.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-6 col-lg-6 offset-xxl-1">
                    <div class="feature-image-2">
                        <img src="{{ asset('assets/user/img/features/feature-2.png') }}" alt="feature">
                    </div>
                </div>
            </div>
        </div>
    </section>


    <section id="how" class="feature-section-1 pt-65 gray-bg-1">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="feature-image-1">
                        <img src="{{ asset('assets/user/img/features/feature-3.png') }}" alt="feature">
                    </div>
                </div>
                <div class="col-xxl-5 col-lg-6 offset-xxl-1">
                    <div class="feature-content-wrapper">
                        <div class="section-title">
                            <h1 class="mb-50">Your money is well protected and secured</h1>
                        </div>
                        <div class="single-feature">
                            <div class="icon-style feature-icon -rotate-90">
                                <i class="lni lni-layers"></i>
                            </div>
                            <div class="content">
                                <h4>Security</h4>
                                <p>The payment app employs robust security measures.
                                </p>
                            </div>
                        </div>
                        <div class="single-feature">
                            <div class="icon-style feature-icon">
                                <i class="lni lni-compass"></i>
                            </div>
                            <div class="content">
                                <h4>
                                    Real-time Transaction</h4>
                                <p>The app provides real-time monitoring of transactions.</p>
                            </div>
                        </div>
                        <div class="single-feature">
                            <div class="icon-style feature-icon -rotate-90">
                                <i class="lni lni-rocket"></i>
                            </div>
                            <div class="content">
                                <h4>Seamless Payment</h4>
                                <p>The app allows users to make payments smoothly and securely.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
