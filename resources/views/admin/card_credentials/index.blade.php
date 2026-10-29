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
					<li class="breadcrumb-item active" aria-current="page">All Assigned Cards</li>
				</ol>
			</nav>
		</div>
		<!-- /BREADCRUMB -->
		<div class="row layout-spacing layout-top-spacing" id="cancel-row">
		    @can('add_assigned_cards')
			<div class="col-lg-12">
				<div class="widget-content searchable-container list">
					<div class="seperator-header clearfix">
						<a href="{{ route('admin.card_credentials.create') }}" class="btn btn-primary btn-sm float-right">
						<i class="fa fa-plus"></i> Add New
						</a>
					</div>
				</div>
			</div>
			@endcan
			<div class="col-lg-12">
				<div class="statbox widget box box-shadow">
					<div class="widget-content widget-content-area">
						<div id="item_table_wrapper" class="dataTables_wrapper container-fluid dt-bootstrap4 no-footer">
							<div class="table-responsive">
							    <div class="m-2">
                                    <input type="text" id="tableSearch" class="form-control" placeholder="Search cards..." style="width:300px">
                                </div>
								<table id="item_table" class="table style-3 dt-table-hover dataTable no-footer" role="grid" aria-describedby="item_table_info" style="width: 2298px;">
									<thead>
										<tr role="row">
											<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 62px;">ID</th>
											<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 62px;">User</th>
											<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 124px;">Card PAN</th>
											<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 124px;">Last 6 PAN</th>
											<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 62px;">CVV</th>
											<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 62px;">Band</th>
											<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 62px;">Used?</th>
											<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 62px;">Delivery Status</th>
											<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 62px;">Actions</th>
										</tr>
									</thead>
									<tbody>
									    @foreach($credentials as $cred)
										<tr role="row" class="odd">
											<td>{{ $cred->id }}</td>
                                          <td>{{ $cred->user->profile->first_name.' '.$cred->user->profile->last_name }}</td>
                                          <td>{{ $cred->card_pan }}</td>
                                          <td>{{ $cred->card_pan_last6 }}</td>
                                          <td>{{ $cred->cvv }}</td>
                                          <td>{{ $cred->brand }}</td>
                                          <td>{{ $cred->used ? 'Yes' : 'No' }}</td>
                                          <td>
                                                @if($cred->delivery_status == 'pending')
                                                    <a href="{{ route('admin.card_credentials.edit', $cred) }}" class="text-warning">Pending</a>
                                                @else
                                                    <a href="{{ route('admin.card_credentials.edit', $cred) }}" class="text-success">Delivered</a>
                                                @endif
                                            </td>
                                          <td>
                                            @can('view_assigned_cards')
                                            <!--<a href="{{ route('admin.card_credentials.show', $cred) }}">View</a>-->
                                            @endcan
                                            @can('edit_assigned_cards')
                                            <a href="{{ route('admin.card_credentials.edit', $cred) }}" class="btn btn-blue btn-sm">Edit</a>
                                            @endcan
                                            @can('delete_assigned_cards')
                                            <form method="POST" action="{{ route('admin.card_credentials.destroy', $cred) }}" style="display:inline">
                                              @csrf @method('DELETE')
                                              <button onclick="return confirm('Delete?')" class="btn btn-danger btn-sm" type="submit">Delete</button>
                                            </form>
                                            @endcan
                                          </td>
										</tr>
										@endforeach
									</tbody>
									<tfoot></tfoot>
								</table>
								{{ $credentials->links() }}
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