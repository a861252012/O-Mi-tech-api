<?php

Route::group(['middleware' => ['login_auth']], function () {
    Route::match(['POST', 'GET'], '/onetomore', function () {
        echo "aaa";
    });
    Route::get('login/test', function () {
        return [Auth::guard()->user(), session()->getId()];
    });
});
Route::match(['POST', 'GET'], '/login', ['name' => 'login', 'uses' => 'LoginController@login']);

Route::get('/captcha', 'Controller@captcha');

/** 用户中心路由组 */
Route::group(['prefix' => 'member'], function () {
    Route::any('mail/verify/confirm/{token}', 'PasswordController@VerifySafeMail')->name('mail_verify_confirm');

    Route::group(['middleware' => 'login_auth'], function () {
        Route::get('menu', 'MemberController@getMenu');
        Route::get('index', 'MemberController@index')->name('member_index');
        Route::post('mail/verify/send', 'PasswordController@sendVerifyMail')->middleware('throttle:5:1')->name('mail_verify_send');
        // 用户中心消息  type 是可有可无的 必须放到最后
        Route::get('msglist/{type?}', 'MemberController@msglist')->where('type', '[0-9]+')->name('member_msglist');
        //购买修改昵称
        Route::post('buyModifyNickname', 'MemberController@buyModifyNickname')->name('buyModifyNickname');
        // 用户中心 我的关注
        Route::get('attention', 'MemberController@attention')->name('member_attention');
        // 用户中心 我的道具
        Route::any('scene', 'MemberController@scene')->name('member_scene');
        // 商城购买
        Route::post('pay', 'MemberController@pay')->name('member_pay');
        // 用户中心 取消装备
        Route::get('cancelscene', 'MemberController@cancelScene')->name('member_cancelscene');
        // 用户中心 充值记录
        Route::get('charge', 'MemberController@charge')->name('member_charge');
        // 用户中心 消费记录
        Route::get('consumerd', 'MemberController@consumerd')->name('member_consumerd');
        // 用户中心 密码修改
        Route::post('password/change', 'MemberController@passwordChange')->name('member_password');
        // 用户中心 我的预约
        Route::match(['POST', 'GET'], 'myReservation', 'MemberController@myReservation')->name('member_myReservation');
        // 用户中心 转账
        Route::get('transfer', 'MemberController@transferHistory')->name('member_transfer_history');
        Route::post('transfer/create', 'MemberController@transfer')->name('member_transfer_create');
        // 用户中心 提现
        Route::get('withdraw', 'MemberController@withdrawHistory')->name('member_withdraw_history');
        Route::post('withdraw/create', 'MemberController@withdraw')->name('member_withdraw_create');
    });
});


Route::group(['prefix' => 'user'], function () {
    Route::group(['middleware' => 'login_auth'], function () {
        Route::get('current', 'UserController@getCurrentUser')->name('user_current');
        Route::get('following', 'UserController@following')->name('user_current');
    });
});


// 所有路由都在这里配置
/** 代理改造，开放游客 */
//rtmp地址
Route::get('/room/rtmp/{rid}', 'RoomController@getRTMP')->where('rid', '[0-9]+')->name('room_rtmp');
/****** 合作平台接口 ******/
Route::get('/recvSskey', ['name' => 'recvSskey', 'uses' => 'ApiController@platform']);
//直播间
Route::get('/{rid:\d+}[/{h5:h5|h5hls}]', ['name' => 'room', 'uses' => 'RoomController@index']);
//APP下载
Route::get('/download', ['name' => 'download', 'uses' => 'PageController@download']);

