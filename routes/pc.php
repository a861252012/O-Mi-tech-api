<?php
/**
 * PC端路由
 */

Route::get('/', function () {
//    $a = app('request')->getSession();
    echo 'pc index';
});

//Route::group(['middleware'=>['login_auth']],function (){
//    Route::match(['POST', 'GET'], '/onetomore', ['name' => 'login', 'uses' => 'LoginController@login']);
//});
//Route::match(['POST', 'GET'], '/login', ['name' => 'login', 'uses' => 'LoginController@login']);

