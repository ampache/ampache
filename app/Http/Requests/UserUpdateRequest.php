<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class UserUpdateRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if ($this->user() !== null) {
            return ($this->user()->isAdmin() || $this->segment(2) == $this->user()->id);
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
        $id = $this->segment(2);
        return [
            'username' => 'required|unique:users,username,' . $id,
            'email' => 'required|email|unique:users,email' . $id
        ];
    }
}
