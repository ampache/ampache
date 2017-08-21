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

Route::get('/', function () {
    return view('pages.index');
});

Auth::routes();
Route::get('logout', 'Auth\LoginController@logout')->name=('logout');
Route::get('server/ajax/index/sidebar/{category}', 'Ajax\IndexController@sidebar');
Route::get('/home', 'HomeController@index')->name('home');
Route::post('update/{id}', 'UserController@update')->name('update');

Route::get('index', 'UserController@index')->name('index');
Route::get('show/{id}', 'UserController@show')->name('show');
Route::get('edit/{id}', 'UserController@edit')->name('edit');
Route::get('user.destroy', 'UserController@destroy')->name('user.destroy');
Route::get('add', 'UserController@create')->name('add');
Route::get('user/disable/{id}', 'UserController@disable');
Route::get('user/enable/{id}', 'UserController@enable');
Route::delete('user/destroy/{id}', 'UserController@destroy')->name('destroy');
Route::get('art/show/{id}/{type}', 'ArtController@show');

Route::get('/messages/index/{id}', 'PrivateMsgController@index');
Route::delete('messages/destroy/{id}', 'PrivateMsgController@destroy')->name('destroy');
Route::get('messages/reply/{id}', 'PrivateMsgController@reply')->name('reply');
