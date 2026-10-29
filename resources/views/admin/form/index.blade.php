@extends('admin.layouts.app')
@section('title', $page_title)
@section('style')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/src/vanillaSelectBox/vanillaSelectBox.css')}}">
<link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/css/dark/vanillaSelectBox/custom-vanillaSelectBox.css')}}">
<link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/css/light/vanillaSelectBox/custom-vanillaSelectBox.css')}}">
<link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/assets/css/light/forms/switches-2.css')}}">
<link rel="stylesheet" type="text/css" href="https://designreset.com/cork/html/src/plugins/css/dark/editors/quill/quill.snow.css">
<link rel="stylesheet" type="text/css" href="https://designreset.com/cork/html/src/plugins/css/light/editors/quill/quill.snow.css">
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
    <form method="POST" action="{{$form_url}}" enctype="multipart/form-data">
        @csrf

        @if (isset($data) && !isset($copy))
        @method('put')
        @endif
        {{--  @isset($data)
        @method('put')
        @endisset  --}}
 @include('admin.form._form')
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

<script src="{{ asset('assets/admin/js/general.js') }}"></script>
<script src="https://designreset.com/cork/html/src/plugins/src/editors/quill/quill.js"></script>
<script>

    (function($){
        "use strict";
         $('.select2').select2();
    })(jQuery);

    $(document).ready(function() {
    getArtimeType("{{ route('adminGetJsItem') }}");
    get_plan("{{ route('adminGetJsItem') }}");
    getOfferServicePlan("{{ route('adminGetOfferJsItem') }}");
});


    $(document).on('click', '.addMore', function(e){
        var $p = $(this);
        var count = $p.data('count');
        var column = $p.data('column');
        var description = $p.data('description');
        var name = $p.data('name');
        var hasValue = $p.data('hasvalue');
        const addMoreFields = document.getElementById(column+'Fields');
        const newAddMoreFields = document.createElement('div');

        if(hasValue){
            newAddMoreFields.innerHTML = `
        <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="${column}" class="label">${name}</label>

                <input type="text" name="${column}[]" id="${column}" class="form-control" placeholder="${description}">
            </div>
        </div>

        <div class="col-md-6">
            <div class="mb-3">
                <label for="${column}_value" class="label">${name} value</label>
                  <a href="javascript:void(0)" class="badge outline-badge-danger  float-end" onclick="removePayloadField(this)" >Remove</a>
                <input type="text" name="${column}_value[]" id="${column}_value" class="form-control" placeholder="${description} value">
            </div>
        </div>
    </div>
      `;
        }else{
            newAddMoreFields.innerHTML = `
        <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <label for="${column}" class="label">${name}</label>
                <a href="javascript:void(0)" class="badge outline-badge-danger  float-end" onclick="removePayloadField(this)" >Remove</a>
                <input type="text" name="${column}[]" id="${column}" class="form-control" placeholder="${description}">
            </div>
        </div>
    </div>
      `;
        }

      addMoreFields.appendChild(newAddMoreFields);


        });



        function removePayloadField(button) {
            button.parentNode.parentNode.parentNode.remove();
          }


          var quill = new Quill('#page_content_wysiwyg', {
            modules: {
                toolbar: [
                [{ header: [1, 2, false] }],
                ['bold', 'italic', 'underline'],
                ['image', 'code-block']
                ]
            },
            placeholder: 'Write content...',
            theme: 'snow'  // or 'bubble'
        });


        quill.on('text-change', function(delta, source) {
            var justHtml = quill.root.innerHTML;
            $('#page_content').val(justHtml);
         });

</script>
@endsection
