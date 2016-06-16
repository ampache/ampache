<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username', 'name', 'email', 'password', 'website', 'name_public', 'country', 'city',
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
        switch ($access)
        {
            case 'admin':
            case 100:
                return $this->isAdmin();
            case 'catalogmanager':
            case 75:
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
}
