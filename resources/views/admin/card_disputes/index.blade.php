@extends('admin.layouts.app')

@section('style')
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
                    @foreach($breadcrumb as $crumb)
                        @if($crumb['url'])
                            <li class="breadcrumb-item"><a href="{{ $crumb['url'] }}">{{ $crumb['title'] }}</a></li>
                        @else
                            <li class="breadcrumb-item active" aria-current="page">{{ $crumb['title'] }}</li>
                        @endif
                    @endforeach
                </ol>
            </nav>
        </div>
        <!-- /BREADCRUMB -->

        <div class="row layout-spacing layout-top-spacing" id="cancel-row">
            <div class="col-lg-12">
                <div class="statbox widget box box-shadow">

                    <div class="widget-header">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h4 class="p-3 mb-0">Physical ATM Card Disputes</h4>
                            </div>
                            <div class="col-md-6 text-md-end p-3">
                                @can('create_card_disputes')
                                <a href="{{ route('admin.card_disputes.create') }}" class="btn btn-primary">
                                    File a dispute
                                </a>
                                @endcan
                            </div>
                        </div>
                    </div>

                    <div class="widget-content widget-content-area">

                        <!-- FILTERS -->
                        <div class="row g-2 mb-3 px-2">
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select id="statusFilter" class="form-select form-select-sm">
                                    <option value="">All statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="submitted">Submitted</option>
                                    <option value="failed">Failed</option>
                                    <option value="won">Won</option>
                                    <option value="lost">Lost</option>
                                    <option value="withdrawn">Withdrawn</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Reason</label>
                                <select id="reasonFilter" class="form-select form-select-sm">
                                    <option value="">All reasons</option>
                                    @foreach($reasons as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button id="resetFilters" class="btn btn-secondary btn-sm w-100">Reset</button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="disputesTable" class="table table-striped table-bordered align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Card</th>
                                        <th>Transaction ID</th>
                                        <th>Amount</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th>StroWallet Ref</th>
                                        <th>Filed</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function () {

    var disputesTable = $('#disputesTable').DataTable({
        processing: true,
        serverSide: true,
        // Newest filing first.
        order: [[0, 'desc']],
        ajax: {
            url: "{{ $ajaxUrl }}",
            data: function (d) {
                d.status = $('#statusFilter').val();
                d.reason = $('#reasonFilter').val();
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            // Computed columns: searchable via filterColumn(), but not sortable
            // because there is no matching column on card_disputes to ORDER BY.
            { data: 'user_name', name: 'user_name', orderable: false },
            { data: 'user_email', name: 'user_email', orderable: false },
            { data: 'masked_pan', name: 'masked_pan' },
            { data: 'transaction_id', name: 'transaction_id' },
            { data: 'amount', name: 'amount' },
            { data: 'reason', name: 'reason' },
            { data: 'status', name: 'status' },
            { data: 'dispute_id', name: 'dispute_id' },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        language: {
            emptyTable: "No disputes filed yet. Use \"File a dispute\" to raise one with StroWallet."
        }
    });

    $('#statusFilter, #reasonFilter').on('change', function () {
        disputesTable.ajax.reload();
    });

    $('#resetFilters').on('click', function () {
        $('#statusFilter').val('');
        $('#reasonFilter').val('');
        disputesTable.ajax.reload();
    });
});
</script>
@endsection
