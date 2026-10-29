@extends('admin.layouts.app')
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
@section('title', $page_title)

@section('content')
<div class="layout-px-spacing">
	<div class="middle-content container-xxl p-0">
		<!-- BREADCRUMB -->
		<div class="page-meta">
			<nav class="breadcrumb-style-one" aria-label="breadcrumb">
				<ol class="breadcrumb">
					<li class="breadcrumb-item"><a href="/">Home</a></li>
					<li class="breadcrumb-item active" aria-current="page">All Virtual Card</li>
				</ol>
			</nav>
		</div>
		<!-- /BREADCRUMB -->
		<div class="row layout-spacing layout-top-spacing" id="cancel-row">
			<div class="col-lg-12">
				<div class="statbox widget box box-shadow">
						<div id="item_table_wrapper" class="dataTables_wrapper container-fluid dt-bootstrap4 no-footer"> 
							<div class="table-responsive">
							    @php use Illuminate\Support\Str; @endphp
							    
							    <select id="statusFilter" class="form-select form-select-sm">
                                    <option value="">All Statuses</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="blocked">Blocked</option>
                                    <option value="pending">Pending</option>
                                </select>
                                <br>
                                <table id="cardsTable" class="table table-striped table-bordered align-middle">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>#</th>
                                            <th>User</th>
                                            <th>Email</th>
                                            <th>Card Brand</th>
                                            <th>Masked PAN</th>
                                            <th>Expiry</th>
                                            <th>Currency</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Spending Limit</th>
                                            <th>Permissions</th>
                                            <th>BIN Check Name</th>
                                            <th>Blocked</th>
                                            <th>Block Reason</th>
                                            <th>Blocked At</th>
                                            <th>Card ID</th>
                                            <th>Account ID</th>
                                            <th>Customer ID</th>
                                            <th>Setup Step</th>
                                            <th>Send To</th>
                                            <th>Created At</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                            
                            <!-- Real-time Search Script -->
                            <script>
                            document.getElementById('tableSearch').addEventListener('keyup', function() {
                                let filter = this.value.toLowerCase();
                                let rows = document.querySelectorAll("#item_table tbody tr");
                            
                                rows.forEach(row => {
                                    let text = row.innerText.toLowerCase();
                                    row.style.display = text.includes(filter) ? "" : "none";
                                });
                            });
                            </script>
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
    $(function() {
        const table = $('#cardsTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: '{{ route("admin.user.physical_cards.ajax") }}',
                type: 'GET',
                data: function (d) {
                    d.status = $('#statusFilter').val();
                }
            },
            columns: [
                { data: 'id', title: '#', render: (data, type, row, meta) => meta.row + 1 },
                { data: 'user_name', title: 'User' },
                { data: 'user_email', title: 'Email' },
                { data: 'brand', title: 'Card Brand' },
                { data: 'card_pan', title: 'Masked PAN' },
                { data: 'expiry', title: 'Expiry' },
                { data: 'currency', title: 'Currency' },
                { data: 'type', title: 'Type' },
                { data: 'status', title: 'Status', orderable: false, searchable: false },
                { data: 'spending_limit', title: 'Spending Limit' },
                { data: 'permissions', title: 'Permissions', orderable: false, searchable: false },
                { data: 'bin_check_name', title: 'BIN Check Name' },
                { data: 'blocked', title: 'Blocked' },
                { data: 'block_reason', title: 'Block Reason' },
                { data: 'blocked_at', title: 'Blocked At' },
                { data: 'card_id', title: 'Card ID' },
                { data: 'account_id', title: 'Account ID' },
                { data: 'customer_id', title: 'Customer ID' },
                { data: 'setup_step', title: 'Setup Step' },
                { data: 'send_to', title: 'Send To' },
                { data: 'created_at', title: 'Created At' },
                { data: 'action', title: 'Action', orderable: false, searchable: false }
            ],
            order: [[0, 'desc']],
            language: { processing: '<i class="fa fa-spinner fa-spin"></i> Loading cards...' },
            pageLength: 10,
        });
    
        $('#statusFilter').change(() => table.ajax.reload());
    });
    </script>
    

@endsection