<?php

Route::get('/', function(){
    echo  "aaa";
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

Route::get('/captcha', ['name' => 'captcha', 'uses' => 'Controller@captcha']);
// 所有路由都在这里配置
/** 代理改造，开放游客 */
//rtmp地址
Route::get('/room/rtmp/{rid:\d+}', ['name' => 'room_rtmp', 'uses' => 'RoomController@getRTMP']);
/****** 合作平台接口 ******/
Route::get('/recvSskey', ['name' => 'recvSskey', 'uses' => 'ApiController@platform']);
//直播间
Route::get('/{rid:\d+}[/{h5:h5|h5hls}]', ['name' => 'room', 'uses' => 'RoomController@index']);
//APP下载
Route::get('/download', ['name' => 'download', 'uses' => 'PageController@download']);

// 首页房间数据json
Route::get('/videoList', ['name' => 'index_videoList', 'uses' => 'IndexController@videoList']);
//首页
Route::get('/', ['name' => 'default', 'uses' => 'IndexController@indexAction']);
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

//rtmp地址
Route::get('/m/room/rtmp/{rid:\d+}', ['name' => 'm_room_rtmp', 'uses' => 'RoomController@getRTMP']);

Route::get('/find', ['name' => 'find', 'uses' => 'ApiController@searchAnchor']);

// 验证是否登录
Route::group(['middleware' => []], function () {
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

    // 用户中心 用户基本信息
    Route::get('/member', ['name' => 'member_index', 'uses' => 'MemberController@index']);
    Route::get('/member/index', ['name' => 'member_index', 'uses' => 'MemberController@index']);
    // 用户中心消息  type 是可有可无的 必须放到最后
    Route::get('/member/msglist[/{type:\d+}]', ['name' => 'member_msglist', 'uses' => 'MemberController@msglist']);
    // 用户中心 vip 贵族体系
    Route::get('/member/vip', ['name' => 'member_vip', 'uses' => 'MemberController@vip']);
    // 用户中心 vip 贵族体系主播佣金
    Route::get('/member/commission', ['name' => 'member_commission', 'uses' => 'MemberController@commission']);
    // 用户中心 推广中心 V2移除
    //Route::get('/member/invite', ['name' => 'member_invite', 'uses' => 'MemberController@invite']);
    // 用户中心 我的关注
    Route::get('/member/attention', ['name' => 'member_attention', 'uses' => 'MemberController@attention']);
    // 用户中心 我的道具
    Route::get('/member/scene', ['name' => 'member_scene', 'uses' => 'MemberController@scene']);
    // 用户中心 充值记录
    Route::get('/member/charge', ['name' => 'member_charge', 'uses' => 'MemberController@charge']);
    // 用户中心 消费记录
    Route::get('/member/consumerd', ['name' => 'member_consumerd', 'uses' => 'MemberController@consumerd']);
    // 用户中心 密码修改
    Route::match(['POST', 'GET'], '/member/password', ['name' => 'member_password', 'uses' => 'MemberController@password']);
    // 用户中心 我的预约
    Route::match(['POST', 'GET'], '/member/myReservation', ['name' => 'member_myReservation', 'uses' => 'MemberController@myReservation']);
    //删除一对一
    Route::get('/member/delRoomOne2One', ['name' => 'member_roomDelOne2One', 'uses' => 'MemberController@delRoomOne2One']);
    //删除一对多
    Route::get('/member/delRoomOne2Many', ['name' => 'member_roomDelOne2Many', 'uses' => 'MemberController@delRoomOne2Many']);
    //一对多添加
    Route::get('/member/roomOneToMore', ['uses' => 'MemberController@roomOneToMore']);
    //一对多记录详情-购买用户
    Route::get('/member/getBuyOneToMore', ['uses' => 'MemberController@getBuyOneToMore']);

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
    Route::match(['POST', 'GET'], '/member/withdraw', ['name' => 'member_withdraw', 'uses' => 'MemberController@withdraw']);
    // 用户中心 房间设置
    Route::match(['POST', 'GET'], '/member/roomSetTimecost', ['name' => 'member_roomset', 'uses' => 'MemberController@roomSetTimecost']);
    // 用户中心 转账
    Route::match(['POST', 'GET'], '/member/transfer', ['name' => 'member_transfer', 'uses' => 'MemberController@transfer']);

    // 用户中心 代理数据
    Route::match(['POST', 'GET'], '/member/agents', ['name' => 'member_agents', 'uses' => 'MemberController@agents']);

    //上传相册
    Route::match(['POST', 'GET'], '/fupload', ['name' => 'member_fupload', 'uses' => 'MemberController@flashUpload']);

    //上传头像
    Route::match(['POST', 'GET'], '/upload', ['name' => 'member_upload', 'uses' => 'MemberController@avatarUpload']);

    //安全邮箱验证
    Route::get('/mailverific', ['name' => 'mailverific', 'uses' => 'PasswordController@mailVerific']);
    Route::match(['GET', 'POST'], 'mailsend', ['name' => 'mailsend', 'uses' => 'PasswordController@mailSend']);
    Route::get('/verifymail[/{token:.+}]', ['name' => 'resetpassword', 'uses' => 'PasswordController@VerifySafeMail']);


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
});


// 验证是否登录 json
Route::group(['middleware' => []], function () {
    // 获取用户有多少钱
    Route::post('/getmoney', ['name' => 'shop_getmoney', 'uses' => 'MemberController@getmoney']);
    // 用户领取坐骑
    Route::post('/getvipmount', ['name' => 'shop_getvipmount', 'uses' => 'MemberController@getVipMount']);
    // 用户中心修改基本信息
    Route::post('/member/edituserinfo', ['name' => 'member_edituserinfo', 'uses' => 'MemberController@editUserInfo']);
    // 用户中心 预约房间设置
    Route::get('/member/roomSetDuration', ['name' => 'member_roomSetDuration', 'uses' => 'MemberController@roomSetDuration']);
    Route::get('/member/roomUpdateDuration', ['name' => 'member_roomUpdateDuration', 'uses' => 'MemberController@roomUpdateDuration']);
    Route::get('/member/delRoomDuration', ['name' => 'member_roomUpdateDuration', 'uses' => 'MemberController@delRoomDuration']);
    //一对多补票
    Route::post('/member/makeUpOneToMore', ['name' => 'makeUpOneToMore', 'uses' => 'MemberController@makeUpOneToMore']);
    // 用户中心 密码房间设置
    Route::post('/member/roomSetPwd', ['name' => 'member_roomSetPwd', 'uses' => 'MemberController@roomSetPwd']);
    // 用户中心 体现
    Route::post('/member/addwithdraw', ['name' => 'member_addwithdraw', 'uses' => 'MemberController@addwithdraw']);
    // 商城购买
    Route::post('/member/pay', ['name' => 'member_pay', 'uses' => 'MemberController@pay']);
// 密码房间
    Route::post('/checkroompwd', ['name' => 'checkroompwd', 'uses' => 'MemberController@checkroompwd']);

    Route::get('/member/doReservation', ['uses' => 'MemberController@doReservation']);
    Route::post('/member/domsg', ['uses' => 'MemberController@domsg']);
    // 用户中心 取消装备
    Route::get('/member/cancelscene', ['name' => 'member_cancelscene', 'uses' => 'MemberController@cancelScene']);
    //购买修改昵称
    Route::post('/member/buyModifyNickname', ['name' => 'buyModifyNickname', 'uses' => 'MemberController@buyModifyNickname']);
    //关注用户接口
    Route::get('/focus', ['name' => 'focus', 'uses' => 'ApiController@Follow']);
    //获取用户信息
    Route::get('/getuser/{id:\d+}', ['name' => 'getuser', 'uses' => 'ApiController@getUserByDes']);

    //邮箱验证
    Route::match(['GET', 'POST'], 'sendVerifyMail', ['name' => 'sendVerifyMail', 'uses' => 'PasswordController@sendVerifyMail']);

    // 目前房间内flash使用的获取商品的接口
    Route::get('/goods', ['name' => 'json_goods', 'uses' => 'ApiController@goods']);
    // 关键字屏蔽
    Route::get('/kw', ['name' => 'json_kw', 'uses' => 'ApiController@kw']);
    // 获取主播房间内的礼物清单
    Route::get('/rank_list_gift', ['name' => 'json_rank_list_gift', 'uses' => 'ApiController@rankListGift']);
    // 获取主播房间内的礼物排行榜
    Route::get('/rank_list_gift_week', ['name' => 'json_rank_list_gift_week', 'uses' => 'ApiController@rankListGiftWeek']);
    Route::get('/get_head_image', ['name' => 'get_head_image', 'uses' => 'ApiController@getUserHeadImage']);


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

//rtmp地址
Route::match(['POST', 'GET'], '/test_room/rtmp/{rid:\d+}', ['name' => 'room_rtmp', 'uses' => 'RoomController@get']);

// 充值类 TODO 登录验证
Route::match(['POST', 'GET'], '/charge/{action}', ['name' => 'charge', 'uses' => 'ChargeController@index']);

//连通测试
Route::get('/ping', ['name' => 'ping', 'uses' => 'ApiController@ping']);

//登录页面
Route::get('/passport', ['name' => 'passport', 'uses' => 'LoginController@passport']);
Route::match(['POST', 'GET'], '/login', ['name' => 'login', 'uses' => 'LoginController@login']);
Route::get('/synlogin', ['name' => 'synlogin', 'uses' => 'LoginController@synLogin']);
Route::get('/reload', ['name' => 'reload', 'uses' => 'LoginController@reloadLogin']);
Route::match(['POST', 'GET'], '/logout', ['name' => 'logout', 'uses' => 'LoginController@logout']);
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
//登录验证码
Route::get('/m/login/captcha', ['name' => 'm_login', 'uses' => 'Mobile\MobileController@loginCaptcha']);
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
Route::group(['middleware' => []], function () {
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
    //购买一对多
//    Route::post('/m/room/buyOneToMany', ['name' => 'm_buyOneToOne', 'uses' => 'Mobile\RoomController@buyOneToMany']);
    Route::post('/m/room/buyOneToMany', ['name' => 'm_buyOneToOne', 'uses' => 'Mobile\RoomController@makeUpOneToMore']);
    /** 检查密码房密码 */
    Route::post('/m/room/checkPwd', ['name' => 'm_room_checkpwd', 'uses' => 'Mobile\RoomController@checkPwd']);
    /** 获取RTMP地址 */
    Route::get('/m/room/rtmp/{rid:\d+}', ['name' => 'm_room_rtmp', 'uses' => 'RoomController@getRTMP']);
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
