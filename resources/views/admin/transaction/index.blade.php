@extends('admin.layouts.app')
@section('style')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/src/table/datatable/datatables-2.css')}}">
<link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/css/light/table/datatable/dt-global_style-2.css')}}">
<link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/css/light/table/datatable/custom_dt_custom-2.css')}}">
<link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/css/dark/table/datatable/dt-global_style-2.css')}}">
<link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/plugins/css/dark/table/datatable/custom_dt_custom-2.css')}}">
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

                    @php
                        $transactionModel = app("App\Models\Transaction");
                        $tableFilter = $transactionModel->tableFilter();
                        $bulkUpdateAction = $transactionModel->bulkUpdateAction();
                    @endphp

                    <!-- Table Filter Section -->
                    {!! $tableFilter !!}

                    <!-- Bulk Update Action Section -->
                    {!! $bulkUpdateAction !!}

                    <div class="row layout-spacing">
                        <div class="col-lg-12">
                            <div class="statbox widget box box-shadow">
                                <div class="widget-content widget-content-area">
                                    <div class="table-responsive">
                                        <table id="item_table" class="table style-3 dt-table-hover table-bordered table-striped" style="padding:20px;">
                                            <thead>
                                                <tr>
                                                    <th class="checkbox-column">
                                                        <div class="form-check form-check-primary d-block">
                                                            <input class="form-check-input bulk-checkbox-main" type="checkbox" id="checkAll">
                                                        </div>
                                                    </th>
                                                    @foreach($column as $key => $col)
                                                    <th>{{ $col->name ?? $key }}</th>
                                                    @endforeach
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data will be loaded via DataTables -->
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
</div>
@endsection