// 首页房间数据json
Route::get('/videoList', ['name' => 'index_videoList', 'uses' => 'IndexController@videoList']);
//首页
Route::get('/', 'IndexController@indexAction');
// 获取主播房间内的礼物清单
Route::get('/rank_list_gift', ['name' => 'json_rank_list_gift', 'uses' => 'ApiController@rankListGift']);
// 获取主播房间内的礼物排行榜
Route::get('/rank_list_gift_week', ['name' => 'json_rank_list_gift_week', 'uses' => 'ApiController@rankListGiftWeek']);
Route::get('/get_head_image', ['name' => 'get_head_image', 'uses' => 'ApiController@getUserHeadImage']);
// 目前房间内flash使用的获取商品的接口
Route::get('/goods', ['name' => 'json_goods', 'uses' => 'ApiController@goods']);
// 关键字屏蔽
Route::get('/kw', ['name' => 'json_kw', 'uses' => 'ApiController@kw']);
// 贵族详情页面
Route::get('/noble', ['name' => 'noble', 'uses' => 'PageController@noble']);
// 商城页面
Route::get('/shop', ['name' => 'shop_index', 'uses' => 'ShopController@index']);
//活动页面
Route::get('/act', ['name' => 'act', 'uses' => 'ActivityController@index']);
//活动详情页面
Route::get('/activity/{action}', ['name' => 'activity_detail', 'uses' => 'ActivityController@activity']);
// PageController 招募页面
Route::get('/join', ['name' => 'join', 'uses' => 'PageController@join']);
Route::get('/cooperation', ['name' => 'join', 'uses' => 'PageController@cooperation']);
// 排行榜页面
Route::get('/ranking', ['name' => 'rank_index', 'uses' => 'RankController@index']);
//排行榜接口
Route::get('/rank_data', ['name' => 'rank_data', 'uses' => 'RankController@rankData']);
// 关于 帮助 投诉
Route::get('/about/{act:[a-z_]+}', ['uses' => 'PageController@index']);
// 活动详情页面
Route::get('/nac/{id:\d+}', ['name' => 'ac_info', 'uses' => 'ActivityController@info']);
Route::match(['GET', 'POST'], '/majax/{action}', ['name' => 'majax', 'uses' => 'MemberController@ajax']);

Route::match(['POST', 'GET'], '/indexinfo', ['name' => 'indexinfo', 'uses' => 'IndexController@getIndexInfo']);
//代理
Route::get('/extend[/{url:.+}]', ['name' => 'business_extend', 'uses' => 'BusinessController@extend']);
Route::get('/CharmStar', ['name' => 'charmstar', 'uses' => 'ActivityController@charmstar']);

Route::get('/getgroupall', ['name' => 'shop_getgroupall', 'uses' => 'ShopController@getGroupAll']);

Route::post('/reg', ['name' => 'api_reg', 'uses' => 'ApiController@reg']);

// PageController
Route::get('/search', ['name' => 'search', 'uses' => 'PageController@search']);


Route::get('/find', ['name' => 'find', 'uses' => 'ApiController@searchAnchor']);

