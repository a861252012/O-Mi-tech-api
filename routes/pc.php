<?php
//Route::group(['prefix'=>'opcache'],function (){
//   Route::get('status','OPcacheController@status');
//   Route::get('config','OPcacheController@config');
//   Route::get('flush','OPcacheController@flush');
//});
Route::match(['POST', 'GET'], '/login', ['name' => 'login', 'uses' => 'LoginController@login']);
Route::get('/captcha', 'LoginController@captcha');
//Route::get('test_recharge', function(){
//    return view('test.recharge');
//});

// send SMS
Route::post('/sms/send', 'SmsController@send')->name('sms_send');

// 任务列表
Route::get('/task', 'TaskController@index')->name('task_index');
//贵族列表
Route::get('/getgroupall', ['name' => 'shop_getgroupall', 'uses' => 'ShopController@getGroupAll']);
Route::get('room/{rid}/{h5?}', 'RoomController@index')
    ->where('rid', '[0-9]+')
    ->where('h5', '(h5|h5hls)');
//房间中间页
Route::get('/room/roommid/{roomid}/{rid}/{id?}', ['name' => 'room_roommid', 'uses' => 'RoomController@roommid'])->where('roomid', '[0-9]+')->where('rid', '[0-9]+')->where('id', '[0-9]+');
// 招募页面
Route::post('/business/{act}', ['name' => 'shop_getgroupall', 'uses' => 'BusinessController@index']);

Route::group(['middleware' => ['login_auth']], function () {
    //上传相册
    Route::post('fupload', 'MemberController@flashUpload')->name('member_fupload');
    //上传头像
    Route::post('upload', 'MemberController@avatarUpload')->name('avatar_upload');
    //图片静态化
    Route::post('/coverUpload', 'ApiController@coverUpload')->name('coverUpload');
    // 任务完成领取奖励api
    Route::any('/task/end/{id}', 'TaskController@billTask')->where('end', '[0-9]+');
    // 投诉
    Route::post('complaints', 'IndexController@complaints');
    // 商城购买
    Route::post('shop/buy', 'MemberController@pay')->name('shop_buy');
});

//修改密码
Route::any('change_pwd', 'PasswordController@changePwd')->name('change_pwd');

/** 用户中心路由组 */
Route::group(['prefix' => 'member'], function () {
    Route::any('mail/verify/confirm/{token}', 'PasswordController@VerifySafeMail')->name('mail_verify_confirm');

    Route::group(['middleware' => 'login_auth'], function () {
        Route::get('menu', 'MemberController@getMenu');
        Route::get('index', 'MemberController@index')->name('member_index');

        // modify mobile
        Route::post('modifymobile/send', 'MemberController@modifyMobileSend')->name('member_modify_mobile_send');
        Route::post('modifymobile/confirm', 'MemberController@modifyMobileConfirm')->name('member_modify_mobile_confirm');

        Route::post('mail/verify/send', 'PasswordController@sendVerifyMail')->middleware('throttle.route:1,1')->name('mail_verify_send');
        // 用户中心消息  type 是可有可无的 必须放到最后
        Route::get('message/{type?}', 'MemberController@msglist')->where('type', '[0-9]+')->name('member_msglist');
        Route::post('/member/domsg', ['uses' => 'MemberController@domsg']);
        //购买修改昵称
        Route::post('buyModifyNickname', 'MemberController@buyModifyNickname')->name('buyModifyNickname');
        // 用户中心 我的关注
        Route::get('attention', 'MemberController@attention')->name('member_attention');
        // 用户中心 我的道具
        Route::get('scene', 'MemberController@scene')->name('member_scene');
        Route::post('scene/toggle', 'MemberController@sceneToggle')->name('member_scene_toggle');
        // 用户中心 取消装备
        // 用户中心 充值记录
        Route::get('charge', 'MemberController@charge')->name('member_charge');
        // 用户中心 消费记录
        Route::get('consume', 'MemberController@consume')->name('member_consume');
        // 用户中心 密码修改
        Route::post('password/change', 'MemberController@passwordChange')->name('member_password');
        Route::post('password', 'MemberController@password')->name('password');
        // 用户中心 取得live的充值小妹contact
        Route::get('contact', 'MemberController@contact')->name('contact');
        // 用户中心 我的预约
        Route::get('reservation', 'MemberController@reservation')->name('member_reservation');
        // 用户中心 转账
        Route::get('transfer', 'MemberController@transferHistory')->name('member_transfer_history');
        Route::post('transfer/create', 'MemberController@transfer')->name('member_transfer_create');
        // 用户中心 提现
        Route::get('withdraw', 'MemberController@withdrawHistory')->name('member_withdraw_history');
        Route::post('withdraw/request', 'MemberController@withdraw')->name('member_withdraw_create');
        // 用户中心 主播中心
        Route::get('anchor', 'MemberController@anchor')->name('member_anchor');
        // 用户中心 房间游戏
        Route::get('game/{type?}', 'MemberController@gamelist')->where('type', '[1,2]')->name('member_gamelist');
        // 用户中心 vip 贵族体系主播佣金
        Route::get('commission', 'MemberController@commission')->name('member_commission');
        // 用户中心 直播统计
        Route::get('live', 'MemberController@live')->name('member_live');
        // 用户中心修改基本信息
        Route::post('edituserinfo', 'MemberController@editUserInfo')->name('member_edituserinfo');
        // 红包明细
        Route::get('redEnvelopeGet', 'MemberController@redEnvelopeGet');
        Route::get('redEnvelopeSend', 'MemberController@redEnvelopeSend');
        // 签到
        Route::any('signin', 'MemberController@signin');
        // 主播房间暱称
        Route::any('roomInfo', 'MemberController@roomInfo');
    });
});

