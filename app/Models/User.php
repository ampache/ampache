<?php

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use DateTime;

class User extends Authenticatable implements MustVerifyEmailContract
{
    use Notifiable, HasRoles, MustVerifyEmail;
 
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
       'id', 'username', 'fullname', 'email', 'password', 'website', 'state','city', 'zip',
        'subsonic_password', 'created_at'];

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
    
    public function getSubsonicPasswordAttribute($value)
    {
        if (strlen($value) > 0) {
            return decrypt($value);
        } else {
            return $value;
        }
    }
    
    public function setSubsonicPasswordAttribute($value)
    {
        if (!is_null($value)) {
            $this->attributes['subsonic_password'] = encrypt($value);
        }
    }
    
    public function getAvatarAttribute($imageData)
    {
        $tmp = '';
        if (!is_null($imageData)) {
            $f         = finfo_open();
            $mime_type = finfo_buffer($f, $imageData, FILEINFO_MIME_TYPE);
            $tmp       = 'data:' . $mime_type . ';base64, ' . base64_encode($imageData);

            return $tmp;
        } else {
            return false;
        }
    }
    public function getUpdatedAtAttribute($value)
    {
        $format = "Y-m-d H:i:s";
        
        return DateTime::createFromFormat($format, $value)->format("m/d/Y");
    }
    public function getCreatedAtAttribute($value)
    {
        $format = "Y-m-d H:i:s";
        
        return DateTime::createFromFormat($format, $value)->format("m/d/Y");
    }
    public function getLastSeenAttribute($value)
    {
        $last_seen = DB::table('sessions')->where('user_id', '=', $this->id)->max('last_activity');
        if (is_null($last_seen)) {
            return 'Never';
        } else {
            $temp = strftime('%m/%d/%y %T', $last_seen);

            return $temp;
        }
    }

    public function getLastLoginAttribute($value)
    {
        if (is_null($value)) {
            return false;
        } else {
            $format  = "Y-m-d H:i:s";
            $date    = DateTime::createFromFormat($format, $value);
            $minutes = new \DateInterval('PT' . config('session.lifetime') . 'M');
            
            $loginTime =  $date->add($minutes);
            $now       = new DateTime("now");

            return ($loginTime < $now);
        }
    }
    
    public function isOnline()
    {
        return Cache::has('user-is-online-' . $this->id);
    }
}
