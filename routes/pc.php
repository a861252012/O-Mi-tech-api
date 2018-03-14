<?php
/**
 * PC端路由
 */

Route::get('/', function () {

    dd(\Illuminate\Support\Facades\Session::getId());

    echo 'pc index'; die;
    echo Session::getId();
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
/*//验证码
Route::get('/captcha', ['name' => 'captcha', 'uses' => 'Controller@captcha']);*/

//pc创建一对多
Route::get('/member/roomOneToMore', ['name' => 'roomOneToMore', 'uses' => 'MemberController@roomOneToMore']);
//移动端创建一对多
Route::get('/m/OneToMore/create', ['name' => 'OneToMore', 'uses' => 'Controller@captcha']);

