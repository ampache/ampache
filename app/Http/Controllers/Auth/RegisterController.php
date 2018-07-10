<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\VerifyUser;
use App\Http\Controllers\Controller;
use App\Mail\EmailVerification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use App\Jobs\SendVerificationEmail;
use \Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;
    
    /**
     * Where to redirect users after registration.
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
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $rules = ['username' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:5|confirmed',
        ];
        
        $req_fields = config('user.registration_mandatory_fields');
        if ($req_fields) {
            foreach ($req_fields as $field) {
                $rules[$field] = 'required';
            }
        }

        return Validator::make($data, $rules);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
//     * @return App\Models\User
     */
    protected function create(array $data)
    {
        if (config('user.email_confirm') == true) {
            $columns = ['email_token' => base64_encode($data['email'])];
        } else {
            $columns = ['verified' => 1];
        }
        $req_fields = config('user.registration_mandatory_fields');
        if ($req_fields) {
            foreach ($req_fields as $field) {
                $columns[$field] = $data[$field];
            }
        }
            
        return User::create([
            'username' => $data['username'],
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'password' => $data['password'],
            $columns,
        ]);
            // Authentication passed...
    }
     
     /**

     * Handle a registration request for the application.

     * @param \Illuminate\Http\Request $request

     * @return \Illuminate\Http\Response

     */
     
     public function register(Request $request)
     {
         $isHuman = true;
         
         if (config('user.captcha_public_reg') == true) {
             $code    = $request->input('CaptchaCode');
             $isHuman = captcha_validate($code);
         }
         
         if ($isHuman) {
             $this->validator($request->all())->validate();
         } else {
             return back()
             ->with('status', 'Are you sure you are human? Please try the Captcha again');
         }
         
         event(new Registered($user = $this->create($request->all())));
         $userCount = DB::table("users")->count();
         if ($userCount > 1) {
             $user->assignRole('User');
             $role_id = DB::table('roles')->select('id')->where('name', 'User')->get();
             DB::table('role_users')->insert(
                ['user_id' => $user->id, 'role_id' => $role_id[0]->id]
            );
         } else {
             $user->assignRole('Administrator');
             $role_id = DB::table('roles')->select('id')->where('name', 'Administrator')->get();
             DB::table('role_users')->insert(
                 ['user_id' => $user->id, 'role_id' => $role_id[0]->id]
             );
         }
         if (config('user.email_confirm') == true) {
             dispatch(new SendVerificationEmail($user));

             return view('email.verification');
         } else {
             return view('welcome', ['Name' => $user->full_name]);
         }
     }
     
     /**

     * Handle a registration request for the application.

     * @param $token

     * @return \Illuminate\Http\Response

     */
     
     public function verify($token)
     {
         $user = User::where('email_token', $token)->first();
         
         $user->verified = 1;
         
         if ($user->save()) {
             return view("email.emailconfirm", ['user' => $user]);
         }
     }
}
