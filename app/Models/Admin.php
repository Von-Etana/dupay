<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Session;
use Spatie\Activitylog\LogOptions;
use Shetabit\Visitor\Traits\Visitor;

use function PHPUnit\Framework\isEmpty;

class Admin extends Authenticatable
{
    use Notifiable,SoftDeletes,LogsActivity;



    protected $table = "admins";

     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        's_password',
        's_pin',
        'pin',
    ];

     /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        's_password',
        'pin',
        's_pin',
        'remember_token',
    ];


     /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function roles()
    {
        return $this->hasMany(AdminRole::class,'admin_id','id');
    }


public function role() {
    return $this->hasOne(AdminRole::class,'admin_id','id')->latestOfMany();

 }

    public function getDescriptionForEvent(string $eventName): string
    {
        return "User was {$eventName}";
    }

    public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults();
}


public function rules ($id=0, $merge=[]) {
    return array_merge(
        [

            'name' => 'required',
            'username'  => 'required|unique:admins,username' . ($id ? ",$id" : ''),
            'email'  => 'required|unique:admins,email' . ($id ? ",$id" : ''),
            'password' => $id ? "" : 'required',
            'pin' => $id ? "" : 'required',
            'status'  => '',
        ],
        $merge);
}


public  function column()
    {

        $role = Role::all();
        $option  =
            [
                [
                    "name" => "Active",
                    "id" => "1"
                ],
                [
                    "name" => "Ban",
                    "id" => "0"
                ]
            ]
        ;
        $column = [
            "name" => [
                "name" => "Name",
                "description" => "Enter fullname",
                "column_name" => "name",
                "input_type" => "0",
                "value" => "",
                "required" => "required",
            ],
            "username" => [
                "name" => "Username",
                "description" => "Enter Username",
                "column_name" => "username",
                "input_type" => "0",
                "value" => "",
                "editable" => true,
                "required" => "required",
            ],
            "email" => [
                "name" => "Email",
                "description" => "Enter Email",
                "column_name" => "email",
                "input_type" => "2",
                "value" => "",
                "required" => "required",
            ],
            "password" => [
                "name" => "Password",
                "description" => "Enter Password",
                "column_name" => "password",
                "input_type" => "1",
                "value" => "",
                "required" => "required",
                "edit" => true,
                "view" => false
            ],
            "pin" => [
                "name" => "Pin",
                "description" => "Enter Pin",
                "column_name" => "pin",
                "input_type" => "1",
                "value" => "",
                "required" => "required",
                "edit" => true,
                "view" => false
            ],
            "role" => [
                "name" => "Role",
                "description" => "Please Select",
                "column_name" => "roles",
                "id" => "roles",
                "input_type" => "4-2",
                "multiple" => ["id" => "role_id", "name" => "name"],
                "required" => "required",
                "value" => "",
                "option" => $role,
                "dataColumn" => ["id" => "id", "name" => "name"]
            ],
            "status" => [
                "name" => "status",
                "description" => "Please Select",
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


    public function insertMethod($data){

        //create new admin
        $admin = new Admin();
        $admin->name = $data['name'];
        $admin->username = $data['username'];
        $admin->email = $data['email'];
        $admin->status = $data['status'];
        $admin->password = bcrypt($data['password']);
        $admin->pin = bcrypt($data['pin']);
        $admin->save();

        $ac['activity'] = "Create new admin with username $admin->username";
        $ac['actions'] =  "create";
        $ac['module'] = "Admin";
        $ac['user_type'] = "2";
        $ac["extra"] = ["id" =>  $admin->id];
        addSiteActitvity(auth()->guard('admin')->user(), $ac);

        //assign roles to user
        if (is_array($data['roles'])) {
            foreach ($data['roles'] as $role) {
                AdminRole::firstOrCreate([
                    'admin_id' => $admin->id,
                    'role_id' => $role
                ]);
            }
        }

            return true;
    }


    public function updateMethod($id,$request){

        if ($id == 1 && auth()->guard("admin")->user()->id != 1) {
            session()->flash('message', "You can't update developer account");
            Session::flash('type', 'danger');
            Session::flash('title', 'Error');
            return redirect()->back();
        }

        //update Admin
        $admin= Admin::findOrFail($id);
        $admin->username=$request['username'];
        $admin->name=$request['name'];
        $admin->email=$request['email'];

        //optional updating password
        if(!empty($request['password']))
        {
            $admin->password=bcrypt($request['password']);
        }
        
        if(!empty($request['pin']))
        {
            $admin->pin = bcrypt($request['pin']);
        }



        $admin->save();

        if($admin['id']!=1)
        {
            //assign roles to user
            $admin->roles()->delete();

            if(is_array($request['roles']))
            {
                foreach($request['roles'] as $role)
                {
                    $admin->roles()->create([
                        'admin_id'=>$id,
                        'role_id'=>$role
                    ]);
                }
            }

        }

        $ac['activity'] = "Updated admin with username $admin->username data";
        $ac['actions'] =  "update";
        $ac['module'] = "Admin";
        $ac['user_type'] = "2";
        $ac["extra"] = ["id" =>  $admin->id, "request" => $request];
        addSiteActitvity(auth()->guard('admin')->user(), $ac);

        return true;
    }

    public function activitySummary(){
        return $this->hasMany(SiteActivity::class, "admin_id", "user_id");
    }

}
