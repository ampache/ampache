<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Soundcloud Username
    |--------------------------------------------------------------------------
    |
    | Here's where you can define your Soundcloud username.
    |
    */

    'username' => env('SOUNDCLOUD_USERNAME'),

    /*
    |--------------------------------------------------------------------------
    | Soundcloud Password
    |--------------------------------------------------------------------------
    |
    | Here's where you can define your Soundcloud password.
    |
    */

    'password' => env('SOUNDCLOUD_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Auto-Connect
    |--------------------------------------------------------------------------
    |
    | When set to true, the user credentials above will be used to connect to
    | SoundCloud automatically, without you having the call the userCredentials
    | method manually. This may be useful to quickly access data of the
    | authenticated user.
    |
    */

    'auto_connect' => env('SOUNDCLOUD_AUTO_CONNECT', false),

];