// 验证是否登录
Route::group(['middleware' => ['login_auth']], function () {
// 任务api
    Route::get('/task', ['name' => 'task_index', 'uses' => 'TaskController@index']);
// 任务完成领取奖励api
    Route::get('/task/end/{id:\d+}', ['uses' => 'TaskController@billTask']);


// 排行榜页面
    Route::get('/ranking', ['name' => 'rank_index', 'uses' => 'RankController@index']);


// PageController 招募页面
    Route::get('/join', ['name' => 'join', 'uses' => 'PageController@join']);
    Route::get('/cooperation', ['name' => 'join', 'uses' => 'PageController@cooperation']);
//邀请注册数据临时处理过程
    Route::get('/interface', ['name' => 'invitation', 'uses' => 'ApiController@Invitation']);

//活动送礼接口
    Route::get('/activity', ['name' => 'activity', 'uses' => 'ApiController@Activity']);

//活动页面
    Route::get('/act', ['name' => 'act', 'uses' => 'ActivityController@index']);
//活动详情页面
    Route::get('/activity/{action}', ['name' => 'activity_detail', 'uses' => 'ActivityController@activity']);

//魅力之星活动排行榜
    Route::get('/CharmStar', ['name' => 'charmstar', 'uses' => 'ActivityController@charmstar']);

//列举最近20个充值的用户
    Route::get('/activityShow', ['name' => 'activityShow', 'uses' => 'ApiController@getLastChargeUser']);

//下载扩充
    Route::get('/download/[{filename:.+}]', ['name' => 'download', 'uses' => 'ApiController@download']);

//获取桌面图标
    Route::get('/shorturl', ['name' => 'shorturl', 'uses' => 'ApiController@shortUrl']);

//roomcnt
    Route::get('/roomcnt', ['name' => 'flashcount', 'uses' => 'ApiController@flashCount']);

//findroomcnt
    Route::get('/findroomcnt', ['name' => 'getflashcount', 'uses' => 'ApiController@getFlashCount']);

//图片静态化
    Route::post('/coverUpload', ['name' => 'coverUpload', 'uses' => 'ApiController@coverUpload']);
//图片静态化
    Route::get('/convertstaticimg', ['name' => 'imagestatic', 'uses' => 'ApiController@imageStatic']);
//FIND

//更新点击数
    Route::get('/clickadd', ['name' => 'click', 'uses' => 'ApiController@click']);


//取回密码验证
    Route::match(['GET', 'POST'], '/resetpassword[/{token:.+}]', ['name' => 'resetpassword', 'uses' => 'PasswordController@resetPassword']);

    Route::match(['GET', 'POST'], '/majax/{action}', ['name' => 'majax', 'uses' => 'MemberController@ajax']);


//验证码，新版本移除
    //Route::get('/verfiycode', ['uses' => 'IndexController@captcha']);


    // 用户中心 vip 贵族体系
    Route::get('/member/vip', ['name' => 'member_vip', 'uses' => 'MemberController@vip']);
    // 用户中心 vip 贵族体系主播佣金
    Route::get('/member/commission', ['name' => 'member_commission', 'uses' => 'MemberController@commission']);
    // 用户中心 推广中心 V2移除
    //Route::get('/member/invite', ['name' => 'member_invite', 'uses' => 'MemberController@invite']);
    // 用户中心 我的关注
    Route::get('/member/attention', ['name' => 'member_attention', 'uses' => 'MemberController@attention']);


    //删除一对多
    Route::get('/member/delRoomOne2Many', ['name' => 'member_roomDelOne2Many', 'uses' => 'MemberController@delRoomOne2Many']);
    //一对多记录详情-购买用户
    Route::get('/member/getBuyOneToMore', ['uses' => 'MemberController@getBuyOneToMore']);
    //pc创建一对多
    Route::get('/member/roomOneToMore', ['name' => 'roomOneToMore', 'uses' => 'MemberController@roomOneToMore']);
    //pc一对多补票
    Route::post('/member/makeUpOneToMore', ['name' => 'makeUpOneToMore', 'uses' => 'MemberController@makeUpOneToMore']);
    //pc删除一对多
    Route::get('/member/delRoomOne2Many', ['name' => 'member_roomDelOne2Many', 'uses' => 'MemberController@delRoomOne2Many']);
    //pc端添加一第一
    Route::get('/member/roomSetDuration', ['name' => 'member_roomSetDuration', 'uses' => 'MemberController@roomSetDuration']);
    //pc端预约一对一
    Route::get('/member/doReservation', ['uses' => 'MemberController@doReservation']);
    //pc端修改房间时长
    Route::get('/member/roomUpdateDuration', ['name' => 'member_roomUpdateDuration', 'uses' => 'MemberController@roomUpdateDuration']);
    //pc端删除一对一
    Route::get('/member/delRoomDuration', ['name' => 'member_roomUpdateDuration', 'uses' => 'MemberController@delRoomDuration']);


    // 用户中心 房间游戏
    Route::get('/member/gamelist[/{type:\d+}]', ['name' => 'member_gamelist', 'uses' => 'MemberController@gamelist']);
    // 用户中心 礼物统计
    Route::get('/member/count', ['name' => 'member_count', 'uses' => 'MemberController@count']);
    // 用户中心 直播统计
    Route::match(['POST', 'GET'], '/member/live', ['name' => 'member_live', 'uses' => 'MemberController@live']);
    // 用户中心 主播中心
    Route::match(['POST', 'GET'], '/member/anchor', ['name' => 'member_anchor', 'uses' => 'MemberController@anchor']);
    // 用户中心 房间设置
    Route::match(['POST', 'GET'], '/member/roomset', ['name' => 'member_roomset', 'uses' => 'MemberController@roomset']);

    // 用户中心 房间设置
    Route::match(['POST', 'GET'], '/member/roomSetTimecost', ['name' => 'member_roomset', 'uses' => 'MemberController@roomSetTimecost']);

    // 用户中心 代理数据
    Route::match(['POST', 'GET'], '/member/agents', ['name' => 'member_agents', 'uses' => 'MemberController@agents']);

    //上传相册
    Route::match(['POST', 'GET'], '/fupload', ['name' => 'member_fupload', 'uses' => 'MemberController@flashUpload']);

    //上传头像
    Route::match(['POST', 'GET'], '/upload', ['name' => 'member_upload', 'uses' => 'MemberController@avatarUpload']);


    //隐身功能接口
    Route::get('/member/hidden[/{status:\d+}]', ['name' => 'hidden', 'uses' => 'MemberController@hidden']);

    //获取用户信息
    Route::get('/getuser/{id:\d+}', ['name' => 'getuser', 'uses' => 'ApiController@getUserByDes']);

    //获取关注用户接口
    Route::get('/getuseratten/{id:\d+}', ['name' => 'getuseratten', 'uses' => 'ApiController@getUserFollows']);


    //私信接口  v2版本中去掉了
//    Route::match(['POST', 'GET'], '/letter', ['name' => 'letter', 'uses' => 'ApiController@Letter']);

    //获取余额接口
    Route::get('/balance', ['name' => 'balance', 'uses' => 'ApiController@Balance']);

    //抽奖接口
    Route::get('/lottery', ['name' => 'lottery', 'uses' => 'ApiController@lottery']);

    //抽奖信息接口
    Route::get('/lotteryinfo', ['name' => 'lotteryinfo', 'uses' => 'ApiController@lotteryinfo']);

    // 房间管理员
    Route::match(['POST', 'GET'], '/member/roomadmin', ['name' => 'roomadmin', 'uses' => 'MemberController@roomadmin']);


    // 开通贵族
    Route::get('/openvip', ['name' => 'shop_openvip', 'uses' => 'MemberController@buyVip']);

    // 贵族根据id获取贵族信息
    Route::get('/getgroup', ['name' => 'shop_getgroup', 'uses' => 'ShopController@getgroup']);
    Route::get('/getgroupall', ['name' => 'shop_getgroupall', 'uses' => 'ShopController@getGroupAll']);

    // 商城页面
    Route::get('/shop', ['name' => 'shop_index', 'uses' => 'ShopController@index']);

    // 关于 帮助 投诉
    Route::get('/about/{act:[a-z_]+}', ['uses' => 'PageController@index']);

    // 贵族详情页面
    Route::get('/noble', ['name' => 'noble', 'uses' => 'PageController@noble']);

    // 活动详情页面
    Route::get('/nac/{id:\d+}', ['name' => 'ac_info', 'uses' => 'ActivityController@info']);

    // 招募页面
    Route::match(['POST', 'GET'], '/business/{act:[a-z]+}', ['name' => 'business_url', 'uses' => 'BusinessController@index']);

    // 代理页面
    Route::get('/extend[/{url:.+}]', ['name' => 'business_extend', 'uses' => 'BusinessController@extend']);

    // 主播空间
    Route::get('/space', ['name' => 'shop_space', 'uses' => 'SpaceController@index']);


    // test 测试用
    Route::get('/task/bb', function () {
        return new \Symfony\Component\HttpFoundation\Response('4455566');
    });


    Route::match(['POST', 'GET'], '/verfiyName', ['uses' => 'IndexController@checkUniqueName']);
    Route::match(['POST', 'GET'], '/setinroomstat', ['name' => 'setinroomstat', 'uses' => 'IndexController@setInRoomStat']);

    Route::post('/complaints', ['uses' => 'IndexController@complaints']);
    Route::get('/cliget/{act}', ['uses' => 'IndexController@cliGetRes']);


    //mobile 移动端
    //首页
    //   Route::get('/m/index', ['name' => 'm_index', 'uses' => 'MobileController@index']);
    //排行
    //  Route::get('/m/rank', ['name' => 'm_rank', 'uses' => 'MobileController@rank']);
    //登录
    //  Route::get('/m/login', ['name' => 'm_login', 'uses' => 'MobileController@login']);
    //注册
    //  Route::get('/m/register', ['name' => 'm_register', 'uses' => 'MobileController@register']);


    // Route::get('/{rid:\d+}[/{h5:h5}]',['as'=>'room','uses'=>'RoomController@index']);

    // 获取用户有多少钱
    Route::post('/getmoney', ['name' => 'shop_getmoney', 'uses' => 'MemberController@getmoney']);
    // 用户领取坐骑
    Route::post('/getvipmount', ['name' => 'shop_getvipmount', 'uses' => 'MemberController@getVipMount']);
    // 用户中心修改基本信息
    Route::post('/member/edituserinfo', ['name' => 'member_edituserinfo', 'uses' => 'MemberController@editUserInfo']);
    // 用户中心 预约房间设置

    Route::post('/member/domsg', ['uses' => 'MemberController@domsg']);
   // 用户中心 密码房间设置
    Route::post('/member/roomSetPwd', ['name' => 'member_roomSetPwd', 'uses' => 'MemberController@roomSetPwd']);
    // 用户中心 体现
    Route::post('/member/addwithdraw', ['name' => 'member_addwithdraw', 'uses' => 'MemberController@addwithdraw']);

    // 密码房间
    Route::post('/checkroompwd', ['name' => 'checkroompwd', 'uses' => 'MemberController@checkroompwd']);



    //关注用户接口
    Route::any('/focus', ['name' => 'focus', 'uses' => 'ApiController@Follow']);
    //获取用户信息
    Route::get('/getuser/{id:\d+}', ['name' => 'getuser', 'uses' => 'ApiController@getUserByDes']);

    //邮箱验证
    Route::match(['GET', 'POST'], 'sendVerifyMail', ['name' => 'sendVerifyMail', 'uses' => 'PasswordController@sendVerifyMail']);

    // 关键字屏蔽
    Route::get('/kw', ['name' => 'json_kw', 'uses' => 'ApiController@kw']);
    // 获取主播房间内的礼物清单
    Route::get('/rank_list_gift', ['name' => 'json_rank_list_gift', 'uses' => 'ApiController@rankListGift']);
    // 获取主播房间内的礼物排行榜
    Route::get('/rank_list_gift_week', ['name' => 'json_rank_list_gift_week', 'uses' => 'ApiController@rankListGiftWeek']);


    //flashCookie记录api
    Route::get('/api/flashcookie', ['name' => 'flashcookie', 'uses' => 'ApiController@FlashCookie']);

    //对换
    Route::post('/api/plat_exchange', ['name' => 'plat_exchange', 'uses' => 'ApiController@platExchange']);


    //平台一对多跳转测试
    Route::get('/switchone2more', ['name' => 'switchone2more', 'uses' => 'RoomController@switchToOne2More']);

    //获取时长打折信处
    Route::get('/getTimeCountRoomDiscountInfo', ['name' => 'flashcookie', 'uses' => 'ApiController@getTimeCountRoomDiscountInfo']);
    //rtmp地址
    Route::get('/room/rtmp/{rid:\d+}', ['name' => 'room_rtmp', 'uses' => 'RoomController@getRTMP']);
    //排行榜数据
    Route::get('/rank_data', ['name' => 'rank_data', 'uses' => 'RankController@rankData']);

    Route::get('/ajaxProxy', ['name' => 'ajaxProxy', 'uses' => 'ApiController@ajaxProxy']);
});
Route::get('/m/test', ['name' => 'm_testasds', 'uses' => 'Mobile\MobileController@test']);
Route::get('/m/login', ['name' => 'm_login', 'uses' => 'Mobile\MobileController@login']);
//rtmp地址
Route::match(['POST', 'GET'], '/test_room/rtmp/{rid:\d+}', ['name' => 'room_rtmp', 'uses' => 'RoomController@get']);