Route::group(['prefix' => 'user'], function () {
    Route::post('pwdreset/by_mobile', 'PasswordController@pwdResetByMobile')->middleware('throttle.route:10,1')->name('pwdreset_by_mobile');
    Route::post('pwdreset/submit', 'PasswordController@pwdResetSubmit')->middleware('throttle.route:10,1')->name('pwdreset_submit');
    Route::post('pwdreset/reset', 'PasswordController@pwdResetConfirm');
    Route::get('check', 'UserController@check')->name('user_check');    // 是否允許用戶停在直播間
    Route::group(['middleware' => 'login_auth'], function () {
        Route::get('current', 'UserController@getCurrentUser')->name('user_current');
        Route::get('following', 'UserController@following')->name('user_current');
        //获取关注用户接口
        Route::get('followed/count', ['name' => '', 'uses' => 'ApiController@getUserFollows'])->name('getuseratten');

        /* 用戶隱身 */
        Route::get('set_hidden/{status?}', 'UserController@setHidden');

        /* - 取得用戶背包物品列表 */
        Route::get('items', 'BackPackController@getItemList');

        /* 使用背包物品 */
        Route::get('item/use/{id}', 'BackPackController@useItem');
    });
});


// 所有路由都在这里配置
/** 代理改造，开放游客 */
/****** 合作平台接口 ******/
Route::get('/recvSskey', ['name' => 'recvSskey', 'uses' => 'ApiController@platform']);
// 测试接口
Route::post('/live/checked', ['name' => 'recvSskey', 'uses' => 'ApiController@platformGetUser']);
//直播间

//APP下载
Route::get('/download', ['name' => 'download', 'uses' => 'PageController@download']);
Route::get('/download/qr.png', ['name' => 'downloadQR', 'uses' => 'PageController@downloadQR']);
Route::get('/contact/qr.png', ['name' => 'contactQR', 'uses' => 'PageController@contactQR']);

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
Route::get('/activity/{action}', 'ActivityController@activity')->name('activity_detail');
//主播招募设置接口
Route::get('/anchor/join', ['name' => 'anchor_join', 'uses' => 'IndexController@anchor_join']);

//活动路由页面    路由名称约定 /activitydetail/活动名
Route::get('/activityurl/{action}', ['name' => 'act', 'uses' => 'ActivityController@activityUrl']);
//活动详情整合   1. /nac/27, 2. /activity/cde + /CharmStar, 3. /activity/cde + /paihang
Route::get('/act/detail', ['name' => 'act', 'uses' => 'ActivityController@detailtype']);

// PageController 招募页面
Route::get('/join', ['name' => 'join', 'uses' => 'PageController@join']);
Route::get('/cooperation', ['name' => 'join', 'uses' => 'PageController@cooperation']);

//排行榜接口
Route::get('/rank_data', ['name' => 'rank_data', 'uses' => 'RankController@rankData']);
// 关于 帮助 投诉
Route::get('/about/{act}', ['uses' => 'PageController@index']);
//首页帮助sort分类 num获取条数
Route::get('/help/{sort}/{num}', ['uses' => 'IndexController@getHelp'])->where('sort', '[0-9]+')->where('num', '[0-9]+');
// 活动详情页面
Route::get('/nac/{id}', ['name' => 'ac_info', 'uses' => 'ActivityController@info']);
Route::match(['GET', 'POST'], '/majax/{action}', ['name' => 'majax', 'uses' => 'MemberController@ajax']);

