<?php
/**
 * 移动端路由
 */



Route::group(['middleware' => ['login_auth:mobile']], function () {
    Route::get('/login/test', 'Mobile\MobileController@logintest');
});
// reg
Route::post('reg','ApiController@reg')->name('m_reg')->middleware('mobile.session');
// reg suggest nickname
Route::get('/reg/nickname', 'RegController@nickname')->name('m_reg_nickname');
//登录
Route::post('/login', 'Mobile\MobileController@login')->name('m_login')->middleware('mobile.session');
//验证码
Route::get('captcha', 'Mobile\MobileController@captcha')->middleware('mobile.session');
// send SMS
Route::post('/sms/send', 'SmsController@send')->name('sms_send');

Route::any('change_pwd', 'Mobile\MobileController@changePwd')->name('change_pwd')->middleware('mobile.session');
//app版本ｈ
Route::any('app/version', ['name' => 'm_app_ver', 'uses' => 'Mobile\MobileController@appVersion']);
Route::any('app/versionIOS', ['name' => 'm_app_ver_ios', 'uses' => 'Mobile\MobileController@appVersionIOS']);

Route::get('conf',['name'=>'m_conf', 'uses'=>'ApiController@getConf']);
Route::get('pre_conf',['name'=>'m_pre_conf', 'uses'=>'ApiController@getPreConf']);
Route::get('room/conf',['name'=>'m_room_conf', 'uses'=>'Mobile\RoomController@getRoomConf']);
// 首页房间数据json
Route::get('/videoList', ['as' => 'index_videoList', 'uses' => 'IndexController@videoList']);
//排行榜接口
Route::get('/rank_data', ['name' => 'rank_data', 'uses' => 'RankController@rankData']);

Route::get('ping', ['as' => 'ping', 'uses' => 'ApiController@ping']);

//活动列表
Route::get('activitylist', ['name' => 'm_activitylist', 'uses' => 'ActivityController@index']);
//域名列表
Route::any('domain_list', ['name' => 'domain_list', 'uses' => 'Mobile\MobileController@domain']);
//活动详情
Route::get('activitydetail', ['name' => 'm_activitydetail', 'uses' => 'ActivityController@detailtype']);

Route::group(['prefix' => 'user'], function () {
    Route::post('pwdreset/by_mobile', 'PasswordController@pwdResetByMobile')->middleware('throttle.route:10,1')->name('m_pwdreset_by_mobile');
    Route::post('pwdreset/send', 'PasswordController@pwdResetSendFromMobile')->middleware('mobile.session')->middleware('throttle.route:10,1')->name('m_pwdreset');
    Route::post('pwdreset/confirm', 'PasswordController@pwdResetConfirmFromMobile')->middleware('throttle.route:10,1')->name('m_pwdreset');
    Route::post('pwdreset/test', 'PasswordController@pwdResetTest');
});