// 充值类 TODO 登录验证
// 验证是否登录
Route::group(['prefix' => 'charge', 'middleware' => ['charge', 'login_auth']], function () {
    Route::match(['POST', 'GET'], 'pay', ['name' => 'charge_pay', 'uses' => 'ChargeController@pay']);
    Route::match(['POST', 'GET'], '/order', ['name' => 'charge_order', 'uses' => 'ChargeController@order']);
    Route::match(['POST', 'GET'], '/order2', ['name' => 'charge_order2', 'uses' => 'ChargeController@order2']);
    Route::match(['POST', 'GET'], '/pay2', ['name' => 'charge_pay2', 'uses' => 'ChargeController@pay2']);
    Route::match(['POST', 'GET'], '/translate', ['name' => 'translate', 'uses' => 'ChargeController@translate']);

});
Route::get('/test', function () {
  //  \App\Models\Users::find(10000)->notify(new \App\Notifications\InvoicePaid(['a'=>'a','b'=>'c']));
    $active = \App\Models\Active::find(18);
     \Illuminate\Support\Facades\Notification::send($active,new \App\Notifications\InvoicePaid(['a'=>'a','b'=>'c']));
     //->notify(new \App\Notifications\InvoicePaid(['a'=>'a','b'=>'c']));

});
Route::get('/test_read', function () {
    $user = \App\Models\Users::find(10000);
    foreach ($user->unreadNotifications as $notification) {
        dd($notification->data);
        // $notification->markAsRead();
    }
});
//通知
Route::group(['prefix' => 'charge', 'middleware' => ['charge']], function () {
    Route::match(['POST', 'GET'], 'notice2', ['name' => 'notice2', 'uses' => 'ChargeController@notice2']);
    Route::match(['POST', 'GET'], 'notice', ['name' => 'charge_notice', 'uses' => 'ChargeController@notice'])->name('charge_notice');
    Route::match(['POST', 'GET'], 'checkKeepVip', ['name' => 'checkKeepVip', 'uses' => 'ChargeController@checkKeepVip']);
    Route::match(['POST', 'GET'], 'callFailOrder', ['name' => 'callFailOrder', 'uses' => 'ChargeController@callFailOrder']);
//    Route::post( 'moniCharge', ['name' => 'charge', 'uses' => 'ChargeController@moniCharge']);
    Route::match(['POST', 'GET'], 'moniHandler', ['name' => 'moniHandler', 'uses' => 'ChargeController@moniHandler']);
    Route::post('del', ['name' => 'charge', 'uses' => 'ChargeController@del']);
});

