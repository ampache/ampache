<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Allow Public Registration
    |--------------------------------------------------------------------------
    |
    | This setting turns on/off public registration. It is recommended you leave
    | this off, as it will allow anyone to sign up for an account on your server.
    | Don't forget to set the mail from address further down in the config.
    |
    */
    
    'allow_public_registration' => true,
    
    /*
    |--------------------------------------------------------------------------
    | Notify admin of registrations
    |--------------------------------------------------------------------------
    |
    | This setting turns on/off admin notification of registration.
    |
    */
    
    'admin_notify_reg' => false,
    
    /*
    |--------------------------------------------------------------------------
    | Admin validation required
    |--------------------------------------------------------------------------
    |
    | This setting determines whether the user will be created as a disabled user.
    | If this is on, an administrator will need to manually enable the account
    | before it's usable.
    |
    */
    
    'admin_enable_required' => false,
    
    /*
    |--------------------------------------------------------------------------
    | User Agreement
    |--------------------------------------------------------------------------
    |
    | This will display the user agreement when registering.
    | For agreement text, edit config/registration_agreement.php
    | User will need to accept the agreement before they can register.
    |
    */
    
    'user_agreement' => false,
    
    /*
    |--------------------------------------------------------------------------
    | User no email confirmation
    |--------------------------------------------------------------------------
    |
    | This disable email confirmation when registering.
    |
    */
    
    'user_no_email_confirm' => true,
    
    /*
    |--------------------------------------------------------------------------
    | Registration display fields
    |--------------------------------------------------------------------------
    |
    | The fields that will be shown on user registration page.
    | Username and email fields are always displayed.
    | POSSIBLE VALUES: fullname,website,state,city
    |
    */
    
    'registration_display_fields' => ['name', 'website'],
    
    /*
    |--------------------------------------------------------------------------
    | Registration mandatory fields
    |--------------------------------------------------------------------------
    |
    | This controls which fields are mandatory for registration.
    | Username and email fields are always mandatory.
    | POSSIBLE VALUES: fullname,website,state,city
    |
    */
    
    'registration_mandatory_fields' => ['name'],
    
    /*
    |--------------------------------------------------------------------------
    | Captcha on public registration
    |--------------------------------------------------------------------------
    |
    | Turning this on requires the user to correctly type in the letters in
    | the image created by Captcha.
    |
    */
    
    'captcha_public_reg' => false,
    
    /*
    |--------------------------------------------------------------------------
    | Track User IPs
    |--------------------------------------------------------------------------
    |
    | If this is enabled Ampache will log the IP of every completed login it
    | will store user,ip,time at one row per login.
    |
    */
    
    'track_user_ip' => false,

];
