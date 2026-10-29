<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        if(isset($this->admin)&&$this->admin==1)
        {
            return [
                'name'=>[
                    'required',
                    Rule::unique('admins')->ignore($this->admin)->whereNull('deleted_at')
                ],
                'username'=>[
                    'required',
                    Rule::unique('admins')->ignore($this->admin)->whereNull('deleted_at')
                ],
                'email'=>[
                    'required',
                    'email',
                    Rule::unique('admins')->ignore($this->admin)->whereNull('deleted_at')
                ]
            ];
        }
        else{
            return [
                'name'=>[
                    'required'
                ],
                'username'=>[
                    'required',
                    Rule::unique('admins')->ignore($this->admin)->whereNull('deleted_at')
                ],
                'email'=>[
                    'required',
                    'email',
                    Rule::unique('admins')->ignore($this->admin)->whereNull('deleted_at')
                ],
                'roles'=>'required',
            ];
        }
    }
}