//连通测试
Route::get('/ping', ['name' => 'ping', 'uses' => 'ApiController@ping']);

//登录页面
Route::get('/passport', ['name' => 'passport', 'uses' => 'LoginController@passport']);
Route::match(['POST', 'GET'], '/login', ['name' => 'login', 'uses' => 'LoginController@login']);
Route::get('/synlogin', ['name' => 'synlogin', 'uses' => 'LoginController@synLogin']);
Route::get('/reload', ['name' => 'reload', 'uses' => 'LoginController@reloadLogin']);
Route::any('/logout', 'LoginController@logout');
//Route::match(['POST', 'GET'], '/peachReg', ['name' => 'peachReg', 'uses' => 'LoginController@mitaoReg']);
Route::match(['POST', 'GET'], '/api/register', ['name' => 'api_register', 'uses' => 'ApiController@register']);
Route::match(['POST', 'GET'], '/api/register_agents', ['name' => 'api_agents', 'uses' => 'ApiController@registerAgents']);


Route::match(['POST', 'GET'], '/get_lcertificate', ['name' => 'api_agents', 'uses' => 'ApiController@get_lcertificate']);

// 用户flash段调取sid的
Route::get('/loadsid', ['name' => 'loadsid', 'uses' => 'ApiController@loadSid']);

