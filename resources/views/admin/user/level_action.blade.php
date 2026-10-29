<form action="{{ route('admin.AdminUserLevelAction', [$data->profile->username]) }}" method="POST">
    @csrf

    {{ getOtherFormInput("selectId","Level", "package_id", isset($data) ? $data->level_id : "", $levels, "required")}}
    {{ getOtherFormInput("checkToggle","Debit", "action", "Yes", "Yes,No", "required") }}
    {{ getOtherFormInput("checkToggle","Alert", "alert", "Yes", "Yes,No", "required") }}

        <button type="submit" class="btn btn-primary w-100">Submit</button>

</form>
