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

header('Access-Control-Allow-Origin:  *');
header('Access-Control-Allow-Methods:  POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers:  Content-Type, X-Auth-Token, Origin, Authorization');

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group([
    'prefix' => 'bi'
], function () {

    Route::get('atividades', 'BusinessIntelligenceController@atividades');

});

Route::group([
    'prefix' => 'auth'
], function () {
    Route::post('login', 'AuthController@login');
    Route::post('signup', 'AuthController@signup');

    Route::group([
      'middleware' => 'auth:api'
    ], function() {
        Route::get('logout', 'AuthController@logout');
        Route::post('user', 'AuthController@user');
        Route::post('extract', 'AuthController@extract');
        Route::post('activeaccount', 'AuthController@activeaccount');
    });
});

Route::group([
    'namespace' => 'Auth',
    'prefix' => 'password'
], function () {
    Route::post('create', 'PasswordResetController@create');
    Route::get('find/{token}', 'PasswordResetController@find');
    Route::post('reset', 'PasswordResetController@reset');
});

Route::group([
  'middleware' => 'auth:api'
], function() {

    Route::group([
      'prefix' => 'insta'
    ], function () {
        Route::post('adduser', 'InstagramAuthController@addUser');
        Route::post('confirm', 'InstagramAuthController@confirm');
        Route::post('getAccounts', 'InstagramAuthController@getAccounts');

        Route::post('getPosts', 'InstagramAuthController@getPosts');
    });

    Route::group([
      'prefix' => 'follow'
    ], function () {
        Route::post('addfollow', 'FollowController@addFollow');
    });

    Route::group([
      'prefix' => 'requests'
    ], function () {
        Route::post('get', 'UserRequestsController@getResquests')->name('getrequests');
        Route::post('add', 'UserRequestsController@addRequest')->name('addrequests');

        Route::post('deletelikerequest', 'UserRequestsController@deleteLikeRequest')->name('deleterequest');

        Route::post('desabilitarconta', 'UserRequestsController@desabilitarConta')->name('deleterequest');
    });
  /*
    Route::group([
      'prefix' => 'photolike'
    ], function () {
       Route::post('add', 'LikeController@addLike')->name('addlikes');
    });
  */

    Route::group([
      'prefix' => 'photolike'
    ], function () {
        Route::post('home', 'LikeController@test');
        Route::post('photolike', 'LikeController@photolikeAdd')->name('addlikes');
    });

    Route::group([
      'prefix' => 'help'
    ], function () {
        Route::post('add', 'HelpController@addHelp')->name('addHelp');
    });

    Route::group([
        'prefix' => 'vip'
    ], function () {
        Route::post('buyvip', 'VipController@buyVIP');
        Route::post('punirunfollow', 'VipController@punishUnfollow');
    });
  
    Route::group([
        'prefix' => 'admin'
    ], function () {
        Route::get('getpointsdata', 'AdminController@getPointsData');
        Route::get('tasksday', 'AdminController@getTasksDay');
    });

});
