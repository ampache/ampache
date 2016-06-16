<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Validator;
use App\Http\Controllers\Controller;
use App\Events\UserRegistered;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Registration & Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users, as well as the
    | authentication of existing users. By default, this controller uses
    | a simple trait to add these behaviors. Why don't you explore it?
    |
    */

    use AuthenticatesAndRegistersUsers, ThrottlesLogins;

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new authentication controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest', ['except' => 'logout']);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $rules = [
            'username' => 'required|max:255|unique:users',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|confirmed|min:4',
        ];
        foreach (\Config::get('user.registration_mandatory_fields') as $field) {
            if (!in_array($field, $rules)) {
                $rules[$field] = 'required|max:255';
            }
        }
        if (\Config::get('user.captcha_public_reg')) {
            $rules['captcha'] = 'required|captcha';
        }
        return Validator::make($data, $rules);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)
    {
        $user = User::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
            'disabled' => \Config::get('user.admin_enable_required'),
        ]);
        
        event(new UserRegistered($user));
        
        return user;
    }
    
    public function postLogin(Request $request)
    {
        $credentials = $request->only('username', 'password');
        $validator = Validator::make(
            $credentials,
            ['username' => 'required', 'password' => 'required']
        );
        if ($validator->fails()) {
            if ($request->ajax()) {
                return Response::json(Ajax::failure($validator->errors()));
            }
            
            redirect()->back()->withErrors($validator)->withInput();
        }
        
        if (!(auth()->attempt($credentials, $request->has('remember')))) {
            if ($request->ajax()) {
                return Response::json(Ajax::failure());
            }
            
            redirect()->back()->withError(T_('Cannot authenticate.'));
        }
        
        if ($request->ajax()) {
            return Response::json(Ajax::success());
        }
        
        return redirect()->back();
    }
}
