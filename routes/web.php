<?php

/**
 * Web Routes
 *
 * Here is where you can register web routes for your application.
 * These routes are loaded by the RouteServiceProvider.
 */

use Titan\Core\Facades\Route;

// Home route
Route::get('/', function () {
    return view('welcome');
});

// Basic controller route
Route::get('/hello', 'HomeController@hello');

// Route with parameters
Route::get('/users/{id}', 'UserController@show');

// Resource routes
Route::resource('/posts', 'PostController');

// Route with middleware
Route::get('/dashboard', 'DashboardController@index')
    ->middleware('auth');

// Route group with prefix and middleware
Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'admin']], function () {
    Route::get('/', 'Admin\DashboardController@index');
    Route::resource('/users', 'Admin\UserController');
});

// Named route
Route::get('/contact', 'ContactController@show')
    ->name('contact');
