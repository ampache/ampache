<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	// Reset cached roles and permissions
        app()['cache']->forget('spatie.permission.cache');

        // create permissions
        Permission::create(['name' => 'administer roles & permissions']);
        Permission::create(['name' => 'add users']);
        Permission::create(['name' => 'remove users']);
        Permission::create(['name' => 'disable users']);
        Permission::create(['name' => 'add catalogs']);
        Permission::create(['name' => 'delete catalogs']);
        Permission::create(['name' => 'clean catalogs']);
        Permission::create(['name' => 'update catalogs']);
        Permission::create(['name' => 'export catalogs']);
        Permission::create(['name' => 'create acl']);
        Permission::create(['name' => 'manage licenses']);
        Permission::create(['name' => 'manage interface']);
        Permission::create(['name' => 'manage options']);
        Permission::create(['name' => 'manage playlists']);
        Permission::create(['name' => 'manage streaming']);
        Permission::create(['name' => 'manage system']);
        Permission::create(['name' => 'manage localplay']);
        Permission::create(['name' => 'manage modules']);
        Permission::create(['name' => 'upload files']);
        Permission::create(['name' => 'create playlists']);

 
        // create roles and assign existing permissions
        $role = Role::create(['name' => 'Administrator']);
        $role->givePermissionTo('administer roles & permissions');
        $role->givePermissionTo('add users');
        $role->givePermissionTo('remove users');
        $role->givePermissionTo('disable users');
        $role->givePermissionTo('add catalogs');
        $role->givePermissionTo('delete catalogs');
        $role->givePermissionTo('update catalogs');
        $role->givePermissionTo('clean catalogs');
        $role->givePermissionTo('export catalogs');
        $role->givePermissionTo('create acl');
        $role->givePermissionTo('manage licenses');
        $role->givePermissionTo('manage interface');
        $role->givePermissionTo('manage options');
        $role->givePermissionTo('manage playlists');
        $role->givePermissionTo('create playlists');
        $role->givePermissionTo('manage streaming');
        $role->givePermissionTo('manage system');
        $role->givePermissionTo('manage modules');
        $role->givePermissionTo('manage localplay');
        $role->givePermissionTo('upload files');


        $role = Role::create(['name' => 'Catalog_manager']);
        $role->givePermissionTo('add catalogs');
        $role->givePermissionTo('delete catalogs');
        $role->givePermissionTo('update catalogs');
        $role->givePermissionTo('clean catalogs');
        $role->givePermissionTo('export catalogs');
        $role->givePermissionTo('create playlists');
        $role->givePermissionTo('upload files');

        $role = Role::create(['name' => 'User']);
        $role->givePermissionTo('upload files');
        $role->givePermissionTo('create playlists');

  }
}
