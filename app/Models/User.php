<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Crypt;

class User extends Authenticatable
{
    use Notifiable;
    use HasRoles;
    protected $guard_name = 'web';
 
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username', 'full_name', 'email', 'password', 'email_token', 'website', 'state','city', 'zip',
        'subsonic_password', ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
    
    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = bcrypt($password);
    }
    
    public function verifyUser()
    {
        return $this->hasOne('App\Models\VerifyUser');
    }
    
    public function getSubsonicPasswordAttribute($value) {
        if (strlen($value) > 0) {
          return decrypt($value);
        } else {
            return $value;
        }
    }
    
    public function setSubsonicPasswordAttribute($value) {
        
        $this->attributes['subsonic_password'] = encrypt($value);
    }
    
    public function getAvatarAttribute($imageData) {
        $tmp = '';
        if (!is_null($imageData)) {
            $f = finfo_open();
            $mime_type = finfo_buffer($f, $imageData, FILEINFO_MIME_TYPE);
            $tmp = 'data: '. $mime_type . ';base64,'. base64_encode($imageData);
        }
        return $tmp;
    }
}
