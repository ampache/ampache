<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
    
    /*
        public function login(Request $request)
        {
            $this->validateLogin($request);

            if ($this->hasTooManyLoginAttempts($request)) {
                $this->fireLockoutEvent($request);

                return $this->sendLockoutResponse($request);
            }

            if ($this->attemptLogin($request)) {
                UserPreferences::get_all(auth::id());
                return $this->sendLoginResponse($request);
            }

            // If the login attempt was unsuccessful we will increment the number of attempts
            // to login and redirect the user back to the login form. Of course, when this
            // user surpasses their maximum number of attempts they will get locked out.
            $this->incrementLoginAttempts($request);

            return back()
            ->with('status', 'Username or password is incorrect.');

            //   return $this->sendFailedLoginResponse($request);
        }
     */
    public function logout(Request $request)
    {
        setcookie("sidebar_tab", "", time() - 3600);
        $this->guard()->logout();
        
        $request->session()->invalidate();
        
        return redirect('/');
    }
}
