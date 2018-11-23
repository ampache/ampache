<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Services\UserPreferences;

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
    
    public function authenticate(Request $request)
    {
        $credentials = $request->only('username', 'passwword', 'remember');
        
        if (Auth::attempt($credentials)) {
            // Authentication passed...
            UserPreferences::get_all(auth::id());

            return redirect()->intended('auth.login');
        }
    }
    
    public function logout(Request $request)
    {
        setcookie("sidebar_tab", "", time() - 3600);
        $id  = auth::id();
        //        Session::flush();
        Auth::logout();

        return view('auth.login');
        //return redirect('/login');
    }
}
