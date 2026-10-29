<?php

namespace App\Models\Telecom;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Shetabit\Visitor\Traits\Visitable;
class ExamPin extends Model
{
    use HasFactory;


    protected $fillable = [
        'exam_id',
        'user_id',
        'pin',
        'serial_number',
        'tranx_ref',
        'status',
    ];



    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [
                'exam_id' => 'required',
                'user_id' => '',
                'pin' => 'required|unique:exam_pins,pin' . ($id ? ",$id" : ''),
                'serial_number' => 'unique:exam_pins,serial_number' . ($id ? ",$id" : ''),
                'tranx_ref' => '',
                'status'  => 'required',
            ],
            $merge);
    }

    public  function column()
    {

        $allUsers = User::select(DB::raw("id, email as name"))->get();
        $allExam = Exam::whereStatus("1")->get();
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
            ]
        ;
        $column = [
            "exam_id" => [
                "name" => "Exam",
                "description" => "Please Exam",
                "column_name" => "exam_id",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $allExam,
            ],
            "pin" => [
                "name" => "Exam Pin",
                "description" => "Enter Exam Pin",
                "column_name" => "pin",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true
            ],
            "serial_number" => [
                "name" => "Serial Number",
                "description" => "Enter Serial Number",
                "column_name" => "serial_number",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "editable" => true
            ],
            "user_id" => [
                "name" => "User",
                "description" => "Please Select",
                "column_name" => "user_id",
                "input_type" => "4",
                "required" => "",
                "value" => "",
                "option" => $allUsers,
            ],
            "status" => [
                "name" => "status",
                "description" => "Please Select Plan Type Status",
                "column_name" => "status",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "option" => $option,
                "use_switch" => true
            ]

        ];
        return json_decode(json_encode($column));
    }

    public function exam(){
        return $this->belongsTo(Exam::class);
    }

}
