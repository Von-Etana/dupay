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
					<li class="breadcrumb-item active" aria-current="page">All Funding Charges</li>
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
                                    <input type="text" id="tableSearch" class="form-control" placeholder="Search cards..." style="width:300px">
                                </div>
								<table id="item_table" class="table style-3 dt-table-hover dataTable no-footer" role="grid" aria-describedby="item_table_info" style="width: 2298px;">
									<thead>
										<tr role="row">
											<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 62px;">Currency</th>
											<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 124px;">Minimum Amount</th>
											<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 124px;">Maximum Amount</th>
											<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 124px;">Charge</th>
											<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 62px;">Actions</th>
										</tr>
									</thead>
									<tbody>
									    @foreach($charges as $charge)
										<tr role="row" class="odd">
                                            <td>{{ $charge->currency }}</td>
                                            <td>{{ $charge->minimum_amount }}</td>
                                            <td>{{ $charge->maximum_amount }}</td>
                                            <td>{{ $charge->charge }}</td>
                                            <td>
                                                <a href="{{ route('admin.funding.charge.edit', $charge) }}" class="btn btn-primary btn-sm">Edit</a>
                                            </td>
										</tr>
										@endforeach
									</tbody>
									<tfoot></tfoot>
								</table>
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