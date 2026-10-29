@extends('admin.layouts.app')
@section('style')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/src/table/datatable/datatables-2.css')}}">

<link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/css/light/table/datatable/dt-global_style-2.css')}}">
<link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/css/light/table/datatable/custom_dt_custom-2.css')}}">

<link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/css/dark/table/datatable/dt-global_style-2.css')}}">
<link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/css/dark/table/datatable/custom_dt_custom-2.css')}}">


{{--  <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/src/vanillaSelectBox/vanillaSelectBox.css')}}">  --}}
{{--  <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/css/dark/vanillaSelectBox/custom-vanillaSelectBox.css')}}">
<link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/css/light/vanillaSelectBox/custom-vanillaSelectBox.css')}}">  --}}
<link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/assets/css/light/forms/switches-2.css')}}">

<link href="{{ asset('assets/admin/src/assets/css/light/components/modal.css')}}" rel="stylesheet" type="text/css">


@endsection
@section('title', $page_title)

@section('content')

{{--  <style>
    select.form-control{
      display: inline;
      width: 200px;
      margin-left: 25px;
    }
  </style>  --}}

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

        <div class="row layout-spacing layout-top-spacing" id="cancel-row">
            <div class="col-lg-12">
                <div class="widget-content searchable-container list">

                    @include('layouts.errorCheck')

                    @if ($canCreate)
                    <div class="seperator-header clearfix">
                        <a href="{{ $createUrl }}" class="btn btn-primary btn-sm float-right">
                          <i class="fa fa-plus"></i> {{ __('Add New') }}
                        </a>
                    </div>
                    @endif

                    @if (isset($filterTable))
                    {!! $filterTable !!}
                    @endif

                    {{--  <div class="custom-search">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search">
                        <button class="btn btn-3d btn-success" id="customSearchBtn">Search</button>
                    </div>  --}}

                    <div class="row layout-spacing">
                        <div class="col-lg-12">
                            <div class="statbox widget box box-shadow">
                                <div class="widget-content widget-content-area">



                                    <table id="item_table" class="table style-3 dt-table-hover">
                                        <thead>
                                        </thead>
                                        <tbody>
                                        </tbody>

                                    </table>
                                    @if (isset($bulkUpdateAction))
                                    {!! $bulkUpdateAction !!}
                                    @endif   <button class="btn btn-danger btn-sm float-right ismartDeleteBulk m-3" hidden>Delete Selected</button>
                                </div>
                            </div>
                        </div>
                    </div>



                </div>
            </div>
        </div>

    </div>

</div>
@endsection


@section('scripts')
<script src="{{ asset('assets/admin/js/general.js') }}"></script>
<script>
    var Columns = [{
            data: "bulk_checkbox",
            searchable: false,
            sortable: false,
            orderable: false,
            className: "checkbox-column dt-no-sorting"
        },
        {{--  {
            data: "id",
            title: "Id",
            sortable: true,
            orderable: true
        },  --}}
        @foreach ($column as $item)
            @if (!isset($item->view))
                {
                    data: "{{ $item->column_name }}",
                    title: "{{ $item->name }}",
                    sortable: @if(isset($item->model)) false @else true @endif,
                    searchable: @if(isset($item->model)) false @else true @endif,
                    orderable: @if(isset($item->model)) false @else true @endif
                },
            @endif
        @endforeach {
            data: "action",
            title: "Action",
            sortable: false,
            searchable: false,
            orderable: false,
            className: "text-center"
        }
    ];
    $(document).ready(function() {
        rederTable("{{ $ajaxUrl }}", Columns);
        deleteItem("{{ $page_title }}");
        iSmartPlaceEdit();
        checkboxStatusUpdate();
    });


    $(document).ready(function() {

$('.ismartDeleteBulk').click(function() {

    var post_arr = [];

    // Get checked checkboxes
    $('#item_table input[type=checkbox]').each(function() {
        if (jQuery(this).is(":checked")) {
            var id = this.id;
            var splitid = id.split('_');
            var postid = splitid[1];
            post_arr.push(postid);
            
        }
    });



    if (post_arr.length > 0) {

        swal({
            title: "Action Confirmation",
            text: "Are you sure you want to delete these records",
            showCancelButton: !0,
            closeOnConfirm: !0,
            showLoaderOnConfirm: !0,
        }, function() {
            // AJAX Request
            $.ajax({
                url: "{{ $bulkDeleteUrl }}",
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: function() {
                    $.LoadingOverlay("show");
                },
                data: {
                    data_id: post_arr
                },
                success: function(response) {
                    if (!response.success) {
                        sweetAlert("Oops...", response.message, "error");
                    } else {
                        // $.each(post_arr, function(i, l) {
                        //     $("#tr_" + l).remove();
                        // });
                        sweetAlert("Yes...", "Deleted  Successfully", "success");
                        //alert(response.message);
                        window.location.reload();
                    }

                },
                complete: function(data) {
                    $.LoadingOverlay("hide");
                },
            });
        })
    }
});

$('.submitUpdateBulkUpdateAction, .submitCheckStatusApiAction').click(function() {
    var form = $(this).closest('form');
    var url = form.attr("action");
    var formData = new FormData(form[0]);
    var post_arr = [];

    // Get checked checkboxes
    $('#item_table input[type=checkbox]').each(function() {
        if ($(this).is(":checked")) {
            var id = this.id;
            var splitid = id.split('_');
            var postid = splitid[1];
            post_arr.push(postid);
        }
    });

    if (post_arr.length > 0) {
        formData.append('data_id', post_arr);

        swal({
            title: "Action Confirmation",
            text: "Are you sure you want to update these records",
            showCancelButton: true,
            closeOnConfirm: true,
            showLoaderOnConfirm: true,
        }, function() {
            //form.submit();
            // AJAX Request
            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                cache: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: function() {
                    $.LoadingOverlay("show");
                },
                success: function(response) {
                    
                    // Handle the JSON response from the server
                    console.log(response.message);
                    swal("Oops...", response.message, "error");

                    // If you want to handle the response further, you can do so here
                    if (!response.success) {
                        sweetAlert("Oops...", response.message, "error");
                        alert(response.message);
                    } else {
                        sweetAlert("Yes...", "Update Submited Successfully", "success");
                        //alert(response.message);
                        window.location.reload();
                    }
                    
                },
                complete: function() {
                    $.LoadingOverlay("hide");
                },
            });
        });
    }
});


});
</script>


@endsection
