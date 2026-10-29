@extends('admin.layouts.app')
@section('style')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/src/table/datatable/datatables-2.css') }}">

    <link rel="stylesheet" type="text/css"
        href="{{ asset('assets/admin/src/plugins/css/light/table/datatable/dt-global_style-2.css') }}">
    <link rel="stylesheet" type="text/css"
        href="{{ asset('assets/admin/src/plugins/css/light/table/datatable/custom_dt_custom-2.css') }}">

    <link rel="stylesheet" type="text/css"
        href="{{ asset('assets/admin/src/plugins/css/dark/table/datatable/dt-global_style-2.css') }}">
    <link rel="stylesheet" type="text/css"
        href="{{ asset('assets/admin/src/plugins/css/dark/table/datatable/custom_dt_custom-2.css') }}">
@endsection
@section('title', $page_title)

@section('content')
    <div class="layout-px-spacing">
        <div class="middle-content container-xxl p-0">

            <!-- BREADCRUMB -->
            <div class="page-meta">
                <nav class="breadcrumb-style-one" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        @foreach ($breadcrumb as $nav)
                            @if ($nav['url'] != null)
                                <li class="breadcrumb-item"><a href="{{ $nav['url'] }}">{{ $nav['title'] }}</a></li>
                            @else
                                <li class="breadcrumb-item active" aria-current="page">{{ $nav['title'] }}</li>
                            @endif
                        @endforeach
                    </ol>
                </nav>
            </div>
            <!-- /BREADCRUMB -->

            <div class="row layout-spacing layout-top-spacing" id="cancel-row">
                <div class="col-lg-12">
                    <div class="widget-content searchable-container list">

                        @include(enableMobile().'layouts.errorCheck')

                        <div class="seperator-header float-right">
                            @can('create_user')
                                <a href="{{ route('admin.user.create') }}" class="btn btn-primary btn-sm">
                                    <i class="fa fa-plus"></i> {{ __('Add New') }}
                                </a>
                            @endcan
                        </div>

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
            {
                data: "id",
                title: "Id",
                sortable: true,
                searchable: false,
                orderable: true
            },
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
            rederTable("{{ route('admin.getUser') }}", Columns);
            deleteItem("user");
            iSmartPlaceEdit();
            checkboxStatusUpdate();
        });
    </script>

@endsection
