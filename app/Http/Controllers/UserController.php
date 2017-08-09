<?php

namespace App\Http\Controllers;

use Illuminate\Validation\Rule;
use App\Models\User;
use App\Support\UI;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserCreateRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;

//use App\Events\UserRegistered;

class UserController extends Controller
{
    protected $user;
    protected $art;
    
    public function __construct(User $model)
    {
        $this->user = $model;
        $this->middleware('web');
    }
    
     /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $users = $this->user->paginate(config('theme.threshold'));
        $links = $users->setPath('')->render();
        UI::flip_class(['odd','even']);

        return view('user.index', compact('users', 'links'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $maxsize = UI::format_bytes(config('system.avatar_max_size'));

        return view('user.create', compact('maxsize'));
    }

    
    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */

    public function store(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|max:255|unique:users',
            'email' => 'required|email|max:225|unique:users',
            'password' => 'required|confirmed|min:4',
        ]);
  
        if (($request->hasFile('image')) && ($request->file('image')->isValid())) {
            $params   = "|dimensions:min_width=config('system.avatar_min_width'),min_height=config('system.avatar_min_height')";
            $messages = [
                        'max:' => 'The avatar size exceeds max limits.',
                        'dimensions' => "The avatar exceeds maximum dimensions."
                ];
                 
            $validator = Validator::make($request->all(), [
                    'image' => 'image|dimensions:max_width=' . config('system.avatar_max_width') . ',max_height=' . config('system.avatar_max_height') . '|max:' . config('system.avatar_max_size'),
                    ], $messages);
            if ($validator->fails()) {
                $errors = $validator->errors();

                return back()->withErrors($validator)
                        ->withInput();
            }
                                  
            $file = $request->file('image');
            $name = time() . $file->getClientOriginalName();
            $file->move('images/client', $name);
            $ext = pathinfo('images/client/' . $name, PATHINFO_EXTENSION);
        
            $data = array_merge(['avatar' => "images/client/{$name}"], $request->all());
            $user = $this->user->create($data);
        } else {
            $user = $this->user->create($request->all());
        }

        return redirect('/home')->withOk(T_('User created.'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        $user = $this->user->findOrFail($id);
        UI::flip_class(['odd','even']);

        return view('user.details', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $users   = $this->user->findOrFail($id);
        $maxsize = \App\Support\UI::format_bytes(config('system.avatar_max_size'));

        return view('user.edit', compact('users', 'maxsize'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(UserUpdateRequest $request, $id)
    {
        $this->user->findOrFail($id)->fill($request->all())->save();
        
        return redirect('user')->withOk('User updated.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        $this->user->findOrFail($id)->delete();

        return redirect()->back();
    }

    public function disable($id)
    {
        $flight = $this->user->where('id', $id)->update(['disabled' => 1]);
        
        return redirect()->back();
    }
    
    public function enable($id)
    {
        $flight = $this->user->where('id', $id)->update(['disabled' => 0]);
    
        return redirect()->back();
    }
}
