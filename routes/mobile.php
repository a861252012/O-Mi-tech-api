<?php
/**
 * 移动端路由
 */


Route::group(['middleware' => ['login_auth:mobile']], function () {
    Route::get('/login/test', 'Mobile\MobileController@logintest');
});
//登录
Route::post('/login', 'Mobile\MobileController@login')->name('m_login')->middleware('mobile.session');
//登录验证码
Route::get('/login/captcha', 'Mobile\MobileController@loginCaptcha')->middleware('mobile.session');

Route::get('/m/room/conf', ['name' => 'm_room_conf', 'uses' => 'Mobile\RoomController@getRoomConf']);
