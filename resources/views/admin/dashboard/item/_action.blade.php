<ul class="table-controls">

    @can('view_detail_'.$pageType)
    <li>
        <a href="{{route('admin.user.show',[$pageType, $item->id])}}" class="bs-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="View" data-original-title="View">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewbox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye p-1 br-8 mb-1"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
    </a>
</li>
    @endcan



    @can('edit_'.$pageType)
            <li>
                <a href="{{route('admin.UpdateItem',[$pageType, $item->id])}}" class="bs-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit" data-original-title="Edit">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewbox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-edit-2 p-1 br-8 mb-1"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>
            </a>
        </li>
    @endcan


    @can('copy_'.$pageType)
    <li>
        <a href="{{route('admin.AddNewItem',[$pageType, $item->id])}}" class="bs-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Copy" data-original-title="Copy">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-file-plus"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
    </a>
</li>
@endcan


    @can('delete_'.$pageType)
    <li>
    <form method="POST" action="{{route('admin.DeleteItem',[$pageType, $item->id])}}"  class="d-inline">
        @csrf
            <input type="hidden" name="_method" value="delete">
            <a  href="javascript:void(0);" class="bs-tooltip delete_item" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete" data-original-title="Delete">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewbox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash p-1 br-8 mb-1"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
            </a>
        </form>
    </li>
    @endcan
    </ul>