Route::match(['POST', 'GET'], '/indexinfo', ['name' => 'indexinfo', 'uses' => 'IndexController@getIndexInfo']);
//代理
Route::get('/extend/{url}', ['name' => 'business_extend', 'uses' => 'BusinessController@extend']);
//代理
Route::get('/dal/{url}', ['name' => 'business_dal', 'uses' => 'BusinessController@extend']);
Route::get('/CharmStar', ['name' => 'charmstar', 'uses' => 'ActivityController@charmstar']);


// 开放平台
Route::get('/api/downrtmp', 'ApiController@getDownRtmp')->name('downrtmp');

// reg
Route::post('/reg/{scode?}', 'ApiController@reg')->name('api_reg');
// reg suggest nickname
Route::get('/reg/nickname', 'RegController@nickname')->name('reg_nickname');
// PageController
Route::get('/search', ['name' => 'search', 'uses' => 'PageController@search']);


Route::get('/find', 'ApiController@searchAnchor')->name('find');

// 验证是否登录
Route::group(['middleware' => ['login_auth']], function () {


// PageController 招募页面
    Route::get('/join', ['name' => 'join', 'uses' => 'PageController@join']);
    Route::get('/cooperation', ['name' => 'join', 'uses' => 'PageController@cooperation']);
//邀请注册数据临时处理过程
    Route::get('/interface', ['name' => 'invitation', 'uses' => 'ApiController@Invitation']);

//活动送礼接口
    Route::get('/activity', ['name' => 'activity', 'uses' => 'ApiController@Activity']);


//魅力之星活动排行榜
    Route::get('/CharmStar', ['name' => 'charmstar', 'uses' => 'ActivityController@charmstar']);

//列举最近20个充值的用户
    Route::get('/activityShow', 'ApiController@getLastChargeUser')->name('activityShow');

//下载扩充
    Route::get('/download/[{filename:.+}]', ['name' => 'download', 'uses' => 'ApiController@download']);

//获取桌面图标
    Route::get('/shorturl', 'ApiController@shortUrl')->name('shorturl');

//图片静态化
    Route::get('/convertstaticimg', 'ApiController@imageStatic')->name('imagestatic');

    // 用户中心 vip 贵族体系
    Route::get('/member/vip', ['name' => 'member_vip', 'uses' => 'MemberController@vip']);
    // 用户中心 viplist 贵族体系
    Route::get('/member/viplist', ['name' => 'member_viplist', 'uses' => 'MemberController@vip_list']);


    // 用户中心 邀请注册
    Route::get('/member/invite', ['name' => 'member_invite', 'uses' => 'MemberController@invite']);
    // 用户中心 我的关注
    Route::get('/member/attention', ['name' => 'member_attention', 'uses' => 'MemberController@attention']);


    //删除一对多
    Route::get('/member/delRoomOne2Many', ['name' => 'member_roomDelOne2Many', 'uses' => 'MemberController@delRoomOne2Many']);
    //一对多记录详情-购买用户
    Route::get('/member/getBuyOneToMore', ['uses' => 'MemberController@getBuyOneToMore']);
    //pc创建一对多
    Route::post('/member/roomOneToMore', ['name' => 'roomOneToMore', 'uses' => 'MemberController@roomOneToMore']);
    //pc一对多补票
    Route::post('/member/makeUpOneToMore', ['name' => 'makeUpOneToMore', 'uses' => 'MemberController@makeUpOneToMore']);
    //pc删除一对多
    Route::post('/member/delRoomOne2Many', ['name' => 'member_roomDelOne2Many', 'uses' => 'MemberController@delRoomOne2Many']);
    //pc端添加一第一
    Route::post('/member/roomSetDuration', ['name' => 'member_roomSetDuration', 'uses' => 'MemberController@roomSetDuration']);
    //pc端预约一对一
    Route::post('/member/doReservation', ['name' => 'member_doReservation', 'uses' => 'MemberController@doReservation']);
    //pc端修改房间时长
    Route::get('/member/roomUpdateDuration', ['name' => 'member_roomUpdateDuration', 'uses' => 'MemberController@roomUpdateDuration']);
    //pc端删除一对一
    Route::post('/member/delRoomDuration', ['name' => 'member_roomUpdateDuration', 'uses' => 'MemberController@delRoomDuration']);


    // 用户中心 礼物统计
    Route::get('/member/income', ['name' => 'member_count', 'uses' => 'MemberController@income']);
    Route::get('/member/gift', ['name' => 'member_gift', 'uses' => 'MemberController@gift']);

    // 用户中心 房间设置
    Route::match(['POST', 'GET'], '/member/roomset', ['name' => 'member_roomset', 'uses' => 'MemberController@roomset']);

    // 用户中心 房间设置
    Route::match(['POST', 'GET'], '/member/roomSetTimecost', ['name' => 'member_roomset', 'uses' => 'MemberController@roomSetTimecost']);

    // 用户中心 代理数据
    Route::match(['POST', 'GET'], '/member/agents', ['name' => 'member_agents', 'uses' => 'MemberController@agents']);


    //隐身功能接口
    Route::get('/member/hidden/{status}', ['name' => 'hidden', 'uses' => 'MemberController@hidden']);


    //私信接口  v2版本中去掉了
//    Route::match(['POST', 'GET'], '/letter', ['name' => 'letter', 'uses' => 'ApiController@Letter']);

    //获取余额接口
    Route::get('/balance', ['name' => 'balance', 'uses' => 'ApiController@Balance']);



    // 房间管理员
    Route::get('/member/roomadmin', ['name' => 'roomadmin', 'uses' => 'MemberController@roomadmin']);
    Route::post('/member/roomadmindelete', ['name' => 'roomadmin', 'uses' => 'MemberController@roomadmindelete']);


    // 开通贵族
    Route::post('/openvip', ['name' => 'shop_openvip', 'uses' => 'MemberController@buyVip']);

    // 贵族根据id获取贵族信息
    Route::get('/getgroup', ['name' => 'shop_getgroup', 'uses' => 'ShopController@getgroup']);

    // 主播空间
    Route::get('space', 'SpaceController@index')->name('space');


    //Route::match(['POST', 'GET'], '/verfiyName', ['uses' => 'IndexController@checkUniqueName']);
    Route::match(['POST', 'GET'], '/setinroomstat', ['name' => 'setinroomstat', 'uses' => 'IndexController@setInRoomStat']);


    Route::get('/cliget/{act}', ['uses' => 'IndexController@cliGetRes']);

    // 获取用户有多少钱
    Route::post('/getmoney', ['name' => 'shop_getmoney', 'uses' => 'MemberController@getmoney']);
    // 用户领取坐骑
    Route::post('/getvipmount', ['name' => 'shop_getvipmount', 'uses' => 'MemberController@getVipMount']);

    // 用户中心 预约房间设置


    // 用户中心 密码房间设置
    Route::post('/member/roomSetPwd', ['name' => 'member_roomSetPwd', 'uses' => 'MemberController@roomSetPwd']);
    // 用户中心 体现
    Route::post('/member/addwithdraw', ['name' => 'member_addwithdraw', 'uses' => 'MemberController@addwithdraw']);

    // 密码房间
    Route::post('/checkroompwd', ['name' => 'checkroompwd', 'uses' => 'MemberController@checkroompwd']);


    //关注用户接口
    Route::any('/focus', ['name' => 'focus', 'uses' => 'ApiController@Follow']);
    //获取用户信息
//    Route::get('/getuser/{id:\d+}', ['name' => 'getuser', 'uses' => 'ApiController@getUserByDes']);
    Route::get('/getuser/{uid}', ['name' => 'getuser', 'uses' => 'ApiController@getUserByDes'])->where('uid', '[0-9]+');

    //邮箱验证
    Route::match(['GET', 'POST'], 'sendVerifyMail', ['name' => 'sendVerifyMail', 'uses' => 'PasswordController@sendVerifyMail']);

    // 关键字屏蔽
    Route::get('/kw', ['name' => 'json_kw', 'uses' => 'ApiController@kw']);

    //对换
    Route::post('plat_exchange', ['name' => 'plat_exchange', 'uses' => 'ApiController@platExchange']);

    //获取时长打折信处
    Route::get('/getTimeCountRoomDiscountInfo', ['name' => 'roomdiscountinfo', 'uses' => 'ApiController@getTimeCountRoomDiscountInfo']);

    Route::get('/ajaxProxy', ['name' => 'ajaxProxy', 'uses' => 'ApiController@ajaxProxy']);

    /* Socket相關 */
    Route::prefix('socket')->group(function () {
        Route::get('proxy_list', 'SocketController@proxyList');
    });
});

