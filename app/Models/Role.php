<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Shetabit\Visitor\Traits\Visitable;

class Role extends Model
{
    use HasFactory;
    use LogsActivity;
    use Visitable;

    public $guarded=[];

    public function permissions()
    {
        return $this->hasMany(RolePermission::class,'role_id','id');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return "Role was {$eventName}";
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function rules ($id=0, $merge=[]) {
        return array_merge(
            [
                'name'  => 'required|unique:roles,name' . ($id ? ",$id" : ''),
                'permissions' => 'required',
            ],
            $merge);
    }


    public  function column()
    {

        $column = [
            "name" => [
                "name" => "Name",
                "description" => "Enter name",
                "column_name" => "name",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "permissions" => [
                "name" => "Permission",
                "description" => "Enter Permission",
                "column_name" => "permissions",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
                "view" => false
            ],

        ];
        return json_decode(json_encode($column));
    }


    public function editMethod($id){
        $data["role"] = Role::findOrFail($id);
        $data["permissions"] = Permission::all();
        $data["modules"] = Module::all();
        $model_name = app("App\Models\\Admin");
        $data["column"] = $model_name->column();
        $data["view"] = "admin.roles._form";
        return $data;
    }

    public function createMethod(){
        $data["permissions"] =Permission::all();
        $data["modules"] =Module::all();
        $data["view"] = "admin.roles._form";
        return $data;
    }


    public function insertMethod($data){
        $role=$this->create([
            'name' => $data['name']
        ]);
        if($data['permissions'])
        {
            $role->permissions()->createMany($data['permissions']);
        }
        return true;
    }

    public function updateMethod($id,$data){

        $role= $this->findOrFail($id);
        $role->update(
            [
                'name' => $data['name']
            ]
        );

        RolePermission::where('role_id',$id)->delete();

        if($data['permissions'])
        {
            $role->permissions()->createMany($data['permissions']);
        }

        return true;
    }

}
