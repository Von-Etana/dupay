@extends('admin.layouts.app')
@section('title', $page_title)
@section('style')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/src/vanillaSelectBox/vanillaSelectBox.css')}}">
<link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/css/dark/vanillaSelectBox/custom-vanillaSelectBox.css')}}">
<link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/css/light/vanillaSelectBox/custom-vanillaSelectBox.css')}}">
<link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/assets/css/light/forms/switches-2.css')}}">
@endsection

@section('content')
<div class="layout-px-spacing">
    <div class="middle-content container-xxl p-0">

<!-- BREADCRUMB -->
<div class="page-meta">
    <nav class="breadcrumb-style-one" aria-label="breadcrumb">
        <ol class="breadcrumb">
            @foreach ($breadcrumb as $nav)
                @if ($nav["url"] != null)
                <li class="breadcrumb-item"><a href="{{ $nav["url"] }}">{{ $nav["title"] }}</a></li>
                @else
                <li class="breadcrumb-item active" aria-current="page">{{ $nav["title"] }}</li>
                @endif
            @endforeach
        </ol>
    </nav>
</div>
<!-- /BREADCRUMB -->

<div class="row">
<div id="flStackForm" class="col-lg-12 layout-spacing layout-top-spacing">
    @include('layouts.errorCheck')
    <div class="statbox widget box box-shadow">
        <div class="widget-header">
            <div class="row">
                <div class="col-xl-12 col-md-12 col-sm-12 col-12">
                    <h4>{{ $page_sub_title }}</h4>
                </div>
            </div>
        </div>
        <div class="widget-content widget-content-area">
    <form method="POST" action="{{$form_url}}">
        @csrf
        @isset($role)
        @method('put')
        @endisset

        {!! getTextFormInput("text", "Role Name", "name",isset($role) ? $role->name: "" ,"Enter your role name", "required") !!}


            <div class="card card-primary card-outline mb-3">
                <div class="card-header">
                    <h5 class="card-title">
                        {{__('Permissions')}}
                    </h5>
              <span class="float-right">
                <button type="button" class="btn btn-danger btn-icon btn-rounded deselect_all_modules mr-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x-octagon"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"></polygon><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                </button>

                <button type="button" class="btn btn-success btn-icon btn-rounded select_all_modules">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-check-circle"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                </button>
              </span>
                </div>

                <div class="card-body">
                    <div class="row">
                        @foreach($modules as $module)
                        <div class="col-4 mb-2">
                            <div class="card card-primary card-outline">
                                <div class="card-header">
                                   {{__(ucfirst($module['name']))}}
                                  <span class="float-right">
                                    <button type="button" class="btn btn-danger btn-icon btn-rounded deselect_module mr-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x-octagon"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"></polygon><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                                    </button>

                                    <button type="button" class="btn btn-success btn-icon btn-rounded  select_module">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-check-circle"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                    </button>
                                  </span>
                                </div>
                                <div class="card-body roleCheck">
                                    @foreach($module['permissions'] as $permission)
                                    @can($permission['key'])
                                    <div class="row">
                                        <div class="col-lg-9">
                                            <label for="{{$permission['key']}}">{{__(ucfirst($permission['name']))}}</label>
                                        </div>
                                        <div class="col-lg-3">

                                            <div class="switch form-switch-custom switch-inline form-switch-success">
                                                <input class="switch-input" name="permissions[][permission_id]" value="{{$permission['id']}}"
                                                id="{{$permission['key']}}" type="checkbox" role="switch" id="form-custom-switch-primary">

                                            </div>
                                        </div>
                                    </div>
                                    @endcan
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>


 {{--  @include('admin.form._form')  --}}
 <button type="submit" class="btn btn-primary">Submit</button>
    </form>

    </div>

    </div>
</div>
</div>

</div>
</div>
@endsection
@section('scripts')
<script src="{{ asset('assets/admin/js/roles.js')}}"></script>
<script>
    (function($){

        "use strict";

        @if(isset($role))
            @foreach($role['permissions'] as $permission)
                $("#{{$permission['permission']['key']}}").prop('checked',true);
            @endforeach
        @endif

    })(jQuery);
</script>
@endsection