// 找回密码
Route::get('/getpwd', ['name' => 'shop_getpwd', 'uses' => 'PasswordController@getpwd']);
// 找回密码
Route::post('/getpwdsuccess', ['name' => 'shop_getpwdsuccess', 'uses' => 'PasswordController@getPwdSuccess']);
// 找回密码
Route::get('/islogin', ['name' => 'islogin', 'uses' => 'LoginController@isLogin']);
//票据
Route::get('/get_lcertificate', ['name' => 'islogin', 'uses' => 'ApiController@get_lcertificate']);

//==================================================================================
/**
 * 移动端相关路由
 **/

//首页
Route::get('/m/index', ['name' => 'm_index', 'uses' => 'Mobile\MobileController@index']);
//排行
Route::get('/m/rank', ['name' => 'm_rank', 'uses' => 'Mobile\MobileController@rank']);

//注册
Route::get('/m/register', ['name' => 'm_register', 'uses' => 'Mobile\MobileController@register']);

//轮播图获取
Route::get('/m/sliderlist', ['name' => 'm_sliderlist', 'uses' => 'Mobile\MobileController@sliderList']);

//活动列表
Route::get('/m/activitylist', ['name' => 'm_activitylist', 'uses' => 'Mobile\MobileController@activityList']);

//活动详情
Route::get('/m/activitydetail/{id}', ['name' => 'm_activitydetail', 'uses' => 'Mobile\MobileController@activityDetail']);

