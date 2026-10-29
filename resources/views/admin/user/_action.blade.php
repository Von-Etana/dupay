
<ul class="table-controls">
    @can('view_user_details')
    <li>
        <a href="{{route('admin.user.show',$item->profile->username ?? $item->id)}}" class="bs-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="View" data-original-title="View">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewbox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye p-1 br-8 mb-1"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
    </a>
</li>
@endcan

<li>
        <a href="{{route('admin.user.edit',$item->id)}}" class="bs-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit" data-original-title="Edit">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-edit-2 p-1 br-8 mb-1"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>
        </a>
</li>

    @can('delete_user')
    <li>
    <form method="POST" action="{{route('admin.user.destroy',$item->id)}}"  class="d-inline">
        @csrf
            <input type="hidden" name="_method" value="delete">
            <a  href="javascript:void(0);" class="bs-tooltip delete_item" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete" data-original-title="Delete">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewbox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash p-1 br-8 mb-1"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
            </a>
        </form>
    </li>
    @endcan
    </ul>