// 充值类 TODO 登录验证
// 验证是否登录
Route::group(['prefix' => 'charge', 'middleware' => ['charge', 'login_auth']], function () {
    Route::match(['POST', 'GET'], 'pay', ['name' => 'charge_pay', 'uses' => 'ChargeController@pay']);
    Route::match(['POST', 'GET'], '/order', ['name' => 'charge_order', 'uses' => 'ChargeController@order']);
    Route::match(['POST', 'GET'], 'checkCharge', ['name' => 'check_charge', 'uses' => 'ChargeController@checkCharge']);
    Route::match(['POST', 'GET'], '/order2', ['name' => 'charge_order2', 'uses' => 'ChargeController@order2']);
    Route::match(['POST', 'GET'], '/pay2', ['name' => 'charge_pay2', 'uses' => 'ChargeController@pay2']);
    Route::match(['POST', 'GET'], '/translate', ['name' => 'translate', 'uses' => 'ChargeController@translate']);

    /* One Pay */
    Route::post('/onepay/notify', 'OnePayController@notify');
});

Route::post('charge/chongti', 'ChargeController@chongti')->name('chongti');
//通知
Route::group(['prefix' => 'charge', 'middleware' => ['charge']], function () {
    Route::match(['POST', 'GET'], 'notice2', ['name' => 'notice2', 'uses' => 'ChargeController@notice2']);
    Route::match(['POST', 'GET'], 'notice/{pay_type?}/{one_pay_token?}', ['name' => 'charge_notice', 'uses' => 'ChargeController@notice'])->name('charge_notice');
    Route::match(['POST', 'GET'], 'checkKeepVip', ['name' => 'checkKeepVip', 'uses' => 'ChargeController@checkKeepVip']);
    Route::match(['POST', 'GET'], 'callFailOrder', ['name' => 'callFailOrder', 'uses' => 'ChargeController@callFailOrder']);
    Route::post('moniCharge', ['name' => 'charge', 'uses' => 'ChargeController@moniCharge']);
    Route::match(['POST', 'GET'], 'moniHandler', ['name' => 'moniHandler', 'uses' => 'ChargeController@moniHandler']);
    Route::post('del', ['name' => 'charge', 'uses' => 'ChargeController@del']);
});

