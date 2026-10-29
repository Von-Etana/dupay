
    @foreach($admin->roles as $role)
            @if(isset($role->role))
                {{$role->role->name}}
            @endif

    @endforeach

