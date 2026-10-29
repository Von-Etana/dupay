<ul class="table-controls">
    @can('view_physical_card_details')
    <li>
        <a href="{{ route('admin.physical_cards.show', $c) }}" class="btn btn-blue btn-sm">View</a>
    </li>
    @endcan
    
    @can('edit_physical_cards')
    <li>
        <a href="{{ route('admin.physical_cards.edit', $c) }}" class="btn btn-blue btn-sm">Edit</a>
    </li>
    @endcan

    @can('delete_physical_cards')
    <li>
        <form method="POST" action="{{ route('admin.physical_cards.destroy', $c) }}" style="display:inline">
            @csrf
            @method('DELETE')
            <button onclick="return confirm('Delete this card?')" class="btn btn-danger btn-sm" type="submit">Delete</button>
        </form>
    </li>
    @endcan
</ul>