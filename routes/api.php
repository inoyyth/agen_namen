<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::group(['prefix' => 'user', 'as' => 'user'], function() {
    Route::post('login', ['as' => 'login', 'uses' => 'Api\UserController@login']);
    Route::post('register',['as' => 'register', 'uses' => 'Api\UserController@register']);
    Route::post('user-activation', ['as' => 'user-activation', 'uses' => 'Api\UserController@userActivation']);
    Route::post('forgot-password', ['as' => 'forgot-password', 'uses' => 'Api\UserController@userActivation']);
    Route::group(['middleware' => 'auth:api'], function() {
        Route::post('change-password', ['as' => 'change-password', 'uses' => 'Api\UserController@changePassword']);
        Route::get('profile', ['as' => 'get-profile', 'uses' => 'Api\UserController@getProfile']);
        Route::post('profile', ['as' => 'change-profile', 'uses' => 'Api\UserController@changeProfile']);
        Route::post('logout', ['as' => 'user-logout', 'uses' => 'Api\UserController@logout']);
    });
});


Route::group(['prefix' => 'merchant', 'as' => 'merchant'], function() {
    Route::post('login', ['as' => 'merchant-login', 'uses' => 'Api\MerchantController@login']);
    Route::group(['middleware' => 'auth:api'], function() {
        Route::post('change-password', ['as' => 'change-password', 'uses' => 'Api\MerchantController@changePassword']);
        Route::post('logout', ['as' => 'merchant-logout', 'uses' => 'Api\MerchantController@logout']);
        Route::get('profile', ['as' => 'merchant-profile', 'uses' => 'Api\MerchantController@getProfile']);
        Route::post('profile', ['as' => 'merchant-change-profile', 'uses' => 'Api\MerchantController@changeProfile']);
    });
});

Route::group(['prefix' => 'category', 'as' => 'category'], function() {
    Route::group(['middleware' => 'auth:api'], function() {
        Route::get('/', ['as' => 'category', 'uses' => 'Api\CategoryController@index']);
    });
});

Route::group(['prefix' => 'products', 'as' => 'products'], function() {
    Route::group(['middleware' => 'auth:api'], function() {
        Route::get('/', ['as' => 'product', 'uses' => 'Api\ProductController@index']);
    });
});