//移动端登录验证
Route::group(['middleware' => ['login_auth:mobile']], function () {
    //app获取粉丝详情
    Route::get('OneToMore/getfans', ['as' => 'm_onetomorecreate','uses' => 'Mobile\MobileController@getFans']);
    //修改密码
    Route::post('update/password', ['as' => 'm_changepassword','uses' => 'Mobile\MobileController@passwordChange'])->middleware('mobile.session');
    // 用户中心消息
    Route::get('system/information', 'Mobile\MobileController@msglist')->name('m_information');
    //首页
    Route::get('index', ['name' => 'm_index', 'uses' => 'Mobile\MobileController@index']);
    //排行
    Route::get('rank', ['name' => 'm_rank', 'uses' => 'Mobile\MobileController@rank']);
    //注册
    Route::get('register', ['name' => 'm_register', 'uses' => 'Mobile\MobileController@register']);
    //轮播图获取
    Route::get('sliderlist', ['name' => 'm_sliderlist', 'uses' => 'Mobile\MobileController@sliderList']);
   //主播列表
    Route::get('video/list/{type}', ['name' => 'm_videolist', 'uses' => 'Mobile\MobileController@videoList']);

    /** 获取配置 */
    Route::get('room/{rid}/checkAccess', ['name' => 'm_room_checkAccess', 'uses' => 'Mobile\RoomController@getRoomAccess']);

    Route::group(['prefix'=>'user'],function (){
        //关注列表
        Route::get('following', ['name' => 'm_userfollowing', 'uses' => 'Mobile\MobileController@userFollowing']);
        //用户信息
        Route::get('info', ['name' => 'm_userinfo', 'uses' => 'Mobile\MobileController@userInfo']);
        //用户特权
        Route::get('privilege', ['name' => 'm_userprivilege', 'uses' => 'Mobile\MobileController@userPrivilege']);
        //座驾列表
        Route::get('mount/list', ['name' => 'm_usermountlist', 'uses' => 'Mobile\MobileController@mountList']);
        Route::post('mount/{gid}', ['name' => 'm_usermount', 'uses' => 'Mobile\MobileController@mount']);
        Route::post('unmount/{gid}', ['name' => 'm_userunmount', 'uses' => 'Mobile\MobileController@unmount']);
        //隐身
        Route::any('stealth/{status}', 'Mobile\MobileController@stealth')->where('status','[0,1]')->name('m_stealths');
    });

    //关注
    Route::any('follow', ['name' => 'm_follow', 'uses' => 'ApiController@follow']);

    Route::group(['prefix'=>'room'],function (){
        //预约列表 type=1 一对一，type=2 一对多
        Route::get('reservation/{type}',  'Mobile\RoomController@listReservation')->where('type',"[1,2,3]")->name('m_userroomreservation');
        //购买一对一
        Route::post('buyOneToOne', ['name' => 'm_buyOneToOne', 'uses' => 'Mobile\RoomController@buyOneToOne']);

        /** 检查密码房密码 */
        Route::post('checkPwd', ['name' => 'm_room_checkpwd', 'uses' => 'Mobile\RoomController@checkPwd'])->middleware('mobile.session');
        /** 获取RTMP地址 */
        Route::get('rtmp/{rid}', 'RoomController@getRTMP')->where('rid','[0-9]+')->name('m_room_rtmp');

        //移动端删除一对一
        Route::post('delRoomOne2One', ['name' => 'member_roomDelOne2One', 'uses' => 'MemberController@delRoomDuration']);
        //移动端预约一对一
        Route::post('buyOneToOne', ['name' => 'm_buyOneToOne', 'uses' => 'MemberController@doReservation']);
        //移动端创建一对一
        Route::post('roomSetDuration', ['name' => 'member_roomSetDuration', 'uses' => 'Mobile\RoomController@roomSetDuration']);
        //移动端购买一对多
        Route::post('buyOneToMany', ['name' => 'm_buyOneToOne', 'uses' => 'MemberController@makeUpOneToMore']);
    });



    Route::group(['prefix'=>'OneToMore'],function(){
        //app删除一对多房间
        Route::post('delete', ['name' => 'm_onetomoredel', 'uses' => 'MemberController@delRoomOne2Many']);
        //app一对多房间列表
        Route::get('list', ['as' => 'm_onetomorelist', 'uses' => 'Mobile\RoomController@listOneToMoreByHost']);
        //移动端创建一对多
        Route::post('create', ['name' => 'OneToMore', 'uses' => 'MemberController@roomOneToMore']);
    });


    //app判断是否开通一对多和一对一
    Route::get('oneToManyCompetence', ['as' => 'competence', 'uses' => 'Mobile\RoomController@competence']);
    //app直播记录表
    Route::get('showlist', ['as' => 'm_showlist', 'uses' => 'Mobile\RoomController@showlist']);

    //移动端一对一房间用户
    Route::get('OneToOne/list', ['as' => 'member_onetoONElist', 'uses' => 'Mobile\RoomController@listOneToOneByHost']);

    // 用户中心 收入统计
    Route::get('member/income', ['name' => 'member_count', 'uses' => 'MemberController@income']);

    //转账功能
    Route::get('transfer', 'MemberController@transferHistory')->name('member_transfer_history');
    Route::post('transfer/create', 'MemberController@transfer')->name('member_transfer_create')->middleware('mobile.session');
    Route::post('password', 'MemberController@password')->name('password')->middleware('mobile.session');
    //转帐明细查询
    Route::get('transferlist', 'MemberController@transferList')->name('member_transfer_list');
    //礼物收入礼物送出纪录
    Route::get('giftlist', 'MemberController@giftList')->name('member_gift_list');

    //上传头像
    Route::post('upload', 'MemberController@avatarUpload')->name('avatar_upload');

    // 用户中心修改基本信息
    Route::post('edituserinfo', 'MemberController@editUserInfo')->name('member_edituserinfo');

    // 用户中心 充值记录
    Route::get('charge', 'MemberController@charge')->name('member_charge');
    // 用户中心 消费记录
    Route::get('consume', 'MemberController@consume')->name('member_consume');
    // 用户中心 取得live的充值小妹contact
    Route::get('contact', 'MemberController@contact')->name('contact');
    // 用户中心 vip 贵族体系
    Route::get('vip', ['name' => 'member_vip', 'uses' => 'MemberController@vip']);
    // 用户中心 viplist 贵族体系
    Route::get('/member/viplist', ['name' => 'member_viplist', 'uses' => 'MemberController@vip_list']);
    // 开通贵族
    Route::post('/openvip', ['name' => 'shop_openvip', 'uses' => 'MemberController@buyVip']);
    //昵称修改次数
    Route::get('/member/index', 'MemberController@index')->name('member_index');
    // 用户中心 modify mobile
    Route::post('member/modifymobile/send', 'MemberController@modifyMobileSend')->name('member_modify_mobile_send');
    Route::post('member/modifymobile/confirm', 'MemberController@modifyMobileConfirm')->name('member_modify_mobile_confirm');
    // 红包明细
    Route::get('member/redEnvelopeGet', 'MemberController@redEnvelopeGet');
    Route::get('member/redEnvelopeSend', 'MemberController@redEnvelopeSend');
    // 签到
    Route::any('member/signin', 'MemberController@signin');
    // 主播房间暱称
    Route::any('member/roomInfo', 'MemberController@roomInfo');

    /* Socket相關 */
    Route::prefix('socket')->group(function () {
        Route::get('proxy_list', 'SocketController@proxyList');
    });
});

