<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Shetabit\Visitor\Traits\Visitable;

class Permission extends Model
{
    use HasFactory;
    use Visitable;
    public $guarded=[];


    public function module()
    {
        return $this->belongsTo(Module::class,'module_id','id');
    }

    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [
                'name'  => 'required',
                'module_id' => 'required',
                'key' => 'required',
            ],
            $merge);
    }

    public  function column()
    {
        $module = Module::all();

        $column = [
            "name" => [
                "name" => "Name",
                "description" => "Enter name",
                "column_name" => "name",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "module_id" => [
                "name" => "Module",
                "description" => "Please Select",
                "column_name" => "module_id",
                "input_type" => "4",
                "required" => "required",
                "value" => "",
                "model" => "module->id",
                "option" => $module,
                "dataColumn" => ["id" => "id", "name" => "name"]
            ],
            "key" => [
                "name" => "Key",
                "description" => "Enter key",
                "column_name" => "key",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "view"=> false
            ],

        ];
        return json_decode(json_encode($column));
    }
}
