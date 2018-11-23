<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\role_user;
//Importing laravel-permission models
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Services\ArtService as Art;
use App\Services\CoreService;

//Enables us to output flash messaging

class UserController extends Controller
{
    public function __construct()
    {
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //Get all users and pass it to the view
        $user_ids = DB::table('users')->pluck('id');
        $owner    = DB::table('role_users')->min('user_id');

        return view('users.index', ['User_ids' => $user_ids, 'owner' => $owner]);
    }
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //Get all roles and pass it to the view
         $roles = Role::get(); //Get all roles
        return view('users.create', ['roles' => $roles]);
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //Validate name, email and password fields
        $this->validate($request, [
            'username' => 'required|max:120',
            'fullname' => 'required|max:120',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed'
        ]);
        
        $user = User::create($request->only('email', 'username', 'fullname', 'password')); //Retrieving only the email and password data
        
        $roles = $request['roles']; //Retrieving the roles field
        //Checking if a role was selected
        if (isset($roles)) {
            foreach ($roles as $role) {
                $role_r = Role::where('id', '=', $role)->firstOrFail();
                $user->assignRole($role_r); //Assigning role to user
            }
        }
        //Redirect to the users.index view and display message
        return redirect()->route('users.index')
        ->with(
            'flash_message',
            'User successfully added.'
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
        return redirect('users');
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user      = User::findOrFail($id); //Get user with specified id
        $roles     = Role::get(); //Get all roles
        $userRoles = $user->roles()->pluck('name');

        return view('users.edit', compact('user', 'roles'));
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
        $user     = User::findOrFail($id); //Get role specified by id
        $validate = array();
        
        //Validate name, email and password fields
        if (!empty($request['username']) && ($user->username != $request['username'])) {
            $validate[] = array('username' => 'required|max:120');
        }
        if (!empty($request['email']) && ($user->email != $request['email'])) {
            $validate[] = array('email' => 'required|email|unique:users,email,' . $id);
        }
        if (!empty($request['password']) && (bcrypt($request['password']) != $user->password)) {
            $validate[] = array('password' => 'required|min:5|confirmed');
        }

        $required_fields = config('user.registration_mandatory_fields');
        
        if (!empty($required_fields)) {
            foreach ($required_fields as $required_field) {
                $validate[$required_field] = 'required';
            }
        }
        
        $validator = Validator::make($request->all(), $validate);
        
        if ($validator->fails()) {
            return redirect('users/edit')
            ->withErrors($validator)
            ->withInput();
        }
                
        $input = $request->all(); //Retreive the name, email and password fields
        $user->fill($input)->save();
        $roles = $request['roles']; //Retreive all roles
        
        if (isset($roles)) {
            $user->roles()->sync($roles);  //If one or more role is selected associate user to roles
        } else {
            $user->roles()->detach(); //If no role is selected remove exisiting role associated to a user
        }
        
        $this->upload_avatar($user);

        return redirect()->route('users.index')
        ->with(
            'flash_message',
            'User successfully edited.'
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
        
        //Find a user with a given id and delete
        $user = User::findOrFail($id);
        $user->roles()->detach(); //Remove exisiting role associated to a user
        $user->delete();

        return response('Removed', 200);
    }
    
    public function update_avatar($data, $mime = '')
    {
        $art = new Art($this->id, 'user');
        $art->insert($data, $mime);
    }
    
    public function upload_avatar($user)
    {
        $upload = array();
        if (!empty($_FILES['avatar']['tmp_name']) && $_FILES['avatar']['size'] <= config('system.max_avatar_size')) {
            $path_info      = pathinfo($_FILES['avatar']['name']);
            $upload['file'] = $_FILES['avatar']['tmp_name'];
            $upload['mime'] = 'image/' . $path_info['extension'];
            $image_data     = Art::get_from_source($upload, 'user');
            
            if ($image_data) {
                $user->avatar = $image_data;
                $user->save();
            }
        }
    }
    
    public function deleteAvatar($id)
    {
        $user         = User::findOrFail($id); //Get user with specified id
        $user->avatar = null;
        $user->save();

        return response('User Avatar Removed', 200);
    }
}
