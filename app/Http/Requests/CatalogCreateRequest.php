<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class CatalogCreateRequest extends Request
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
            'name' => 'required',
            'type' => 'required'
        ];
    }
}
