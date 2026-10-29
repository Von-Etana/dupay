<form action="{{ route('admin.AdminUserWalletAction', [$data->profile->username]) }}" method="POST" class="wallet-action-form" onsubmit="return false;">

    @csrf
    <input type="hidden" name="wallet_id" value="{{ $wallet_id }}">
    {!! getTextFormInput("number", "Amount", "amount", "","Enter Amount", "required" ) !!}
    {!! getTextFormInput("textarea", "Reason", "reason", "","Enter Reason", "" ) !!}

    {{ getOtherFormInput("checkToggle","Action", "action", "Credit", "Credit,Debit", "required") }}
    {{ getOtherFormInput("checkToggle","Remove Charge", "remove_charge", "Yes", "Yes,No", "required") }}
    {{ getOtherFormInput("checkToggle","Alert", "alert", "Yes", "Yes,No", "required") }}

        <button type="button" class="btn btn-success w-100 wallet-action-btn">Submit</button>

</form>
