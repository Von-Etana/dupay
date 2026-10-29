<?php

namespace App\Models\Fintech;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferFund extends Model
{
    use HasFactory;
    
     protected $fillable = [
        'sender_id',
        'reciever_id',
        'amount',
        'charge',
        'referrence',
        'comment',
        'status',
    ];

    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [
                'sender_id' => 'required',
                'reciever_id' => 'required',
                'amount' => 'required',
                'comment' => '',
            ],
            $merge);
    }

    public function user(){
        return $this->belongsTo(User::class, "sender_id");
    }

    public function reciever(){
        return $this->belongsTo(User::class, "reciever_id");
    }

    public function transaction(){
        return $this->hasOne(Transaction::class,  "reference", "referrence");
    }

    public function actionView(){
        return "admin.transaction._action";
    }


    public  function column()
    {
        $option  =
            [
                [
                    "name" => "Success",
                    "id" => "1"
                ],
                [
                    "name" => "Unsuccessful",
                    "id" => "0"
                ]
            ]
        ;

        $column = [
            "sender_id" => [
                "name" => "Sender",
                "description" => "Please Select",
                "column_name" => "sender_id",
                "input_type" => "4",
                "required" => "",
                "value" => "",
                "model" => "user->profile->username",
                "link" => route('admin.user.show', '@@this@@')
            ],
            "reciever_id" => [
                "name" => "Receiver",
                "description" => "Please Select",
                "column_name" => "reciever_id",
                "input_type" => "4",
                "required" => "",
                "value" => "",
                "model" => "user->profile->username",
                "link" => route('admin.user.show', '@@this@@')
            ],
            "amount" => [
                "name" => "Amount",
                "description" => "Enter Amount",
                "column_name" => "amount",
                "input_type" => "0",
                "value" => "",
                "editable" => true,
                "required" => "required",
                "isPrice" => true
            ],
            "charge" => [
                "name" => "Charge",
                "description" => "Enter Charge",
                "column_name" => "charge",
                "input_type" => "0",
                "value" => "",
                "editable" => true,
                "required" => "required",
                "isPrice" => true
            ],
            "referrence" => [
                "name" => "Reference",
                "description" => "Enter reference",
                "column_name" => "referrence",
                "input_type" => "0",
                "value" => "",
                "editable" => true,
                "required" => "required"
            ],
            "comment" => [
                "name" => "Comment",
                "description" => "Enter comment",
                "column_name" => "comment",
                "input_type" => "0",
                "value" => "",
                "editable" => true,
                "required" => "required",
                "view" => false,
            ],

            "date" => [
                "name" => "Date",
                "description" => "Enter Date",
                "column_name" => "created_at",
                "input_type" => "0",
                "value" => "",
                "required" => "",
                "create" => false,
                "edit" => false
            ],
            "status" => [
                "name" => "status",
                "description" => "Select Status",
                "column_name" => "status",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $option,
            ]

        ];
        return json_decode(json_encode($column));
    }


}
