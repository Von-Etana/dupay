<?php

namespace App\Models\Telecom;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

class Coupon extends Model
{
    use HasFactory;


    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [
                'name'  => 'required',
                'code'     => 'required',
                'description'     => '',
                'discount' => 'required',
                'is_active' => 'required',
                'use_limit' => '',
                'start_date' => 'required',
                'end_date' => 'required'
            ],
            $merge);
    }
    public function column() {
        $is_active = [
            [
                'name' => "Available",
                'id' => '1'
            ],
            [
                'name' => 'Unavailable',
                'id' => '0'
            ]
        ];
        $code_type = [
            [
                'name' => 'LETTERS',
                'id' => 'letters'
            ],
            [
                'name' => 'NUMERIC',
                'id' => 'numeric'
            ],
            [
                'name' => 'MIXED',
                'id' => 'mixed'
            ]
        ];
        $column = [
            "name" => [
                'name' => 'Coupon Name',
                'description' => 'The name of the Coupon for easier remembrance',
                'column_name' => 'name',
                'input_type' => '0',
                'required' => 'required',
                'value' => '',
                'show_column' => true
            ],
            "code" => [
                'name' => 'Coupon Code',
                'description' => 'Coupon Code. (Type in GENERATE to Generate a random 6 Digit Coupon)',
                'column_name' => 'code',
                'input_type' => '0',
                'required' => 'required',
                'value' => 'GENERATE',
                'show_column' => true
            ],
            "number_to_generate" => [
                'name' => 'Number of Coupon to Generate',
                'description' => 'Number of coupon to generate (Default is 1)',
                'column_name' => 'number_to_generate',
                'input_type' => '0',
                'required' => '',
                'value' => '1',
                'show_column' => false
            ],
            "length" => [
                'name' => 'Length of the coupon',
                'description' => 'Length of the Coupon (Default is 6)',
                'column_name' => 'length',
                'input_type' => '0',
                'required' => '',
                'value' => '6',
                'show_column' => false
            ],
            "prefix" => [
                'name' => 'Prefix',
                'description' => 'Prefix of the Coupon',
                'column_name' => 'prefix',
                'input_type' => '0',
                'required' => '',
                'value' => '',
                'show_column' => false
            ],
            "suffix" => [
                'name' => 'Suffix',
                'description' => 'Suffix of the Coupon',
                'column_name' => 'suffix',
                'input_type' => '0',
                'required' => '',
                'value' => '',
                'show_column' => false
            ],
            "code_type" => [
                "name" => "How the Code should be",
                "description" => "How the code should be",
                "column_name" => "code_type",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                'show_column' => false,
                "option" => $code_type,
            ],
            "description" => [
                'name' => 'Coupon Description',
                'description' => 'Coupon Description',
                'column_name' => 'description',
                'input_type' => '0',
                'required' => '',
                'value' => '',
            ],
            "discount" => [
                'name' => 'Coupon Discount',
                'description' => 'Discount to be Given',
                'column_name' => 'discount',
                'input_type' => '0',
                'required' => 'required',
                'value' => ''
            ],
            "start_date" => [
                'name' => 'Coupon Starts',
                'description' => 'Coupon Starts On',
                'column_name' => 'start_date',
                'input_type' => '8',
                'required' => 'required',
                'value' => ''
            ],
            "end_date" => [
                'name' => 'Coupon Ends',
                'description' => 'Coupon Ends On',
                'column_name' => 'end_date',
                'input_type' => '8',
                'required' => 'required',
                'value' => '',

            ],
            "status" => [
                "name" => "Is Active",
                "description" => "Coupon Status",
                "column_name" => "status",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $is_active,
            ]
        ];
        return json_decode(json_encode($column));
    }

    public function user() {
        return $this->belongsTo(User::class);
    }


    public function generateCoupon($length, $mode, $prefix, $suffix) {
        // $code = Str::random($length);
        $code = !empty($prefix) ? $prefix : '';
        $char = '';
        if ($mode == 'letters') {
            $char .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }
        if ($mode == 'numeric') {
            $char .= '0123456789';
        }
        if ($mode == 'mixed') {
            $char .= 'ABC0DEF1GHI2JKL3MNO4PQR5STU6VWX7YZ89';
        }

        $code .= substr(str_shuffle($char), 0, $length);

        $code .= !empty($suffix) ? $suffix : '';

        $coupon = $this->where('code', $code)->first();
        if ($coupon) {
            $this->generateCoupon($length, $mode, $prefix, $suffix);
        }

        return $code;
    }


    public function insertMethod($data){

            $code = $data['code'];
            $length = $data['length'] !== null ? $data['length'] : 6;
            $number_to_generate = $data['number_to_generate'];
            $mode = $data['code_type'];
            $prefix = $data['prefix'];
            $suffix = $data['suffix'];

            if ($data['end_date'] < $data['start_date']) {
                session()->flash('message', "End date cannot be lesser than start date");
                Session::flash('type', 'danger');
                Session::flash('title', 'Error');
                 return redirect()->back();
            }

            if ($code == 'GENERATE') {
                for ($i = 0; $i < $number_to_generate; $i++) {
                    $data['code'] = $this->generateCoupon($length, $mode, $prefix, $suffix);
                    $this->create($data);
                }
                return true;
            } else {
                $this->create($data);
                return true;
            }


    }


}
