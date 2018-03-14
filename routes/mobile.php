<?php
/**
 * 移动端路由
 */

Route::get('/', function () {
    echo 'mobile index';
});

Route::group(['middleware'=>['login_auth']],function (){
    Route::match(['POST', 'GET'], '/onetomore', function(){
        echo "aaa";
    });
});
Route::match();