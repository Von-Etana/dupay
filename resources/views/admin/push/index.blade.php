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
					<li class="breadcrumb-item active" aria-current="page">All Push Updates</li>
				</ol>
			</nav>
		</div>
		<!-- /BREADCRUMB -->
		<div class="row layout-spacing layout-top-spacing" id="cancel-row">
			<div class="col-lg-12">
			    @can('add_push_updates')
			    <div class="seperator-header clearfix">
                        <a href="{{ route('admin.push.create') }}" class="btn btn-primary btn-sm float-right">
                          <i class="fa fa-plus"></i> Add New
                        </a>
                </div>
                @endcan
				<div class="statbox widget box box-shadow">
					<div class="widget-content widget-content-area">
						<div id="item_table_wrapper" class="dataTables_wrapper container-fluid dt-bootstrap4 no-footer">
							<div class="table-responsive">
								@php use Illuminate\Support\Str; @endphp

                                {{-- Ordering is done in PushUpdatesController::index() via latest('id'),
                                     so the newest push update is always the first row. Do NOT let
                                     DataTables re-sort this table client-side or that order is lost. --}}
                                <table id="item_table" class="table style-3 dt-table-hover dataTable no-footer" role="grid">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Message</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($updates as $update)
                                        <tr>
                                            <td>{{ $update->title }}</td>
                                            <td>{{ Str::limit($update->message, 90) }}</td>
                                            <td>{{ $update->status }}</td>
                                            <td>{{ optional($update->created_at)->format('d M Y h:i A') }}</td>
                                            <td>
                                                @can('edit_push_updates')
                                                <a href="{{ route('admin.push.edit', $update) }}" class="btn btn-success btn-sm">Edit</a>
                                                @endcan

                                                @can('send_or_resend_push_updates')
                                                <form method="POST" action="{{ route('admin.push.resend', $update) }}" style="display:inline">
                                                    @csrf
                                                    <button class="btn btn-info btn-sm" type="submit">{{ $update->sent == '1' ? 'Resend' : 'Send' }}</button>
                                                </form>
                                                @endcan

                                                @can('delete_push_updates')
                                                <form method="POST" action="{{ route('admin.push.destroy', $update) }}" style="display:inline"
                                                      onsubmit="return confirm('Delete this push update? This cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                                                </form>
                                                @endcan
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="text-center">No data found</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>

                                <div class="mt-3">
                                    {{ $updates->links() }}
                                </div>

								<div id="item_table_processing" class="dataTables_processing card" style="display: none;">Processing...</div>
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