@extends('layouts.app')

@section('content')
    <section id="home" class="hero-section">
        <div class="container">
            <div class="row">
                <div class="col-xl-6 col-lg-6 col-md-10 align-self-center">
                    <div class="hero-content">

                        <h1>{{ $page->title }}</h1>
                        <p>{{ getSiteSettings('title') }}</p>
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


    <section id="how" class="feature-section-1 pt-65">
        <div class="container">
            <div class="row align-items-center">

                {!! $page->page_content !!}

            </div>
        </div>
    </section>
@endsection