@section('scripts')
<script src="{{ asset('assets/admin/src/plugins/src/table/datatable/datatables-2.js') }}"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#item_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ $ajaxUrl }}",
            data: function(d) {
                d.search = $('#ismartSearch').val();
                d.filter = $('#filter').val();
                d.currency = $('#currency_filter').val();
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
            }
        },
        columns: [
            {
                data: 'bulk_checkbox',
                name: 'bulk_checkbox',
                orderable: false,
                searchable: false,
                className: 'checkbox-column'
            },
            @foreach($column as $key => $col)
            {
                data: '{{ $col->column_name ?? $key }}',
                name: '{{ $col->column_name ?? $key }}',
                @if(isset($col->isPrice) && $col->isPrice)
                render: function(data, type, row) {
                    return data || 'N/A';
                }
                @endif
            },
            @endforeach
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false
            }
        ],
        order: [[Object.keys(<?php echo json_encode($column); ?>).length + 1, 'desc']],
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        lengthMenu: [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
        pageLength: 50,
        language: {
            paginate: {
                previous: '<i class="fa fa-angle-left"></i>',
                next: '<i class="fa fa-angle-right"></i>'
            }
        }
    });

    // Real-time filtering for search, type filter, and currency filter
    let filterTimeout;
    
    function applyFilters() {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(function() {
            table.ajax.reload();
        }, 500); // 500ms debounce
    }

    // Real-time search
    $('#ismartSearch').on('keyup', function() {
        applyFilters();
    });

    // Real-time filter changes
    $('#filter, #currency_filter').on('change', function() {
        applyFilters();
    });

    // Apply date filter button
    $('#applyDateFilter').on('click', function() {
        table.ajax.reload();
    });

    // Reset all filters button
    $('#resetAllFilters').on('click', function() {
        $('#ismartSearch').val('');
        $('#filter').val('all');
        $('#currency_filter').val('all');
        $('#start_date').val('');
        $('#end_date').val('');
        table.ajax.reload();
    });

    // Allow Enter key to trigger search
    $('#ismartSearch').on('keyup', function(e) {
        if (e.keyCode === 13) {
            clearTimeout(filterTimeout);
            table.ajax.reload();
        }
    });

    // Bulk checkbox functionality
    $('#checkAll').on('change', function() {
        var isChecked = $(this).is(':checked');
        $('.bulk-checkbox-item').prop('checked', isChecked);
        toggleBulkActionSection();
    });

    $(document).on('change', '.bulk-checkbox-item', function() {
        var allChecked = $('.bulk-checkbox-item:checked').length === $('.bulk-checkbox-item').length;
        $('#checkAll').prop('checked', allChecked);
        toggleBulkActionSection();
    });

    function toggleBulkActionSection() {
        var checkedCount = $('.bulk-checkbox-item:checked').length;
        if (checkedCount > 0) {
            $('.bulkUpdateAction').removeAttr('hidden');
        } else {
            $('.bulkUpdateAction').attr('hidden', true);
        }
    }

    // ── Delete a single transaction ──
    // Delegated on `document`: DataTables re-draws the rows on every ajax
    // reload, so a listener bound to the row itself would die on first redraw.
    $(document).on('click', '.delete_item', function(e) {
        e.preventDefault();

        var $btn      = $(this);
        var url       = $btn.data('url');
        var reference = $btn.data('reference');

        if (!url) {
            alert('Delete route missing for this row.');
            return;
        }

        if (!confirm('Delete transaction ' + reference + '?\n\nThis cannot be undone.')) {
            return;
        }

        // Hide the tooltip so it doesn't stick around after the row is gone
        try {
            var tip = bootstrap.Tooltip.getInstance($btn[0]);
            if (tip) { tip.hide(); }
        } catch (err) {}

        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            data: {
                _token: "{{ csrf_token() }}",
                _method: 'DELETE'
            },
            success: function(response) {
                table.ajax.reload(null, false);

                var msg = (response && response.message)
                    ? response.message
                    : 'Transaction Deleted Successfully.';

                if (typeof swal === 'function') {
                    swal({ title: 'Success', text: msg, type: 'success', confirmButtonText: 'OK' });
                } else {
                    alert(msg);
                }
            },
            error: function(xhr) {
                var msg = 'Could not delete this transaction.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                } else if (xhr.status === 404) {
                    msg = 'Transaction not found — it may already be deleted.';
                } else if (xhr.status === 403) {
                    msg = 'You do not have permission to delete transactions.';
                } else if (xhr.status === 405) {
                    msg = 'Delete route is not registered as a DELETE route.';
                } else if (xhr.status === 419) {
                    msg = 'Session expired. Refresh the page and try again.';
                }

                if (typeof swal === 'function') {
                    swal({ title: 'Error', text: msg, type: 'error', confirmButtonText: 'OK' });
                } else {
                    alert(msg);
                }
            }
        });
    });

    // Bulk update status action
    $(document).on('click', '.submitUpdateBulkUpdateAction', function() {
        var selectedIds = [];
        $('.bulk-checkbox-item:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            alert('Please select at least one transaction');
            return;
        }

        var status = $('select[name="status"]').val();
        
        if (confirm('Are you sure you want to update the status of ' + selectedIds.length + ' transaction(s)?')) {
            $.ajax({
                url: "{{ route('admin.editBulkTransactionStatus') }}",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    ids: selectedIds,
                    status: status
                },
                success: function(response) {
                    alert(response.message);
                    table.ajax.reload();
                    $('.bulkUpdateAction').attr('hidden', true);
                    $('.bulk-checkbox-item').prop('checked', false);
                    $('#checkAll').prop('checked', false);
                },
                error: function(xhr) {
                    alert('Error updating transactions');
                }
            });
        }
    });

    // Verify status from API action
    $(document).on('click', '.submitCheckStatusApiAction', function() {
        var selectedIds = [];
        $('.bulk-checkbox-item:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            alert('Please select at least one transaction');
            return;
        }

        var apiConnectionId = $('select[name="api_connection_id"]').val();
        var autoUpdate = $('select[name="auto_update"]').val();
        var autoRefund = $('select[name="auto_refund"]').val();
        
        if (confirm('Are you sure you want to verify the status of ' + selectedIds.length + ' transaction(s) from API?')) {
            $.ajax({
                url: "{{ route('admin.verifyTransactionApiStatus') }}",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    ids: selectedIds,
                    api_connection_id: apiConnectionId,
                    auto_update: autoUpdate,
                    auto_refund: autoRefund
                },
                success: function(response) {
                    alert(response.message);
                    table.ajax.reload();
                    $('.bulkUpdateAction').attr('hidden', true);
                    $('.bulk-checkbox-item').prop('checked', false);
                    $('#checkAll').prop('checked', false);
                },
                error: function(xhr) {
                    alert('Error verifying transactions');
                }
            });
        }
    });
});
</script>
@endsection