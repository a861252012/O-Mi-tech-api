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
Route::get('captcha/test',function (\Illuminate\Http\Request $request){
//    Session::setId($request->get('cid'));
//    Session::start();
//    \Illuminate\Support\Facades\Session::getHandler()->read()
    $phrase =$request->get('captcha');
    var_dump(session()->getId());
    var_dump(Session::get('captcha'));
    var_dump(\Mews\Captcha\Facades\Captcha::check($phrase));
})->middleware('mobile.session');

Route::get('room/conf', ['name' => 'm_room_conf', 'uses' => 'Mobile\RoomController@getRoomConf']);


//移动端登录验证
Route::group(['middleware' => ['login_auth:mobile']], function () {

    //首页
    Route::get('index', ['name' => 'm_index', 'uses' => 'Mobile\MobileController@index']);
    //排行
    Route::get('rank', ['name' => 'm_rank', 'uses' => 'Mobile\MobileController@rank']);
    //注册
    Route::get('register', ['name' => 'm_register', 'uses' => 'Mobile\MobileController@register']);
    //轮播图获取
    Route::get('/m/sliderlist', ['name' => 'm_sliderlist', 'uses' => 'Mobile\MobileController@sliderList']);
    //活动列表
    Route::get('activitylist', ['name' => 'm_activitylist', 'uses' => 'Mobile\MobileController@activityList']);
    //活动详情
    Route::get('activitydetail/{id}', ['name' => 'm_activitydetail', 'uses' => 'Mobile\MobileController@activityDetail']);
    //主播列表
    Route::get('video/list/{type}', ['name' => 'm_videolist', 'uses' => 'Mobile\MobileController@videoList']);
    //app版本ｈ
    Route::get('app/version', ['name' => 'm_app_ver', 'uses' => 'Mobile\MobileController@appVersion']);
    Route::get('app/versionIOS', ['name' => 'm_app_ver_ios', 'uses' => 'Mobile\MobileController@appVersionIOS']);
    Route::post('app/version', ['name' => 'm_app_ver', 'uses' => 'Mobile\MobileController@appVersion']);
    Route::post('app/versionIOS', ['name' => 'm_app_ver_ios', 'uses' => 'Mobile\MobileController@appVersionIOS']);
    /** 获取配置 */
    Route::get('conf', ['name' => 'm_conf', 'uses' => 'Mobile\RoomController@getConf']);
    Route::get('room/conf', ['name' => 'm_room_conf', 'uses' => 'Mobile\RoomController@getRoomConf']);
    Route::get('room/{rid}/checkAccess', ['name' => 'm_room_checkAccess', 'uses' => 'Mobile\RoomController@getRoomAccess']);
   //关注列表
    Route::get('user/following', ['name' => 'm_userfollowing', 'uses' => 'Mobile\MobileController@userFollowing']);
    //用户信息
    Route::get('user/info', ['name' => 'm_userinfo', 'uses' => 'Mobile\MobileController@userInfo']);
    //用户特权
    Route::get('user/privilege', ['name' => 'm_userprivilege', 'uses' => 'Mobile\MobileController@userPrivilege']);
    //座驾列表
    Route::get('user/mount/list', ['name' => 'm_usermountlist', 'uses' => 'Mobile\MobileController@mountList']);
    Route::post('user/mount/{gid}', ['name' => 'm_usermount', 'uses' => 'Mobile\MobileController@mount']);
    Route::post('user/unmount/{gid}', ['name' => 'm_userunmount', 'uses' => 'Mobile\MobileController@unmount']);
    //关注
    Route::match(['POST', 'GET'], 'follow', ['name' => 'm_follow', 'uses' => 'Mobile\MobileController@follow']);
    //预约列表 type=1 一对一，type=2 一对多,type=3 所有
    Route::get('room/reservation/{type}', ['name' => 'm_userroomreservation', 'uses' => 'Mobile\RoomController@listReservation']);
    //隐身
    Route::get('user/stealth[/{status:\d+}]', ['name' => 'm_stealth', 'uses' => 'Mobile\MobileController@stealth']);
    //购买一对一
    Route::post('room/buyOneToOne', ['name' => 'm_buyOneToOne', 'uses' => 'Mobile\RoomController@buyOneToOne']);

    /** 检查密码房密码 */
    Route::post('room/checkPwd', ['name' => 'm_room_checkpwd', 'uses' => 'Mobile\RoomController@checkPwd']);
    /** 获取RTMP地址 */
    Route::get('room/rtmp/{rid:\d+}', ['name' => 'm_room_rtmp', 'uses' => 'RoomController@getRTMP']);

    //移动端创建一对多
    Route::post('OneToMore/create', ['name' => 'OneToMore', 'uses' => 'Mobile\RoomController@createOne2More']);
    //移动端购买一对多
    Route::post('room/buyOneToMany', ['name' => 'm_buyOneToOne', 'uses' => 'Mobile\RoomController@makeUpOneToMore']);
    //app删除一对多房间
    Route::post('OneToMore/delete', ['name' => 'm_onetomoredel', 'uses' => 'Mobile\RoomController@delRoomOne2More']);
    //app一对多房间列表
    Route::get('OneToMore/list', ['as' => 'm_onetomorelist', 'uses' => 'Mobile\RoomController@listOneToMoreByHost']);
    //app判断是否开通一对多和一对一
    Route::get('oneToManyCompetence', ['as' => 'competence', 'uses' => 'Mobile\RoomController@competence']);
    //app直播记录表
    Route::get('showlist', ['as' => 'm_showlist', 'uses' => 'Mobile\RoomController@showlist']);

    //移动端删除一对一
    Route::post('room/delRoomOne2One', ['name' => 'member_roomDelOne2One', 'uses' => 'Mobile\RoomController@delRoomOne2One']);
    //移动端预约一对一
    Route::post('room/buyOneToOne', ['name' => 'm_buyOneToOne', 'uses' => 'Mobile\RoomController@buyOneToOne']);
    //移动端创建一对一
    Route::post('room/roomSetDuration', ['name' => 'member_roomSetDuration', 'uses' => 'Mobile\RoomController@roomSetDuration']);
    //移动端一对一房间用户
    Route::get('OneToOne/list', ['name' => 'member_onetoONElist', 'uses' => 'Mobile\MobileController@listOneToOneByHost']);


});