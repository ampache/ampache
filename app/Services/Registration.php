<?php

namespace App\Services;

class Registration
{
    /**
     * Construct an instance of Registration service.
     *
     */
    public function __construct()
    {
        
    }
    
    public function isFieldVisible($fieldname)
    {
        return in_array($fieldname, \Config::get('user.registration_display_fields'));
    }
    
    public function isFieldMandatory($fieldname)
    {
        return in_array($fieldname, \Config::get('user.registration_mandatory_fields'));
    }
}