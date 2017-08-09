<?php

use Illuminate\Database\Seeder;
use App\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $admin = Role::create([
            'name' => 'Administrator',
            'slug' => 'administrator',
            'permissions' => [
                'manage-users' => true,
                'manage-catalogs' => true,
                'manage modules' => true,
                'manage-ACLs' => true,
                'manage-server' => true,
                'own-catalogs' => true,
                'access-playlists' => true,
           ]
        ]);
        $user = Role::create([
            'name' => 'User',
            'slug' => 'user',
            'permissions' => [
                'manage-users' => false,
                'manage-catalogs' => false,
                'manage-modules' => false,
                'manage-ACLs' => false,
                'manage-server' => false,
                'own-catalogs' => false,
                'access-playlists' => true,
                ]
        ]);
        $cat_manager = Role::create([
            'name' => 'Catalog_Manager',
            'slug' => 'catalog_manager',
            'permissions' => [
                'manage-users' => false,
                'manage-catalogs' => true,
                'manage-modules' => false,
                'manage-ACLs' => false,
                'manage-server' => false,
                'own-catalogs' => true,
                'access-playlists' => true,
                ]
        ]);
        $content_manager = Role::create([
            'name' => 'Content_Manager',
            'slug' => 'content_manager',
            'permissions' => [
                'manage-users' => false,
                'manage-catalogs' => true,
                'manage-modules' => false,
                'manage-ACLs' => false,
                'manage-server' => false,
                'own-catalogs' => true,
                'access-playlists' => true,
                ]
        ]);
    }
}
