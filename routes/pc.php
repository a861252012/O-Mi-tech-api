<?php
/**
 * PC端路由
 */
//Route::get('/user', 'UserController@index');
//$app->addRoute(['POST', 'GET'], '/login', ['as' => 'login', 'uses' => 'App\Controller\LoginController@login']);
Route::get('/', function () {
    echo session()->getHandler()->destroy(Session::getId());
    echo session()->getHandler()->destroy('Ys2ihkAy8SyKX23kigNlhzhpliCfFhuYU4kMW0Sf');
//    throw new \ErrorException('insert to user table error.');
//    try {
//        throw new Exception('Please make sure is a numeric');
//    } catch (Exception $e) {
//        echo $e->getMessage();
//    }
//    $a = app()->make('redis')->hgetall('huser_info:10000');
//    dd($a);
    $user = \App\Models\Users::find(10000);
    //dd(Auth::guard('pc'));
    var_dump(Auth::guard('pc')->check());
    //Auth::login($user);
    echo 'pc index';
});

Route::group(['middleware'=>['login_auth']],function (){
    Route::match(['POST', 'GET'], '/onetomore', ['name' => 'login', 'uses' => 'LoginController@login']);
});
Route::match(['POST', 'GET'], '/login', ['name' => 'login', 'uses' => 'LoginController@login']);

