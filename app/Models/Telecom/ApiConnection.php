<?php

namespace App\Models\Telecom;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiConnection extends Model
{
    use HasFactory;
    public $guarded=[];


    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [
                'name'  => 'required|unique:api_connections,name' . ($id ? ",$id" : ''),
                'token'  => '',
                'encrypt_token'  => '',
                'url' => 'required',
                'payload' => '',
                'header' => '',
                'header_type' => '',
                'api_auth_method' => 'required',
                'auth_api_connection_id' => '',
                'success_field'  => 'required',
                'success_response'  => 'required',
                'token_field'  => 'required',
                'auto_refund' => 'required',
                'method'  => 'required',
                'status'  => 'required',
            ],
            $merge);
    }



    public  function column()
    {
        $allApiConnections = ApiConnection::whereStatus("1")->get();
        $option  =
            [
                [
                    "name" => "Available",
                    "id" => "1"
                ],
                [
                    "name" => "Unavailable",
                    "id" => "0"
                ]
            ];

            $headerOption  =
            [
                [
                    "name" => "Add to Header",
                    "id" => "1"
                ],
                [
                    "name" => "Replace Header",
                    "id" => "0"
                ]
            ];

            $refundOption  =
            [
                [
                    "name" => "Yes",
                    "id" => "1"
                ],
                [
                    "name" => "No",
                    "id" => "0"
                ]
            ];
            $tokenEncryptOption  =
            [
                [
                    "name" => "Yes",
                    "id" => "1"
                ],
                [
                    "name" => "No",
                    "id" => "0"
                ]
            ];
            $method  =
            [
                [
                    "name" => "POST Form",
                    "id" => "1"
                ],
                [
                    "name" => "GET",
                    "id" => "0"
                ],
                [
                    "name" => "POST Parameter",
                    "id" => "2"
                ]
            ];
            $tokenMethod  =
            [
                [
                    "name" => "Bearer",
                    "id" => "Bearer"
                ],
                [
                    "name" => "Basic",
                    "id" => "Basic"
                ],
                [
                    "name" => "Token",
                    "id" => "Token"
                ]
            ];

            $apiAuthMethod  =
            [
                [
                    "name" => "Automatic",
                    "id" => "1"
                ],
                [
                    "name" => "Reauth",
                    "id" => "0"
                ]
            ];

        $column = [

            "name" => [
                "name" => "Name",
                "description" => "Enter a short name to identify the api e.g PayDem Data",
                "column_name" => "name",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true,

            ],
            "url" => [
                "name" => "URL",
                "description" => "Enter End Point URL",
                "column_name" => "url",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true,
                "view" => false,
            ],
            "token" => [
                "name" => "Token",
                "description" => "Enter Api Token or Server Key",
                "column_name" => "token",
                "input_type" => "0",
                "value" => "",
                "required" => "",
                "view" => false
            ],
            "tokenMethod" => [
                "name" => "Token Method",
                "description" => "Please Token Method",
                "column_name" => "token_field",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $tokenMethod,
                "view" => false
            ],
            "encrypt_token" => [
                "name" => "Encrypt Token",
                "description" => "Please Token Encrypt Method",
                "column_name" => "encrypt_token",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $tokenEncryptOption,
                "view" => false
            ],
            "payload" => [
                "name" => "Payload",
                "description" => "Enter payload",
                "column_name" => "payload",
                "input_type" => "0",
                "value" => "",
                "required" => "",
                "view" => false,
                "addMore" => [
                    "available" =>  true,
                    "hasValue" => true,
                    "toJson" => true,
                    "valueColumn" => "payload_value",
                    "valueName" => "Payload Value",
                    "valueDescription" => "enter Payload Value",
                ],
            ],
            "header" => [
                "name" => "Header",
                "description" => "Enter header",
                "column_name" => "header",
                "input_type" => "0",
                "value" => "",
                "required" => "",
                "view" => false,
                "addMore" => [
                    "available" =>  true,
                    "hasValue" => true,
                    "toJson" => true,
                    "valueColumn" => "header_value",
                    "valueName" => "Header Value",
                    "valueDescription" => "enter Header Value",
                ],
            ],
            "header_type" => [
                "name" => "Header Value Type",
                "description" => "Please Select",
                "column_name" => "header_type",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $headerOption,
                "view" => false
            ],
            "method" => [
                "name" => "Method",
                "description" => "Please Connect Method",
                "column_name" => "method",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $method,
                "view" => false
            ],
            "success_field" => [
                "name" => "Success Field",
                "description" => "Enter Success filed",
                "column_name" => "success_field",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "view" => false
            ],
            "success_response" => [
                "name" => "Success Response",
                "description" => "Enter Success Response",
                "column_name" => "success_response",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "view" => false
            ],
            "api_auth_method" => [
                "name" => "Api Auth Method",
                "description" => "Please Select",
                "column_name" => "api_auth_method",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $apiAuthMethod,
                "view" => false
            ],
            "auth_api_connection_id" => [
                "name" => "Authentication Api",
                "description" => "Please Select",
                "column_name" => "auth_api_connection_id",
                "input_type" => "4",
                "value" => "",
                "required" => "",
                "option" => $allApiConnections,
                "view" => false
            ],
            "auto_check_fail_transaction" => [
                "name" => "Auto Check Fail Transaction",
                "description" => "Please select",
                "column_name" => "auto_check_fail_transaction",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $refundOption,
                "view" => false
            ],
            "transaction_verify_api_connection_id" => [
                "name" => "Transaction Verification Api",
                "description" => "Please select",
                "column_name" => "transaction_verify_api_connection_id",
                "input_type" => "4",
                "required" => "",
                "value" => "",
                "option" => $allApiConnections,
                "view" => false
            ],
            "auto_refund" => [
                "name" => "Auto Refund",
                "description" => "Please select",
                "column_name" => "auto_refund",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $refundOption,
                "view" => false
            ],
            "status" => [
                "name" => "Status",
                "description" => "Please Select Status",
                "column_name" => "status",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "use_switch" => true,
                "option" => $option,
            ]
        ];
        return json_decode(json_encode($column));
    }


    public function authApiConnection(){
        return $this->belongsTo(ApiConnection::class, "auth_api_connection_id");
    }

    public function transactionVerificationApiConnection(){
        return $this->belongsTo(ApiConnection::class, "transaction_verify_api_connection_id");
    }





}
