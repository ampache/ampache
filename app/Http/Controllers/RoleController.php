<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

//Importing laravel-permission models
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'isAdmin']);//isAdmin middleware lets only users with a //specific permission permission to access these resources
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $roles = Role::all();//Get all roles
        
        return view('roles.index')->with('roles', $roles);
    }
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $permissions = Permission::all();//Get all permissions
        
        return view('roles.create', ['permissions' => $permissions]);
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //Validate name and permissions field
        $this->validate(
            $request,
            [
            'name' => 'required|unique:roles',
            'permissions' => 'required',
        ]
            );
        
        $name       = $request['name'];
        $role       = new Role();
        $role->name = $name;
        
        $permissions = $request['permissions'];
        
        $role->save();
        //Looping thru selected permissions
        foreach ($permissions as $permission) {
            $p = Permission::where('id', '=', $permission)->firstOrFail();
            //Fetch the newly created role and assign permission
            $role = Role::where('name', '=', $name)->first();
            $role->givePermissionTo($p);
        }
        
        return redirect()->route('roles.index')
        ->with(
            'flash_message',
            'Role' . $role->name . ' added!'
        );
    }
    
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return redirect('roles');
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $role            = Role::findOrFail($id); //Get user with specified id
        $permissions     = Permission::all(); //Get all roles
        $rolePermissions = DB::table('role_has_permissions')->select('name')
        ->join('permissions', 'permission_id', '=', 'permission_id')->where('role_id', $id)->distinct()->get();
        
        foreach ($permissions as $item) {
            $p[] = $item->name;
        }

        return view('roles.edit', compact('role', 'permissions', 'p')); //pass roles and roles data to view
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);//Get role with the given id
        //Validate name and permission fields
        $this->validate($request, [
            'name' => 'required|max:50|unique:roles,name,' . $id,
            'permissions' => 'required',
        ]);
        
        $input       = $request->except(['permissions']);
        $permissions = $request['permissions'];
        $role->fill($input)->save();
        
        $p_all = Permission::all();//Get all permissions
        
        foreach ($p_all as $p) {
            $role->revokePermissionTo($p); //Remove all permissions associated with role
        }
        
        foreach ($permissions as $permission) {
            $p = Permission::where('id', '=', $permission)->firstOrFail(); //Get corresponding form //permission in db
            $role->givePermissionTo($p);  //Assign permission to role
        }
        
        return redirect()->route('roles.index')
        ->with(
            'flash_message',
            'Role' . $role->name . ' updated!'
        );
    }
    
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();
        
        return redirect()->route('roles.index')
        ->with(
            'flash_message',
            'Role deleted!'
        );
    }
}
