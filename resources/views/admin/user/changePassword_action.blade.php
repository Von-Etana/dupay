<form action="{{ route('admin.AdminChangeUserPassword', [$data->profile->username]) }}" method="POST">

    @csrf

    {!! getTextFormInput("password", "Password", "password", "","Enter Password", "required" ) !!}

    <button type="submit" class="btn btn-success w-100">Submit</button>

</form>
