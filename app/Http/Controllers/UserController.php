<?php

namespace App\Http\Controllers;

use Illuminate\Validation\Rule;
use App\Models\User;
use App\Support\UI;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserCreateRequest;
use App\Events\userUpdatedEvent;
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
        $rules = [
            'username' => 'required|max:255|unique:users',
            'email' => 'required|email|max:225|unique:users',
            'password' => 'required|confirmed|min:4',
        ];
        foreach (config('user.registration_mandatory_fields') as $field) {
            if (!in_array($field, $rules)) {
                $rules[$field] = 'required|max:225';
            }
        }
        if (config('user.captcha_public_reg')) {
            $rules['captcha'] = 'required|captcha';
        }

        return Validator::make($data, $rules);
        
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
        $user    = $this->user->findOrFail($id);
        $maxsize = \App\Support\UI::format_bytes(config('system.avatar_max_size'));

        return view('user.edit', compact('user', 'maxsize'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $rules = array();
        if (!is_null($request->input('email'))) {
            $rules['email'] = 'required|email|max:255|unique:users' ;
        }
        
        if (!is_null($request->input('password'))) {
            $rules['password'] = 'confirmed|min:4';
        }
        
        
        foreach (config('user.registration_mandatory_fields') as $field) {
            if (!in_array($field, $rules)) {
                if ($request->has($field)) {
                    $rules[$field] = 'required|max:225';
                }
            }
        }

        $validated = Validator::make($request->all(), $rules);

        $this->user->findOrFail($id)->fill($request->all())->save();

        return response()->json(
          [  "status" => "User Info updated" ]
        );
 
 //       return back();
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
