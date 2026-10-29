@extends('admin.layouts.app')
@section('title', $page_title)

@section('style')



    <link href="{{ asset('assets/admin/src/assets/css/light/dashboard/dash_2.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/admin/src/assets/css/dark/dashboard/dash_2.css') }}" rel="stylesheet" type="text/css" />

    <link href="{{ asset('assets/admin/src/assets/css/light/users/user-profile.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/admin/src/assets/css/dark/users/user-profile.css') }}" rel="stylesheet" type="text/css">

    <link href="{{ asset('assets/admin/src/assets/css/light/components/modal.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/admin/src/assets/css/dark/components/modal.css') }}" rel="stylesheet" type="text/css">

{{--
    <link rel="stylesheet" type="text/css"
        href="{{ asset('assets/admin/src/plugins/src/vanillaSelectBox/vanillaSelectBox.css') }}">  --}}
    <link rel="stylesheet" type="text/css"
        href="{{ asset('assets/admin/src/plugins/css/dark/vanillaSelectBox/custom-vanillaSelectBox.css') }}">
    <link rel="stylesheet" type="text/css"
        href="{{ asset('assets/admin/src/plugins/css/light/vanillaSelectBox/custom-vanillaSelectBox.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/src/assets/css/light/forms/switches-2.css') }}">



    <link rel="stylesheet" type="text/css"
        href="{{ asset('assets/admin/src/plugins/css/light/table/datatable/dt-global_style-2.css') }}">
    <link rel="stylesheet" type="text/css"
        href="{{ asset('assets/admin/src/plugins/css/light/table/datatable/custom_dt_custom-2.css') }}">

    <link rel="stylesheet" type="text/css"
        href="{{ asset('assets/admin/src/plugins/css/dark/table/datatable/dt-global_style-2.css') }}">
    <link rel="stylesheet" type="text/css"
        href="{{ asset('assets/admin/src/plugins/css/dark/table/datatable/custom_dt_custom-2.css') }}">


        <link href="{{ asset('assets/admin/src/plugins/src/flatpickr/flatpickr.css') }}" rel="stylesheet" type="text/css">
        <link href="{{ asset('assets/admin/src/assets/css/light/scrollspyNav.css') }}" rel="stylesheet" type="text/css">
        <link href="{{ asset('assets/admin/src/plugins/css/light/flatpickr/custom-flatpickr.css') }}" rel="stylesheet" type="text/css">
        <link href="{{ asset('assets/admin/src/assets/css/dark/scrollspyNav.css') }}" rel="stylesheet" type="text/css">
        <link href="{{ asset('assets/admin/src/plugins/css/dark/flatpickr/custom-flatpickr.css') }}" rel="stylesheet" type="text/css">


@endsection

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

            
            <div class="row layout-spacing ">

                <!-- Content -->
                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 layout-top-spacing">
                    <div class="card">
                        <div class="card-header">
                            <h4>{{ $page_sub_title }}</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <div class="mb-3">
                                    <select id="currencyFilter" class="form-select" style="width: 200px;">
                                        <option value="">All Currencies</option>
                                        <option value="USD" {{ $currency == 'USD' ? 'selected' : '' }}>USD</option>
                                        <option value="EUR" {{ $currency == 'EUR' ? 'selected' : '' }}>EUR</option>
                                    </select>
                                </div>
                                <table id="transactionsTable" class="table table-striped table-bordered align-middle">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Reference</th>
                                            <th>Type</th>
                                            <th>Kind</th>
                                            <th>Amount</th>
                                            <th>Fee</th>
                                            <th>Amount Settled</th>
                                            <th>Status</th>
                                            <th>Balance Before</th>
                                            <th>Balance After</th>
                                            <th>Payer Name</th>
                                            <th>Payer Bank</th>
                                            <th>Payer Account</th>
                                            <th>Routing Number</th>
                                            <th>Currency</th>
                                            <th>Description</th>
                                            <th>Created At</th>
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

<script src="{{ asset('assets/admin/src/plugins/src/flatpickr/flatpickr.js') }}"></script>
    <script src="{{ asset('assets/admin/src/plugins/src/flatpickr/custom-flatpickr.js') }}"></script>

    <script src="{{ asset('assets/admin/js/general.js') }}"></script>
    
    <script>
        $(function() {
            const table = $('#transactionsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ $ajaxUrl }}',
                    data: function(d) {
                        d.currency = $('#currencyFilter').val();
                    }
                },
                columns: [
                    { data: 'reference_id', title: 'Reference' },
                    { data: 'type', title: 'Type' },
                    { data: 'kind', title: 'Kind' },
                    { data: 'amount', title: 'Amount' },
                    { data: 'fee', title: 'Fee' },
                    { data: 'amount_settled', title: 'Amount Settled' },
                    { data: 'status', title: 'Status' },
                    { data: 'balance_before', title: 'Balance Before' },
                    { data: 'balance_after', title: 'Balance After' },
                    { data: 'payer_name', title: 'Payer Name' },
                    { data: 'payer_bank_name', title: 'Payer Bank' },
                    { data: 'payer_account_number', title: 'Payer Account' },
                    { data: 'payer_routing_number', title: 'Routing Number' },
                    { data: 'currency', title: 'Currency' },
                    { data: 'description', title: 'Description' },
                    { data: 'created_at', title: 'Created At' },
                    { data: 'action', title: 'Action', orderable: false, searchable: false }
                ]
            });
        
            $('#currencyFilter').change(() => table.ajax.reload());
        });
    </script>
    
    <script>
document.addEventListener('DOMContentLoaded', function() {
    $(document).on('click', '.delete_item', function(e) {
        e.preventDefault();
        let form = $(this).closest('form');
        let url = form.attr('action');

        if (confirm('Are you sure you want to delete this transaction?')) {
            $.ajax({
                url: url,
                type: 'POST',
                data: form.serialize(),
                success: function() {
                    alert('Transaction deleted successfully!');
                    $('#transactionsTable').DataTable().ajax.reload(null, false);
                },
                error: function() {
                    alert('An error occurred while deleting the transaction.');
                }
            });
        }
    });
});
</script>

@endsection
