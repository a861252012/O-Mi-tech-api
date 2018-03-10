<?php
/**
 * PC端路由
 */
//Route::get('/user', 'UserController@index');
//$app->addRoute(['POST', 'GET'], '/login', ['as' => 'login', 'uses' => 'App\Controller\LoginController@login']);
Route::get('/', function () {
    $a = app('request')->getSession();
    dd($a);
    echo 'pc index';
});

Route::group(['middleware'=>['login_auth']],function (){
    Route::match(['POST', 'GET'], '/onetomore', ['name' => 'login', 'uses' => 'LoginController@login']);
});
Route::match(['POST', 'GET'], '/login', ['name' => 'login', 'uses' => 'LoginController@login']);

