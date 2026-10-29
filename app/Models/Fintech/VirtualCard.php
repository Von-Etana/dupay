<?php

namespace App\Models\Fintech;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class VirtualCard extends Model
{
    use HasFactory;
    protected $fillable = [];
    
    public $guarded=[];

    public function user(){
        return $this->belongsTo(User::class);
    }


    public function column()
{
    $option = [
        [
            "name" => "Available",
            "id" => "1"
        ],
        [
            "name" => "Unavailable",
            "id" => "0"
        ]
    ];

    $column = [
        "user_id" => [
            "name" => "User",
            "description" => "Please Select a User",
            "column_name" => "user_id",
            "input_type" => "4",
            "required" => "required",
            "value" => "",
            "model" => "user->profile->username",
            "link" => route('admin.user.show', '@@this@@')
        ],
        "trans_id" => [
            "name" => "Transaction ID",
            "description" => "Transaction ID (Optional)",
            "column_name" => "trans_id",
            "input_type" => "0",
            "value" => "",
            "required" => "",
        ],
        "card_id" => [
            "name" => "Card ID",
            "description" => "Unique Card ID from Payscribe",
            "column_name" => "payscribe_card_id",
            "input_type" => "0",
            "required" => "required",
            "value" => "",
        ],
        "card_type" => [
            "name" => "Card Type",
            "description" => "Type of Card",
            "column_name" => "card_type",
            "input_type" => "0",
            "value" => "",
            "required" => "required",
        ],
        "currency" => [
            "name" => "Currency",
            "description" => "Card Currency",
            "column_name" => "currency",
            "input_type" => "0",
            "value" => "",
            "required" => "required",
        ],
        "brand" => [
            "name" => "Brand",
            "description" => "Card Brand",
            "column_name" => "brand",
            "input_type" => "0",
            "value" => "",
            "required" => "required",
        ],
        "name" => [
            "name" => "Card Name",
            "description" => "Name on Card",
            "column_name" => "name",
            "input_type" => "0",
            "value" => "",
            "required" => "required",
        ],
        "first_six" => [
            "name" => "First 6 Digits",
            "description" => "Enter First 6 Digits of Card",
            "column_name" => "first_six",
            "input_type" => "0",
            "value" => "",
            "required" => "required",
        ],
        "last_four" => [
            "name" => "Last 4 Digits",
            "description" => "Enter Last 4 Digits of Card",
            "column_name" => "last_four",
            "input_type" => "0",
            "value" => "",
            "required" => "required",
        ],
        "masked" => [
            "name" => "Masked Number",
            "description" => "Masked Card Number",
            "column_name" => "masked",
            "input_type" => "0",
            "value" => "",
            "required" => "required",
        ],
        "number" => [
            "name" => "Full Card Number",
            "description" => "Card Number (For Admin Only)",
            "column_name" => "number",
            "input_type" => "0",
            "value" => "",
            "required" => "required",
        ],
        "expiry" => [
            "name" => "Expiry Date",
            "description" => "Enter Card Expiry",
            "column_name" => "expiry",
            "input_type" => "0",
            "value" => "",
            "required" => "required",
        ],
        "ccv" => [
            "name" => "CCV",
            "description" => "Card Security Code",
            "column_name" => "ccv",
            "input_type" => "0",
            "value" => "",
            "required" => "required",
        ],
        "status" => [
            "name" => "Status",
            "description" => "Select Card Status",
            "column_name" => "status",
            "input_type" => "4",
            "required" => "required",
            "value" => "",
            "option" => $option,
        ],
    ];

    return json_decode(json_encode($column));
}

}
