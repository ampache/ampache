<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'name', 'slug', 'permissions',
    ];
    protected $casts = [
        'permissions' => 'array',
    ];
    
    public $timestamps = false;
    
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_has_roles');
    }

    public function preferences()
    {
        return $this->belongsToMany(Preference::class, 'preference_has_roles');
    }
    
    public function access_list()
    {
        return $this->belongsToMany(Access_List::class, 'acl_has_roles');
    }
    
    public function hasAccess(array $permissions)
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    private function hasPermission(string $permission)
    {
        return $this->permissions[$permission] ?: false;
    }
}