/** 进房间 */
Route::any('get_room/{rid}', 'Mobile\RoomController@getRoom')->where('rid','[0-9]{5,15}')->name('m_get_room');
/** 一对一table进房间 */
Route::any('get_onetoone_room/{rid}', 'Mobile\RoomController@getRoomonetoone')->where('rid','[0-9]{5,15}')->name('m_get_roomonetoone');

Route::get('find', ['name' => 'm_find', 'uses' => 'Mobile\MobileController@searchAnchor']);
Route::get('oort2bunny', ['name' => 'guanggao', 'uses' => 'AdsController@getAd']);//广告接口
Route::get('domains', ['name' => 'guanggao', 'uses' => 'Mobile\MobileController@domains']);//广告接口


// 充值类 TODO 登录验证
// 验证是否登录
Route::group(['prefix' => 'pay', 'middleware' => ['login_auth:mobile','charge']], function () {
    Route::match(['POST', 'GET'], 'pay', ['name' => 'charge_pay', 'uses' => 'ChargeController@pay']);
    Route::match(['POST', 'GET'], '/order', ['name' => 'charge_order', 'uses' => 'ChargeController@order']);
    Route::match(['POST', 'GET'], '/translate', ['name' => 'translate', 'uses' => 'ChargeController@translate']);
});

Route::group(['prefix' => 'pay','namespace'=>'Mobile',],function () {
    Route::any('/test', ['name' => 'charge_order', 'uses' => 'ChargeController@order']);
});

//通知
Route::group(['prefix' => 'pay', 'middleware' => ['charge']], function () {
    Route::match(['POST', 'GET'], 'notice', ['name' => 'charge_notice', 'uses' => 'ChargeController@notice'])->name('charge_notice');
    Route::match(['POST', 'GET'], 'checkKeepVip', ['name' => 'checkKeepVip', 'uses' => 'ChargeController@checkKeepVip']);
    Route::match(['POST', 'GET'], 'callFailOrder', ['name' => 'callFailOrder', 'uses' => 'ChargeController@callFailOrder']);
    Route::post( 'moniCharge', ['name' => 'charge', 'uses' => 'ChargeController@moniCharge']);
    Route::match(['POST', 'GET'], 'moniHandler', ['name' => 'moniHandler', 'uses' => 'ChargeController@moniHandler']);
    Route::post('del', ['name' => 'charge', 'uses' => 'ChargeController@del']);
});

//统计接口
Route::match(['POST', 'GET'], '/statistic', ['name' => 'm_statistic', 'uses' => 'Mobile\MobileController@statistic']);
Route::post('/send_crash', ['name' => 'send_crash', 'uses' => 'Mobile\MobileController@saveCrash']);//app报错接口

//app探索页
Route::get('appMarket', ['name' => 'm_appmarket', 'uses' => 'Mobile\MobileController@appMarket']);

//生成数据
Route::any('other/homeonetomany', ['name' => 'm_homeonetomany', 'uses' => 'OtherController@createHomeOneToManyList']);

// 关键字屏蔽
Route::get('/kw', ['name' => 'json_kw', 'uses' => 'ApiController@kw']);

//官方聯繫
Route::get('official', ['name' => 'm_follow', 'uses' => 'Mobile\MobileController@official']);

//登入公告
Route::get('loginmsg', ['name' => 'm_loginmsg', 'uses' => 'Mobile\MobileController@loginmsg']);

Route::group(['middleware' => 'cache.headers:public;max_age=60'], function() {
    Route::get('marquee', ['name' => 'm_marquee', 'uses' => 'Mobile\MobileController@marquee']);
});

//首頁輪播
//Route::get('marquee', ['name' => 'm_marquee', 'uses' => 'Mobile\MobileController@marquee'])->middleware('cache.headers:public;max_age=60');

Route::get('/contact/qr.png', ['name' => 'contactQR', 'uses' => 'PageController@contactQR']);

//贵族列表
Route::get('/getgroupall', ['name' => 'shop_getgroupall', 'uses' => 'ShopController@getGroupAll']);

// 遊戲中心
Route::prefix('game')->middleware(['login_auth:mobile'])->group(function () {
	Route::get('entry','GameController@entry');
	Route::post('deposit','GameController@deposit');
});
