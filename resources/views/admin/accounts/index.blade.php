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
					<li class="breadcrumb-item"><a href="/">Home</a></li>
					<li class="breadcrumb-item active" aria-current="page">All Foreign Account</li>
				</ol>
			</nav>
		</div>
		<!-- /BREADCRUMB -->
		<div class="row layout-spacing layout-top-spacing" id="cancel-row">
		    
			<div class="col-lg-12">
				<div class="statbox widget box box-shadow">
					<div class="widget-content widget-content-area">
						<div id="item_table_wrapper" class="dataTables_wrapper container-fluid dt-bootstrap4 no-footer">
							<div class="table-responsive">
							    <div class="m-2">
                                    <input type="text" id="tableSearch" class="form-control" placeholder="Search accounts..." style="width:300px">
                                </div>
								<table id="item_table" class="table style-3 dt-table-hover dataTable no-footer" role="grid" aria-describedby="item_table_info" style="width: 2298px;">
									<thead>
										<tr role="row">
											<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 62px;">ID</th>
											<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 62px;">User</th>
											<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 124px;">Account Number</th>
											<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 124px;">Account Name</th>
											<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 124px;">Bank Name</th>
											<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 124px;">Balance</th>
											<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 124px;">Status</th>
											@canany(['view_virtual_accounts_details', 'edit_virtual_accounts', 'delete_virtual_accounts'])
											<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 62px;">Actions</th>
											@endcanany
										</tr>
									</thead>
									<tbody>
									    @forelse($accounts as $account)
                                            <tr>
                                                <td>{{ $account->id }}</td>
                                                <td>{{ $account->user?->profile ? trim($account->user->profile->first_name.' '.$account->user->profile->last_name) : '—' }}</td>
                                                <td>{{ $account->account_number }}</td>
                                                <td>{{ $account->account_name }}</td>
                                                <td>{{ $account->bank_name }}</td>
                                                <td>
                                                    @if($account->currency == 'USD')
                                                    &#36;{{ number_format($account->user?->usd_balance ?? 0, 2) }}
                                                    @elseif($account->currency == 'EUR')
                                                    &#8364;{{ number_format($account->user?->eur_balance ?? 0, 2) }}
                                                    @else
                                                    UNKNOWN
                                                    @endif
                                                </td>
                                                <td>{{ $account->status }}</td>
                                                <td>
                                                    @can('view_virtual_accounts_details')
                                                    <a href="{{ route('admin.foreign.accounts.show', $account->id) }}" class="btn btn-info btn-sm">View</a>
                                                    @endcan
                                                    @can('edit_virtual_accounts')
                                                    <a href="{{ route('admin.foreign.accounts.edit', $account->id) }}" class="btn btn-warning btn-sm">Edit</a>
                                                    @endcan
                                                    @can('delete_virtual_accounts')
                                                    <form action="{{ route('admin.foreign.accounts.destroy', $account->id) }}" method="POST" style="display:inline;">
                                                        @csrf @method('DELETE')
                                                        <button class="btn btn-danger btn-sm" onclick="return confirm('Delete request?')">Delete</button>
                                                    </form>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="6">No foreign account found.</td></tr>
                                        @endforelse
									</tbody>
									<tfoot></tfoot>
								</table>
								{{ $accounts->links() }}
								<div id="item_table_processing" class="dataTables_processing card" style="display: none;">Processing...</div>
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
	</div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('assets/admin/js/general.js') }}"></script>
    

@endsection