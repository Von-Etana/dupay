<ul class="table-controls">

    {{-- VIEW TRANSACTION --}}
    @can('view_transaction')
        <li>
            <a href="{{ route('admin.transaction.view', $item->reference) }}"
               class="bs-tooltip"
               data-bs-toggle="tooltip"
               data-bs-placement="top"
               title="View Transaction">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     class="feather feather-eye p-1 br-8 mb-1">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            </a>
        </li>
    @endcan


    {{-- EDIT TRANSACTION STATUS --}}
    @can('edit_transaction')
        <li>
            <a href="javascript:void(0);"
               data-bs-toggle="modal"
               data-bs-target="#EditModal{{ $item->reference }}"
               class="bs-tooltip"
               data-bs-placement="top"
               title="Edit Status">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     class="feather feather-edit-2 p-1 br-8 mb-1">
                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                </svg>
            </a>
        </li>

        {{-- Edit Modal --}}
        <div class="modal fade" id="EditModal{{ $item->reference }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title">Edit Transaction Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <form method="POST"
                          action="{{ route('admin.editTransactionStatus', ['all', $item->reference]) }}">
                        @csrf
                        <div class="modal-body">
                            {{ getOtherFormInput(
                                'select',
                                'Status',
                                'status',
                                $item->status,
                                json_decode(json_encode(['pending', 'success', 'unsuccessful', 'cancel', 'refund'])),
                                'required', '', ''
                            ) }}
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-light-dark" data-bs-dismiss="modal">
                                <i class="flaticon-cancel-12"></i> Discard
                            </button>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan


    {{-- DELETE TRANSACTION --}}
    {{--
        Rows are injected by DataTables over AJAX, so this partial renders
        OUTSIDE the layout. @push('scripts') / @section never fire here (the
        layout uses @yield('scripts'), and there is no @stack anywhere), and an
        inline <script> would re-bind on every table draw. That is exactly why
        the old delete link did nothing but sit on javascript:void(0).

        The click is handled by the delegated listener in
        admin/transaction/index.blade.php, which reads these data-* attributes.
    --}}
    @can('delete_transaction')
        <li>
            <a href="javascript:void(0);"
               class="bs-tooltip delete_item"
               data-bs-toggle="tooltip"
               data-bs-placement="top"
               data-url="{{ route('admin.DeleteTransaction', $item->reference) }}"
               data-reference="{{ $item->reference }}"
               title="Delete Transaction">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     class="feather feather-trash p-1 br-8 mb-1">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
            </a>
        </li>
    @endcan

</ul>