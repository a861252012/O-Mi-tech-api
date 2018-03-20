<?php
/**
 * 移动端路由
 */

Route::get('/', function () {
    echo 'mobile index';
});

Route::group(['middleware' => ['login_auth:mobile']], function () {
    Route::match(['POST', 'GET'], '/onetomore', function () {
        echo "aaa";
    });
    Route::get('/login/test', 'Mobile\MobileController@logintest');
});
//登录
Route::post('/login', 'Mobile\MobileController@login')->name('m_login')->middleware('mobile.session');
//登录验证码
Route::get('/login/captcha', 'Mobile\MobileController@loginCaptcha')->middleware('mobile.session');

Route::get('captcha/test',function (\Illuminate\Http\Request $request){
//    Session::setId($request->get('cid'));
//    Session::start();
//    \Illuminate\Support\Facades\Session::getHandler()->read()
    $phrase =$request->get('captcha');
    var_dump(session()->getId());
    var_dump(Session::get('captcha'));
    var_dump(\Mews\Captcha\Facades\Captcha::check($phrase));
})->middleware('mobile.session');
