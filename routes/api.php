<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::group(['prefix', 'auth'], function () {
    Route::post('login', 'App\Http\Controllers\AuthController@login');
    Route::post('user-register', 'App\Http\Controllers\AuthController@register');
    Route::post('admin-register', 'App\Http\Controllers\AuthController@register_admin');
    Route::post('resend-verification', 'App\Http\Controllers\AuthController@resend_otp');
    Route::post('verify-mail', 'App\Http\Controllers\AuthController@verify_mail');
    Route::post('create-verify-link', 'App\Http\Controllers\AuthController@create_verify_link');
    Route::post('edit-user', 'App\Http\Controllers\ProfileController@edit');
});