//连通测试
Route::get('/ping', ['name' => 'ping', 'uses' => 'ApiController@ping']);

Route::post('login', 'LoginController@login')->name('login');
Route::any('/logout', 'LoginController@logout');

Route::match(['POST', 'GET'], '/get_lcertificate', ['name' => 'api_agents', 'uses' => 'ApiController@get_lcertificate']);

// 用户flash段调取sid的
Route::get('/loadsid', ['name' => 'loadsid', 'uses' => 'ApiController@loadSid']);

Route::get('/islogin', ['name' => 'islogin', 'uses' => 'LoginController@isLogin']);

Route::get('/oort2bunny', ['name' => 'guanggao', 'uses' => 'AdsController@getAd']);//广告接口


//Route::match(['POST', 'GET'], '/pay/g2p', ['name' => 'pay_g2p', 'uses' => 'Pay\PayController@index']);
Route::match(['POST', 'GET'], 'log', ['name' => 'getss', 'uses' => 'ApiController@getLog']);

//古都通知接口
Route::post('v2pay/inner', 'ChargeController@noticeGD')->name('gd_notice');
Route::post('gd_test', 'ChargeController@testNoticeGD')->name('gd_test');

//app探索页
Route::get('appMarket', ['name' => 'm_appmarket', 'uses' => 'Mobile\MobileController@appMarket']);

//杏吧兑换exchange
Route::get('exchange', ['name' => 'm_exchange', 'uses' => 'ChargeController@exchange']);

//登入公告
Route::get('loginmsg', ['name' => 'loginmsg', 'uses' => 'Mobile\MobileController@loginmsg']);

// 遊戲中心
Route::prefix('game')->middleware(['login_auth'])->group(function () {
	Route::post('entry','GameController@entry');
	Route::post('deposit','GameController@deposit');
    Route::get('game_list','GameController@gameList');
});

Route::prefix('v2')->namespace('v2')->group(function () {
    Route::get('captcha/{cKey?}', 'CaptchaController@index');
});

/* 守護功能 */
Route::prefix('guardian')->group(function () {
    /* 取得權限 */
    Route::get('get_setting', 'GuardianController@getSetting');

    Route::middleware(['login_auth'])->group(function () {
        /* 我的守護資訊 */
        Route::get('my_info', 'GuardianController@myInfo');

        /* 開通紀錄 */
        Route::get('history', 'GuardianController@history');

        /*开通守护*/
        Route::post('buy', 'GuardianController@buy');

        /* 取得使用者消費紀錄 */
        Route::get('history', 'GuardianController@history');
    });
});

Route::any('omey/v2/check', 'OmeyController@v2Check');
Route::any('omey/v2/diamondGet', 'OmeyController@v2DiamondGet');
Route::any('omey/v2/diamondExpend', 'OmeyController@v2DiamondExpend');
Route::group(['middleware' => 'login_auth'], function () {
    Route::any('omey/v2/{act?}', 'OmeyController@v2');
});
