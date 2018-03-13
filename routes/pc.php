<?php
/**
 * PC端路由
 */

Route::get('/', function () {
    echo 'pc index';
});
    echo session()->getHandler()->destroy(Session::getId());
    echo session()->getHandler()->destroy('Ys2ihkAy8SyKX23kigNlhzhpliCfFhuYU4kMW0Sf');
    $user = \App\Models\Users::find(10000);
    //dd(Auth::guard('pc'));
    var_dump(Auth::guard('pc')->check());
    //Auth::login($user);
    echo 'pc index';
});

Route::group(['middleware'=>['login_auth']],function (){
    Route::match(['POST', 'GET'], '/onetomore', function(){
        echo "aaa";
    });
});
Route::match(['POST', 'GET'], '/login', ['name' => 'login', 'uses' => 'LoginController@login']);

