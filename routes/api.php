<?php

/**
 * API Routes
 *
 * Here is where you can register API routes for your application.
 * These routes are loaded by the RouteServiceProvider with the 'api' prefix.
 */

use Titan\Core\Facades\Route;

// API version 1 group
Route::group(['prefix' => 'v1', 'namespace' => 'Api\V1'], function () {
    // Public routes
    Route::get('/status', 'StatusController@check');

    // Auth routes
    Route::post('/login', 'AuthController@login');
    Route::post('/register', 'AuthController@register');

    // Protected routes
    Route::group(['middleware' => 'auth:api'], function () {
        // User routes
        Route::get('/user', 'UserController@current');
        Route::put('/user', 'UserController@update');

        // Resource routes
        Route::apiResource('/posts', 'PostController');
        Route::apiResource('/comments', 'CommentController');
    });
});

// API version 2 group
Route::group(['prefix' => 'v2', 'namespace' => 'Api\V2'], function () {
    // Public routes
    Route::get('/status', 'StatusController@check');

    // Protected routes
    Route::group(['middleware' => 'auth:api'], function () {
        // New v2 endpoints
        Route::apiResource('/resources', 'ResourceController');
    });
});
