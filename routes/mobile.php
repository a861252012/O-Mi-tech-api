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
Route::post('/login', 'Mobile\MobileController@login')->name('m_login');
//登录验证码
Route::get('/login/captcha', 'Mobile\MobileController@loginCaptcha');
