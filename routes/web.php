<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
/*
Route::get('/', function () {
    return view('home');
});
*/
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redis;
use App\Models\User;
use App\Models\Role;

Route::get('login', 'Auth\LoginController@showLoginForm')->name('login');
Auth::routes();
Route::get('/verifyemail/{token}', 'Auth\RegisterController@verify');
Route::get('/', function () {
    return view('home');
});

Route::get('/home', 'HomeController@index')->name('home');
Route::get('users/delete/{id}', 'UserController@destroy');

Route::resource('users/create', 'UserController');
Route::get('users/edit/{id}', 'UserController@edit');
Route::get('avatar/delete/{id}', 'UserController@deleteAvatar');
Route::resource('users', 'UserController');
Route::get('catalogs/index', 'CatalogController@index');
Route::get('/catalogs/create', 'CatalogController@create');
Route::get('/catalogs/edit/{id}', 'CatalogController@edit');
Route::post('/catalogs/store', 'CatalogController@store');
Route::get('catalogs/action/{action}/{id}', 'CatalogController@action');

Route::resource('roles', 'RoleController');

Route::resource('permissions', 'PermissionController');
Route::get('/lighttab', function () {
    return view('partials.sidebar.light');
});

Route::get('loadtab/{tab}', 'SidebarController@loadTab');
   
Route::get('/modules/{type}/{action}', 'ModulesController@action');
Route::get('/modules/show_catalogs', 'ModulesController@show_catalogs');
Route::get('/SSE/{action}/{catalogs}/{options}', 'SSEController@processAction');

Route::get('/apikey/create/{id}', function ($id) {
    $user = User::find($id);
    $apikey = hash('md5', time() . $user->username . $user->password);
    $user->apikey=$apikey;
    $user->save();

    return $user->apikey;
});

Route::resource('image', 'ImageController');
//Route::get('image/{id}', 'ImageController');

Route::resource('preference', 'PreferenceController');
Route::get('user_preference/{id}/edit', 'User_PreferenceController@edit');
Route::get('profile/{user}', function (App\Models\User_Preference $user) {
    //
});
