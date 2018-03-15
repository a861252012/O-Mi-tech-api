<?php
/**
 * 移动端路由
 */

Route::get('/', function () {
    echo 'mobile index';
});

Route::group(['middleware' => ['login_auth']], function () {
    Route::match(['POST', 'GET'], '/onetomore', function () {
        echo "aaa";
    });
});
//登录
Route::post('/login', 'Mobile\MobileController@login')->name('m_login');
//登录验证码
Route::get('/login/captcha', 'Mobile\MobileController@loginCaptcha');
Route::get('/login/test', 'Mobile\MobileController@logintest');