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

Auth::routes();

Route::get('/', 'HomeController@index')->name('home');

Route::get('/home', 'HomeController@index')->name('home');
Route::resource('users', 'UserController');
Route::resource('catalogs', 'CatalogController');
Route::get('actions/{action}', 'CatalogController@action');
Route::resource('roles', 'RoleController');

Route::resource('permissions', 'PermissionController');
Route::get('/lighttab', function () {
    return view('partials.sidebar.light');
});

Route::get('loadtab/{tab}', 'SidebarController@loadTab');
   
Route::get('/modules/{type}/{action}', 'ModulesController@action');    
Route::get('/modules/show_catalogs', 'ModulesController@show_catalogs');