//主播列表
Route::get('/m/video/list/{type}', ['name' => 'm_videolist', 'uses' => 'Mobile\MobileController@videoList']);
//app版本ｈ
Route::get('/m/app/version', ['name' => 'm_app_ver', 'uses' => 'Mobile\MobileController@appVersion']);
Route::get('/m/app/versionIOS', ['name' => 'm_app_ver_ios', 'uses' => 'Mobile\MobileController@appVersionIOS']);
Route::post('/m/app/version', ['name' => 'm_app_ver', 'uses' => 'Mobile\MobileController@appVersion']);
Route::post('/m/app/versionIOS', ['name' => 'm_app_ver_ios', 'uses' => 'Mobile\MobileController@appVersionIOS']);
/** 获取配置 */
Route::get('/m/conf', ['name' => 'm_conf', 'uses' => 'Mobile\RoomController@getConf']);
Route::get('/m/room/conf', ['name' => 'm_room_conf', 'uses' => 'Mobile\RoomController@getRoomConf']);
Route::get('/m/room/{rid}/checkAccess', ['name' => 'm_room_checkAccess', 'uses' => 'Mobile\RoomController@getRoomAccess']);
//移动端登录验证
Route::group(['middleware' => ['login_auth']], function () {
//    Route::get('/m/logintest', ['name' => 'm_logintest', 'uses' => 'Mobile\MobileController@logintest']);

    //关注列表
    Route::get('/m/user/following', ['name' => 'm_userfollowing', 'uses' => 'Mobile\MobileController@userFollowing']);
    //用户信息
    Route::get('/m/user/info', ['name' => 'm_userinfo', 'uses' => 'Mobile\MobileController@userInfo']);
    //用户特权
    Route::get('/m/user/privilege', ['name' => 'm_userprivilege', 'uses' => 'Mobile\MobileController@userPrivilege']);
    //座驾列表
    Route::get('/m/user/mount/list', ['name' => 'm_usermountlist', 'uses' => 'Mobile\MobileController@mountList']);
    Route::post('/m/user/mount/{gid}', ['name' => 'm_usermount', 'uses' => 'Mobile\MobileController@mount']);
    Route::post('/m/user/unmount/{gid}', ['name' => 'm_userunmount', 'uses' => 'Mobile\MobileController@unmount']);
    //关注
    Route::match(['POST', 'GET'], '/m/follow', ['name' => 'm_follow', 'uses' => 'Mobile\MobileController@follow']);
    //预约列表 type=1 一对一，type=2 一对多,type=3 所有
    Route::get('/m/room/reservation/{type}', ['name' => 'm_userroomreservation', 'uses' => 'Mobile\RoomController@listReservation']);
    //隐身
    Route::get('/m/user/stealth[/{status:\d+}]', ['name' => 'm_stealth', 'uses' => 'Mobile\MobileController@stealth']);
    //购买一对一
    Route::post('/m/room/buyOneToOne', ['name' => 'm_buyOneToOne', 'uses' => 'Mobile\RoomController@buyOneToOne']);

    /** 检查密码房密码 */
    Route::post('/m/room/checkPwd', ['name' => 'm_room_checkpwd', 'uses' => 'Mobile\RoomController@checkPwd']);
    /** 获取RTMP地址 */
    Route::get('/m/room/rtmp/{rid:\d+}', ['name' => 'm_room_rtmp', 'uses' => 'RoomController@getRTMP']);

    //移动端创建一对多
    Route::get('/m/OneToMore/create', ['name' => 'OneToMore', 'uses' => 'Mobile\RoomController@createOne2More']);
    //移动端购买一对多
    Route::post('/m/room/buyOneToMany', ['name' => 'm_buyOneToOne', 'uses' => 'Mobile\RoomController@makeUpOneToMore']);
    //app删除一对多房间
    Route::get('/m/OneToMore/delete', ['name' => 'm_onetomoredel', 'uses' => 'Mobile\RoomController@delRoomOne2More']);
    //app一对多房间列表
    Route::get('/m/OneToMore/list', ['as' => 'm_onetomorelist', 'uses' => 'Mobile\RoomController@listOneToMoreByHost']);
    //app判断是否开通一对多和一对一
    Route::get('/m/oneToManyCompetence', ['as' => 'competence', 'uses' => 'Mobile\RoomController@competence']);
    //app直播记录表
    Route::get('/m/showlist', ['as' => 'm_showlist', 'uses' => 'Mobile\RoomController@showlist']);

    //移动端删除一对一
    Route::get('/m/room/delRoomOne2One', ['name' => 'member_roomDelOne2One', 'uses' => 'Mobile\RoomController@delRoomOne2One']);
    //移动端预约一对一
    Route::get('/m/room/buyOneToOne', ['name' => 'm_buyOneToOne', 'uses' => 'Mobile\RoomController@buyOneToOne']);
    //移动端创建一对一
    Route::get('/m/room/roomSetDuration', ['name' => 'member_roomSetDuration', 'uses' => 'Mobile\RoomController@roomSetDuration']);
    //移动端一对一房间用户
    Route::get('/m/OneToOne/list', ['name' => 'member_onetoONElist', 'uses' => 'Mobile\MobileController@listOneToOneByHost']);

});
Route::get('/m/find', ['name' => 'm_find', 'uses' => 'Mobile\MobileController@searchAnchor']);
Route::match(['POST', 'GET'], '/m/pay/{action}', ['name' => 'm_pay', 'uses' => 'Mobile\PaymentController@action']);
//统计接口
Route::match(['POST', 'GET'], '/m/statistic', ['name' => 'm_statistic', 'uses' => 'Mobile\MobileController@statistic']);

Route::get('/oort2bunny', ['name' => 'guanggao', 'uses' => 'AdsController@getAd']);//广告接口
Route::post('/send_crash', ['name' => 'send_crash', 'uses' => 'Mobile\MobileController@saveCrash']);//app报错接口


//
Route::match(['POST', 'GET'], '/return', function () {
    return new \Symfony\Component\HttpFoundation\Response('4455566');
});

Route::match(['POST', 'GET'], '/pay/g2p', ['name' => 'pay_g2p', 'uses' => 'Pay\PayController@index']);
Route::match(['POST', 'GET'], '/pay/{action}', ['name' => 'pay_notify', 'uses' => 'Pay\PayController@notify']);
//Route::match(['POST', 'GET'], '/pay/{action}', function(){
//    echo json_encode($_POST);
//    file_put_contents(BASEDIR.'/app/logs/inner.log',json_encode($_POST));
//});
Route::match(['POST', 'GET'], '/getss', ['name' => 'getss', 'uses' => 'ApiController@getLog']);
