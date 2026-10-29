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
					<li class="breadcrumb-item active" aria-current="page">All Virtual Card</li>
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
								@php use Illuminate\Support\Str; @endphp

                                <!-- Search Input -->
                                <div class="m-2">
                                    <input type="text" id="tableSearch" class="form-control" placeholder="Search cards..." style="width:300px">
                                </div>
                                <table id="item_table" class="table style-3 dt-table-hover dataTable no-footer" role="grid">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Card ID</th>
                                            <th>Card Brand</th>
                                            <th>Reference</th>
                                            <th>Balance</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($cards as $card)
                                        @php
                                            $cardPayload = [
                                                'card_id' => $card->card_id,
                                                'public_key' => env('STROWALLET_PUBLIC_KEY'),
                                            ];
                                            $cardData = getCardDetails($cardPayload);
                                        @endphp
                                        <tr>
                                            <td>{{ $card->user->email }}</td>
                                            <td>{{ $card->card_id }}</td>
                                            <td>{{ $card->card_brand }}</td>
                                            <td>{{ $card->reference }}</td>
                                            <td>
                                                @if(isset($cardData['status']) && $cardData['status'] === true && isset($cardData['data']))
                                                    @php
                                                        $cardDetail = $cardData['data'];
                                                    @endphp
                                                    ${{ number_format((float) $cardDetail['balance'], 2) }}
                                                @else
                                                    $0.00
                                                @endif
                                            </td>
                                            <td>
                                                @can('view_dollar_card_details')
                                                <a href="{{ url('/adminoffice/virtual-usd-cards', $card->id) }}" class="btn btn-primary btn-sm">View</a>
                                                @endcan
                                                
                                                @can('delete_dollar_virtual_card')
                                                <form method="POST" action="{{ url('/adminoffice/virtual-usd-cards/destroy', $card->id) }}" style="display:inline">
                                                  @csrf @method('DELETE')
                                                  <button onclick="return confirm('Delete?')" class="btn btn-danger btn-sm" type="submit">Delete</button>
                                                </form>
                                                @endcan
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
								{{ $cards->links() }}
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