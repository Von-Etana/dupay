<?php

namespace App\Models\Fintech;

use App\Models\Status;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Shetabit\Visitor\Traits\Visitable;

class WithdrawalLog extends Model
{
    use HasFactory;

    use Visitable;
    public $guarded=[];

    public function user(){
        return $this->belongsTo(User::class);
    }


    public function transaction(){
        return $this->hasOne(Transaction::class,  "reference", "referrence");
    }



    public  function column()
    {
        $Typeoption  =
            [
                [
                    "name" => "Credit",
                    "id" => "1"
                ],
                [
                    "name" => "Debit",
                    "id" => "0"
                ]
            ]
        ;
        $option  = Status::all();
        $column = [
            "username" => [
                "name" => "User",
                "description" => "Please Select",
                "column_name" => "username",
                "input_type" => "4",
                "required" => "",
                "value" => "",
                "model" => "user->profile->username",
                "link" => route('admin.user.show', '@@this@@')
            ],
            "bank_name" => [
                "name" => "Bank",
                "description" => "Enter BankName",
                "column_name" => "bank_name",
                "input_type" => "0",
                "value" => "",
                "required" => "required",

            ],
            "account_name" => [
                "name" => "Account Name",
                "description" => "Enter account name",
                "column_name" => "account_name",
                "input_type" => "0",
                "required" => "required",
                "value" => "",

            ],
            "account_number" => [
                "name" => "Account Number",
                "description" => "Account Number",
                "column_name" => "account_name",
                "input_type" => "0",
                "editable" => true,
                "value" => "",
                "required" => "required",
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

            "admin_comment" => [
                "name" => "Admin Comment",
                "description" => "Please Select",
                "column_name" => "admin_comment",
                "input_type" => "0",
                "value" => "",
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
                "model" => "tStatus->name",
                "option" => $option,
            ]

        ];
        return json_decode(json_encode($column));
    }


    public function getAmountAttribute(){
        return getPrice($this->attributes['amount']);
     }

     public function getChargeAttribute(){
        return getPrice($this->attributes['charge']);
     }

     public function getInitialBalanceAttribute(){
        return getPrice($this->attributes['initial_balance']);
     }

     public function getFinalBalanceAttribute(){
        return getPrice($this->attributes['final_balance']);
     }



    public function tableFilter(){
        return '
        <div class="ismart-filter">
                        <select id="filter" class="form-control">
                                    <option value="all">Show All </option>
                                    <option value="success"> Success </option>
                                    <option value="pending"> Pending </option>
                                    <option value="cancel"> Cancel </option>
                                    <option value="refund"> Refund </option>
                        </select>
                      </div>
        ';
    }


    public function filterMethod($model, $filter){
        if($model != null && $filter != ""){
            if ($filter == "all") {
            } else if ($filter == "success") {
                $model->where('status', "success");
            } else if ($filter == "pending") {
                $model->where('status', "pending");
            } else if ($filter == "cancel") {
                $model->where('status', "cancel");
            } else if ($filter == "refund") {
                $model->where('status', "refund");
            }
        }
        return $model;
    }


    public function actionView(){
        return "admin.transaction._action";
    }
}

