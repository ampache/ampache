<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
    
    public function username()
    {
        return 'username';
    }
    
    public function validateLogin(Request $request)
    {
        if (Auth::attempt(['username' => $request->input('username'), 'password' => $request->input('password')])) {
            // Authentication passed...
 //           return redirect()->intended('/');
        } else {
            $request->session()->flash('error', 'User name or password incorrect!');
            
            return view('auth.login');
            //          sendFailedLoginResponse($request);
        }
    }
    
    public function logout(Request $request)
    {
        setcookie("sidebar-tab", "home", 30);
        $this->guard()->logout();
        
        $request->session()->invalidate();
        
        return redirect('/');
    }
    
}
