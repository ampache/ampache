<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class UserCreateRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if ($this->user() !== null) {
            return $this->user()->isAdmin();
        }
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'username' => 'required|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:4'
        ];
    }
}
