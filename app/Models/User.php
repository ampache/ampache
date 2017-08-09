<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\Session;
use App\Models\Art;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use App\Support\UI;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username', 'fullname', 'email', 'password', 'website', 'name_public', 'country', 'city', 'avatar',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'access', 'apikey', 'disabled', 'validation',
    ];
    
    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = \Hash::make($password);
    }
    
    public function playlists()
    {
        return $this->hasMany('App\Models\Playlist');
    }
    
    public function isAdmin()
    {
        return (intval($this->attributes['access']) >= 100);
    }
    
    public function isCatalogManager()
    {
        return (intval($this->attributes['access']) >= 75);
    }
    /* && Access::check('interface', 25) */
    public function isContentManager()
    {
        return (intval($this->attributes['access']) >= 50);
    }
    
    public function isRegisteredUser()
    {
        return (intval($this->attributes['access']) >= 25);
    }
    
    public function checkAccess($access)
    {
        switch ($access) {
            case 'admin':
            case 100:
                return $this->isAdmin();
            case 'catalogmanager':
            case 75:store:
                return $this->isCatalogManager();
            case 'contentmanager':
            case 50:
                return $this->isContentManager();
            case 'user':
            case '25':
                return $this->isRegisteredUser();
            default:
                return true;
        }
    }
    public function role()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }
    
    public function is_logged_in()
    {
        $result = \App\Models\Session::select('ip')->where('username', '=', $this->attributes['username'])->get();
        if ($result->count() > 0) {
            return true;
        }
        
        return false;
    } // is_logged_in

    public function is_online($delay = 1200)
    {
        $last   = strtotime($this->attributes['last_seen']);
        $time   = strtotime("now");
        $result = $time - $last;

        return $last;
    } // is_online
    
    public function usage()
    {
        $total = DB::table('songs')->
        where([['object_count.user', '=', $this->attributes['id']],['object_count.object_type','=', 'song']])
        ->leftjoin('object_count', 'songs.id', '=', 'object_count.object_id')
        ->sum('songs.size');

        return UI::format_bytes($total);
    }
    
    public function has_avatar()
    {
        $result = Art::where('image_type', "user") ->where('image_id', $this->attributes['id'])->count();

        return $result > 0 ? true : false;
    }

    public function avatar_url()
    {
        //       $url = "http://localhost/art/22/user";
        $url =  url('art/show', [$this->attributes['id'], 'user']);

        return $url;
    }
    
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_users');
    }
    
    /**
     * Checks if User has access to $permissions.
     */
    public function hasAccess(array $permissions) : bool
    {
        // check if the permission is available in any role
        foreach ($this->roles as $role) {
            if ($role->hasAccess($permissions)) {
                return true;
            }
        }

        return false;
    }
    
    /**
     * Checks if the user belongs to role.
     */
    public function inRole(string $roleSlug)
    {
        return $this->roles()->where('slug', $roleSlug)->count() == 1;
    }
    public function check($type, $level)
    {
        $level = intval($this->attributes['access']);
    
        // Switch on the type
        switch ($type) {
            case 'localplay':
                // Check their localplay_level
                return (AmpConfig::get('localplay_level') >= $level
                || $this->attributes['access'] >= 100);
            case 'interface':
                // Check their standard user level
                return ($this->attributes['access'] >= $level);
            default:
                return false;
        }
    }
}
