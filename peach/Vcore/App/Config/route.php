<?php
if (!defined('BASEDIR')) {
    exit('File not found');
}
$openWeb=$app->make('config')->get('config.OPEN_WEB');
$app->get('/captcha', ['as' => 'captcha', 'uses' => 'App\Controller\BaseController@captcha']);
// 所有路由都在这里配置
if ($openWeb){
/** 代理改造，开放游客 */
    //rtmp地址
    $app->get('/room/rtmp/{rid:\d+}',['as'=>'room_rtmp','uses'=>'App\Controller\RoomController@getRTMP']);
    /****** 合作平台接口 ******/
    $app->get('/recvSskey',['as'=>'recvSskey','uses'=>'App\Controller\ApiController@platform']);
    //直播间
    $app->get('/{rid:\d+}[/{h5:h5|h5hls}]',['as'=>'room','uses'=>'App\Controller\RoomController@index']);
    //APP下载
    $app->get('/download',['as'=>'download','uses'=>'App\Controller\PageController@download']);

    // 首页房间数据json
    $app->get('/videoList', ['as' => 'index_videoList', 'uses' => 'App\Controller\IndexController@videoList']);
    //首页
    $app->get('/', ['as' => 'default', 'uses' => 'App\Controller\IndexController@indexAction']);
    // 获取主播房间内的礼物清单
    $app->get('/rank_list_gift', ['as' => 'json_rank_list_gift', 'uses' => 'App\Controller\ApiController@rankListGift']);
    // 获取主播房间内的礼物排行榜
    $app->get('/rank_list_gift_week', ['as' => 'json_rank_list_gift_week', 'uses' => 'App\Controller\ApiController@rankListGiftWeek']);
    $app->get('/get_head_image', ['as' => 'get_head_image', 'uses' => 'App\Controller\ApiController@getUserHeadImage']);
    // 目前房间内flash使用的获取商品的接口
    $app->get('/goods', ['as' => 'json_goods', 'uses' => 'App\Controller\ApiController@goods']);
    // 关键字屏蔽
    $app->get('/kw', ['as' => 'json_kw', 'uses' => 'App\Controller\ApiController@kw']);
    // 贵族详情页面
    $app->get('/noble', ['as'=>'noble','uses' => 'App\Controller\PageController@noble']);
    // 商城页面
    $app->get('/shop', ['as' => 'shop_index', 'uses' => 'App\Controller\ShopController@index']);
    //活动页面
    $app->get('/act', ['as' => 'act', 'uses' => 'App\Controller\ActivityController@index']);
    //活动详情页面
    $app->get('/activity/{action}', ['as' => 'activity_detail', 'uses' => 'App\Controller\ActivityController@activity']);
    // PageController 招募页面
    $app->get('/join', ['as' => 'join', 'uses' => 'App\Controller\PageController@join']);
    $app->get('/cooperation', ['as' => 'join', 'uses' => 'App\Controller\PageController@cooperation']);
    // 排行榜页面
    $app->get('/ranking', ['as' => 'rank_index', 'uses' => 'App\Controller\RankController@index']);
    //排行榜接口
    $app->get('/rank_data', ['as' => 'rank_data', 'uses' => 'App\Controller\RankController@rankData']);
    // 关于 帮助 投诉
    $app->get('/about/{act:[a-z_]+}', ['uses' => 'App\Controller\PageController@index']);
    // 活动详情页面
    $app->get('/nac/{id:\d+}', ['as' => 'ac_info', 'uses' => 'App\Controller\ActivityController@info']);
    $app->addRoute(['GET', 'POST'], '/majax/{action}', ['as' => 'majax', 'uses' => 'App\Controller\MemberController@ajax']);

    $app->addRoute(['POST', 'GET'], '/indexinfo', ['as'=>'indexinfo','uses' => 'App\Controller\IndexController@getIndexInfo']);
    //代理
    $app->get('/extend[/{url:.+}]', ['as' => 'business_extend', 'uses' => 'App\Controller\BusinessController@extend']);
    $app->get('/CharmStar', ['as' => 'charmstar', 'uses' => 'App\Controller\ActivityController@charmstar']);

    $app->get('/getgroupall', ['as' => 'shop_getgroupall', 'uses' => 'App\Controller\ShopController@getGroupAll']);

    $app->post('/reg',           ['as' => 'api_reg', 'uses' => 'App\Controller\ApiController@reg']);

// PageController
    $app->get('/search', ['as' => 'search', 'uses' => 'App\Controller\PageController@search']);

    //rtmp地址
    $app->get('/m/room/rtmp/{rid:\d+}',['as'=>'m_room_rtmp','uses'=>'App\Controller\RoomController@getRTMP']);

    $app->get('/find', ['as' => 'find', 'uses' => 'App\Controller\ApiController@searchAnchor']);
}
// 验证是否登录
$app->routeDecorator(['decorator' => ['App\Controller\BaseController@notLogin']], function () use ($app,$openWeb) {
    $openWeb or $app->addRoute(['GET', 'POST'], '/', ['as' => 'default', 'uses' => 'App\Controller\IndexController@indexAction']);
    // 首页房间数据json
    $openWeb or $app->get('/videoList', ['as' => 'index_videoList', 'uses' => 'App\Controller\IndexController@videoList']);
// 任务api
    $app->get('/task', ['as' => 'task_index', 'uses' => 'App\Controller\TaskController@index']);
// 任务完成领取奖励api
    $app->get('/task/end/{id:\d+}', ['uses' => 'App\Controller\TaskController@billTask']);


// 排行榜页面
   $openWeb or $app->get('/ranking', ['as' => 'rank_index', 'uses' => 'App\Controller\RankController@index']);


// PageController 招募页面
    $openWeb or $app->get('/join', ['as' => 'join', 'uses' => 'App\Controller\PageController@join']);
    $openWeb or $app->get('/cooperation', ['as' => 'join', 'uses' => 'App\Controller\PageController@cooperation']);
//邀请注册数据临时处理过程
    $app->get('/interface', ['as' => 'invitation', 'uses' => 'App\Controller\ApiController@Invitation']);

//活动送礼接口
    $app->get('/activity', ['as' => 'activity', 'uses' => 'App\Controller\ApiController@Activity']);

//活动页面
   $openWeb or $app->get('/act', ['as' => 'act', 'uses' => 'App\Controller\ActivityController@index']);
//活动详情页面
   $openWeb or $app->get('/activity/{action}', ['as' => 'activity_detail', 'uses' => 'App\Controller\ActivityController@activity']);

//魅力之星活动排行榜
    $openWeb or $app->get('/CharmStar', ['as' => 'charmstar', 'uses' => 'App\Controller\ActivityController@charmstar']);

//列举最近20个充值的用户
    $app->get('/activityShow', ['as' => 'activityShow', 'uses' => 'App\Controller\ApiController@getLastChargeUser']);

//下载扩充
    $app->get('/download/[{filename:.+}]', ['as' => 'download', 'uses' => 'App\Controller\ApiController@download']);

//获取桌面图标
    $app->get('/shorturl', ['as' => 'shorturl', 'uses' => 'App\Controller\ApiController@shortUrl']);

//roomcnt
    $app->get('/roomcnt', ['as' => 'flashcount', 'uses' => 'App\Controller\ApiController@flashCount']);

//findroomcnt
    $app->get('/findroomcnt', ['as' => 'getflashcount', 'uses' => 'App\Controller\ApiController@getFlashCount']);

//图片静态化
    $app->post('/coverUpload', ['as' => 'coverUpload', 'uses' => 'App\Controller\ApiController@coverUpload']);
//图片静态化
    $app->get('/convertstaticimg', ['as' => 'imagestatic', 'uses' => 'App\Controller\ApiController@imageStatic']);
//FIND

//更新点击数
    $app->get('/clickadd', ['as' => 'click', 'uses' => 'App\Controller\ApiController@click']);


//取回密码验证
    $app->addRoute(['GET', 'POST'], '/resetpassword[/{token:.+}]', ['as' => 'resetpassword', 'uses' => 'App\Controller\PasswordController@resetPassword']);

    $openWeb or $app->addRoute(['GET', 'POST'], '/majax/{action}', ['as' => 'majax', 'uses' => 'App\Controller\MemberController@ajax']);


//验证码，新版本移除
    //$app->get('/verfiycode', ['uses' => 'App\Controller\IndexController@captcha']);

    // 用户中心 用户基本信息
    $app->get('/member', ['as' => 'member_index', 'uses' => 'App\Controller\MemberController@index']);
    $app->get('/member/index', ['as' => 'member_index', 'uses' => 'App\Controller\MemberController@index']);
    // 用户中心消息  type 是可有可无的 必须放到最后
    $app->get('/member/msglist[/{type:\d+}]', ['as' => 'member_msglist', 'uses' => 'App\Controller\MemberController@msglist']);
    // 用户中心 vip 贵族体系
    $app->get('/member/vip', ['as' => 'member_vip', 'uses' => 'App\Controller\MemberController@vip']);
    // 用户中心 vip 贵族体系主播佣金
    $app->get('/member/commission', ['as' => 'member_commission', 'uses' => 'App\Controller\MemberController@commission']);
    // 用户中心 推广中心 V2移除
    //$app->get('/member/invite', ['as' => 'member_invite', 'uses' => 'App\Controller\MemberController@invite']);
    // 用户中心 我的关注
    $app->get('/member/attention', ['as' => 'member_attention', 'uses' => 'App\Controller\MemberController@attention']);
    // 用户中心 我的道具
    $app->get('/member/scene', ['as' => 'member_scene', 'uses' => 'App\Controller\MemberController@scene']);
    // 用户中心 充值记录
    $app->get('/member/charge', ['as' => 'member_charge', 'uses' => 'App\Controller\MemberController@charge']);
    // 用户中心 消费记录
    $app->get('/member/consumerd', ['as' => 'member_consumerd', 'uses' => 'App\Controller\MemberController@consumerd']);
    // 用户中心 密码修改
    $app->addRoute(['POST', 'GET'], '/member/password', ['as' => 'member_password', 'uses' => 'App\Controller\MemberController@password']);
    // 用户中心 我的预约
    $app->addRoute(['POST', 'GET'], '/member/myReservation', ['as' => 'member_myReservation', 'uses' => 'App\Controller\MemberController@myReservation']);
    //删除一对一
    $app->get('/member/delRoomOne2One', ['as' => 'member_roomDelOne2One', 'uses' => 'App\Controller\MemberController@delRoomOne2One']);
    //删除一对多
    $app->get('/member/delRoomOne2Many', ['as' => 'member_roomDelOne2Many', 'uses' => 'App\Controller\MemberController@delRoomOne2Many']);
    //一对多添加
    $app->get('/member/roomOneToMore', ['uses' => 'App\Controller\MemberController@roomOneToMore']);
    //一对多记录详情-购买用户
    $app->get('/member/getBuyOneToMore', ['uses' => 'App\Controller\MemberController@getBuyOneToMore']);

    // 用户中心 房间游戏
    $app->get('/member/gamelist[/{type:\d+}]', ['as' => 'member_gamelist', 'uses' => 'App\Controller\MemberController@gamelist']);
    // 用户中心 礼物统计
    $app->get('/member/count', ['as' => 'member_count', 'uses' => 'App\Controller\MemberController@count']);
    // 用户中心 直播统计
    $app->addRoute(['POST', 'GET'], '/member/live', ['as' => 'member_live', 'uses' => 'App\Controller\MemberController@live']);
    // 用户中心 主播中心
    $app->addRoute(['POST', 'GET'], '/member/anchor', ['as' => 'member_anchor', 'uses' => 'App\Controller\MemberController@anchor']);
    // 用户中心 房间设置
    $app->addRoute(['POST', 'GET'], '/member/roomset', ['as' => 'member_roomset', 'uses' => 'App\Controller\MemberController@roomset']);
    // 用户中心 房间设置
    $app->addRoute(['POST', 'GET'], '/member/withdraw', ['as' => 'member_withdraw', 'uses' => 'App\Controller\MemberController@withdraw']);
    // 用户中心 房间设置
    $app->addRoute(['POST', 'GET'], '/member/roomSetTimecost', ['as' => 'member_roomset', 'uses' => 'App\Controller\MemberController@roomSetTimecost']);
    // 用户中心 转账
    $app->addRoute(['POST', 'GET'], '/member/transfer', ['as' => 'member_transfer', 'uses' => 'App\Controller\MemberController@transfer']);

    // 用户中心 代理数据
    $app->addRoute(['POST', 'GET'], '/member/agents', ['as' => 'member_agents', 'uses' => 'App\Controller\MemberController@agents']);

    //上传相册
    $app->addRoute(['POST', 'GET'], '/fupload', ['as' => 'member_fupload', 'uses' => 'App\Controller\MemberController@flashUpload']);

    //上传头像
    $app->addRoute(['POST', 'GET'], '/upload', ['as' => 'member_upload', 'uses' => 'App\Controller\MemberController@avatarUpload']);

    //安全邮箱验证
    $app->get('/mailverific', ['as' => 'mailverific', 'uses' => 'App\Controller\PasswordController@mailVerific']);
    $app->addRoute(['GET', 'POST'], 'mailsend', ['as' => 'mailsend', 'uses' => 'App\Controller\PasswordController@mailSend']);
    $app->get('/verifymail[/{token:.+}]', ['as' => 'resetpassword', 'uses' => 'App\Controller\PasswordController@VerifySafeMail']);


    //隐身功能接口
    $app->get('/member/hidden[/{status:\d+}]', ['as' => 'hidden', 'uses' => 'App\Controller\MemberController@hidden']);

    //获取用户信息
    $openWeb or $app->get('/getuser/{id:\d+}', ['as' => 'getuser', 'uses' => 'App\Controller\ApiController@getUserByDes']);

    //获取关注用户接口
    $app->get('/getuseratten/{id:\d+}', ['as' => 'getuseratten', 'uses' => 'App\Controller\ApiController@getUserFollows']);


    //私信接口  v2版本中去掉了
//    $app->addRoute(['POST', 'GET'], '/letter', ['as' => 'letter', 'uses' => 'App\Controller\ApiController@Letter']);

    //获取余额接口
    $app->get('/balance', ['as' => 'balance', 'uses' => 'App\Controller\ApiController@Balance']);

    // 房间管理员
    $app->addRoute(['POST', 'GET'], '/member/roomadmin', ['as' => 'roomadmin', 'uses' => 'App\Controller\MemberController@roomadmin']);


    // 开通贵族
    $app->get('/openvip', ['as' => 'shop_openvip', 'uses' => 'App\Controller\MemberController@buyVip']);

    // 贵族根据id获取贵族信息
    $app->get('/getgroup', ['as' => 'shop_getgroup', 'uses' => 'App\Controller\ShopController@getgroup']);
    $openWeb or $app->get('/getgroupall', ['as' => 'shop_getgroupall', 'uses' => 'App\Controller\ShopController@getGroupAll']);

    // 商城页面
    $openWeb or $app->get('/shop', ['as' => 'shop_index', 'uses' => 'App\Controller\ShopController@index']);

    // 关于 帮助 投诉
    $openWeb or $app->get('/about/{act:[a-z_]+}', ['uses' => 'App\Controller\PageController@index']);

    // 贵族详情页面
    $openWeb or $app->get('/noble', ['as'=>'noble','uses' => 'App\Controller\PageController@noble']);

    // 活动详情页面
    $openWeb or $app->get('/nac/{id:\d+}', ['as' => 'ac_info', 'uses' => 'App\Controller\ActivityController@info']);

    // 招募页面
    $app->addRoute(['POST', 'GET'], '/business/{act:[a-z]+}', ['as' => 'business_url', 'uses' => 'App\Controller\BusinessController@index']);

    // 代理页面
    $openWeb or $app->get('/extend[/{url:.+}]', ['as' => 'business_extend', 'uses' => 'App\Controller\BusinessController@extend']);

    // 主播空间
    $app->get('/space', ['as' => 'shop_space', 'uses' => 'App\Controller\SpaceController@index']);


    // test 测试用
    $app->get('/task/bb', function () {
        return new \Symfony\Component\HttpFoundation\Response('4455566');
    });


    $openWeb or $app->addRoute(['POST', 'GET'], '/indexinfo', ['uses' => 'App\Controller\IndexController@getIndexInfo']);
    $app->addRoute(['POST', 'GET'], '/verfiyName', ['uses' => 'App\Controller\IndexController@checkUniqueName']);
    $app->addRoute(['POST', 'GET'], '/setinroomstat', ['as' => 'setinroomstat', 'uses' => 'App\Controller\IndexController@setInRoomStat']);

    $app->post('/complaints', ['uses' => 'App\Controller\IndexController@complaints']);
    $app->get('/cliget/{act}', ['uses' => 'App\Controller\IndexController@cliGetRes']);


    //mobile 移动端
    //首页
 //   $app->get('/m/index', ['as' => 'm_index', 'uses' => 'App\Controller\MobileController@index']);
    //排行
  //  $app->get('/m/rank', ['as' => 'm_rank', 'uses' => 'App\Controller\MobileController@rank']);
    //登录
  //  $app->get('/m/login', ['as' => 'm_login', 'uses' => 'App\Controller\MobileController@login']);
    //注册
  //  $app->get('/m/register', ['as' => 'm_register', 'uses' => 'App\Controller\MobileController@register']);


   // $openWeb or $app->get('/{rid:\d+}[/{h5:h5}]',['as'=>'room','uses'=>'App\Controller\RoomController@index']);
});


// 验证是否登录 json
$app->routeDecorator(['decorator' => ['App\Controller\BaseController@notLoginJson']], function () use ($app,$openWeb) {
    // 获取用户有多少钱
    $app->post('/getmoney', ['as' => 'shop_getmoney', 'uses' => 'App\Controller\MemberController@getmoney']);
    // 用户领取坐骑
    $app->post('/getvipmount', ['as' => 'shop_getvipmount', 'uses' => 'App\Controller\MemberController@getVipMount']);
    // 用户中心修改基本信息
    $app->post('/member/edituserinfo', ['as' => 'member_edituserinfo', 'uses' => 'App\Controller\MemberController@editUserInfo']);
    // 用户中心 预约房间设置
    $app->get('/member/roomSetDuration', ['as' => 'member_roomSetDuration', 'uses' => 'App\Controller\MemberController@roomSetDuration']);
    $app->get('/member/roomUpdateDuration', ['as' => 'member_roomUpdateDuration', 'uses' => 'App\Controller\MemberController@roomUpdateDuration']);
    $app->get('/member/delRoomDuration', ['as' => 'member_roomUpdateDuration', 'uses' => 'App\Controller\MemberController@delRoomDuration']);
    //一对多补票
    $app->post('/member/makeUpOneToMore', ['as'=>'makeUpOneToMore','uses' => 'App\Controller\MemberController@makeUpOneToMore']);
    // 用户中心 密码房间设置
    $app->post('/member/roomSetPwd', ['as' => 'member_roomSetPwd', 'uses' => 'App\Controller\MemberController@roomSetPwd']);
    // 用户中心 体现
    $app->post('/member/addwithdraw', ['as' => 'member_addwithdraw', 'uses' => 'App\Controller\MemberController@addwithdraw']);
    // 商城购买
    $app->post('/member/pay', ['as' => 'member_pay', 'uses' => 'App\Controller\MemberController@pay']);
// 密码房间
    $app->post('/checkroompwd', ['as' => 'checkroompwd', 'uses' => 'App\Controller\MemberController@checkroompwd']);

    $app->get('/member/doReservation', ['uses' => 'App\Controller\MemberController@doReservation']);
    $app->post('/member/domsg', ['uses' => 'App\Controller\MemberController@domsg']);
    // 用户中心 取消装备
    $app->get('/member/cancelscene', ['as' => 'member_cancelscene', 'uses' => 'App\Controller\MemberController@cancelScene']);
    //购买修改昵称
    $app->post('/member/buyModifyNickname', ['as' => 'buyModifyNickname', 'uses' => 'App\Controller\MemberController@buyModifyNickname']);
    //关注用户接口
    $app->get('/focus', ['as' => 'focus', 'uses' => 'App\Controller\ApiController@Follow']);
    //获取用户信息
    $app->get('/getuser/{id:\d+}', ['as' => 'getuser', 'uses' => 'App\Controller\ApiController@getUserByDes']);

    //邮箱验证
    $app->addRoute(['GET', 'POST'], 'sendVerifyMail', ['as' => 'sendVerifyMail', 'uses' => 'App\Controller\PasswordController@sendVerifyMail']);

    // 目前房间内flash使用的获取商品的接口
    $openWeb or $app->get('/goods', ['as' => 'json_goods', 'uses' => 'App\Controller\ApiController@goods']);
    // 关键字屏蔽
    $openWeb or $app->get('/kw', ['as' => 'json_kw', 'uses' => 'App\Controller\ApiController@kw']);
    // 获取主播房间内的礼物清单
    $openWeb or $app->get('/rank_list_gift', ['as' => 'json_rank_list_gift', 'uses' => 'App\Controller\ApiController@rankListGift']);
    // 获取主播房间内的礼物排行榜
    $openWeb or $app->get('/rank_list_gift_week', ['as' => 'json_rank_list_gift_week', 'uses' => 'App\Controller\ApiController@rankListGiftWeek']);
    $openWeb or $app->get('/get_head_image', ['as' => 'get_head_image', 'uses' => 'App\Controller\ApiController@getUserHeadImage']);

    //对换
    $app->post('/api/plat_exchange', ['as'=>'plat_exchange', 'uses'=>'App\Controller\ApiController@platExchange']);


    //平台一对多跳转测试
    $app->get('/switchone2more', ['as'=>'switchone2more', 'uses'=>'App\Controller\RoomController@switchToOne2More']);

    //获取时长打折信处
    $app->get('/getTimeCountRoomDiscountInfo', ['as'=>'roomdiscountinfo', 'uses'=>'App\Controller\ApiController@getTimeCountRoomDiscountInfo']);
    //rtmp地址
    $openWeb or $app->get('/room/rtmp/{rid:\d+}',['as'=>'room_rtmp','uses'=>'App\Controller\RoomController@getRTMP']);
    //排行榜数据
    $openWeb or $app->get('/rank_data', ['as' => 'rank_data', 'uses' => 'App\Controller\RankController@rankData']);

    $app->get('/ajaxProxy', ['as' => 'ajaxProxy', 'uses' => 'App\Controller\ApiController@ajaxProxy']);
});
$app->get('/m/test', ['as' => 'm_testasds', 'uses' => 'App\Controller\Mobile\MobileController@test']);

//rtmp地址
$app->addRoute(['POST', 'GET'], '/test_room/rtmp/{rid:\d+}',['as'=>'room_rtmp','uses'=>'App\Controller\RoomController@get']);

// 充值类 TODO 登录验证
$app->addRoute(['POST', 'GET'], '/charge/{action}', ['as' => 'charge', 'uses' => 'App\Controller\ChargeController@index']);

//连通测试
$app->get('/ping', ['as' => 'ping', 'uses' => 'App\Controller\ApiController@ping']);

//登录页面
$app->get('/passport', ['as' => 'passport', 'uses' => 'App\Controller\LoginController@passport']);
$app->addRoute(['POST', 'GET'], '/login', ['as' => 'login', 'uses' => 'App\Controller\LoginController@login']);
$app->get('/synlogin', ['as' => 'synlogin', 'uses' => 'App\Controller\LoginController@synLogin']);
$app->get('/reload', ['as' => 'reload', 'uses' => 'App\Controller\LoginController@reloadLogin']);
$app->addRoute(['POST', 'GET'], '/logout', ['as' => 'logout', 'uses' => 'App\Controller\LoginController@logout']);
//$app->addRoute(['POST', 'GET'], '/peachReg', ['as' => 'peachReg', 'uses' => 'App\Controller\LoginController@mitaoReg']);
$app->addRoute(['POST', 'GET'], '/api/register', ['as' => 'api_register', 'uses' => 'App\Controller\ApiController@register']);
$app->addRoute(['POST', 'GET'], '/api/register_agents', ['as' => 'api_agents', 'uses' => 'App\Controller\ApiController@registerAgents']);


$app->addRoute(['POST', 'GET'], '/get_lcertificate', ['as' => 'api_agents', 'uses' => 'App\Controller\ApiController@get_lcertificate']);

// 用户flash段调取sid的
$app->get('/loadsid', ['as' => 'loadsid', 'uses' => 'App\Controller\ApiController@loadSid']);

// 找回密码
$app->get('/getpwd', ['as' => 'shop_getpwd', 'uses' => 'App\Controller\PasswordController@getpwd']);
// 找回密码
$app->post('/getpwdsuccess', ['as' => 'shop_getpwdsuccess', 'uses' => 'App\Controller\PasswordController@getPwdSuccess']);
// 找回密码
$app->get('/islogin', ['as' => 'islogin', 'uses' => 'App\Controller\LoginController@isLogin']);
//票据
$app->get('/get_lcertificate', ['as' => 'islogin', 'uses' => 'App\Controller\ApiController@get_lcertificate']);

//==================================================================================
/**
 * 移动端相关路由
 **/

//首页
$app->get('/m/index', ['as' => 'm_index', 'uses' => 'App\Controller\Mobile\MobileController@index']);
//排行
$app->get('/m/rank', ['as' => 'm_rank', 'uses' => 'App\Controller\Mobile\MobileController@rank']);
//登录
$app->post('/m/login', ['as' => 'm_login', 'uses' => 'App\Controller\Mobile\MobileController@login']);
//登录验证码
$app->get('/m/login/captcha', ['as' => 'm_login', 'uses' => 'App\Controller\Mobile\MobileController@loginCaptcha']);
//注册
$app->get('/m/register', ['as' => 'm_register', 'uses' => 'App\Controller\Mobile\MobileController@register']);

//轮播图获取
$app->get('/m/sliderlist', ['as' => 'm_sliderlist', 'uses' => 'App\Controller\Mobile\MobileController@sliderList']);

//活动列表
$app->get('/m/activitylist', ['as' => 'm_activitylist', 'uses' => 'App\Controller\Mobile\MobileController@activityList']);

//活动详情
$app->get('/m/activitydetail/{id}', ['as' => 'm_activitydetail', 'uses' => 'App\Controller\Mobile\MobileController@activityDetail']);

//主播列表
$app->get('/m/video/list/{type}', ['as' => 'm_videolist', 'uses' => 'App\Controller\Mobile\MobileController@videoList']);
//app版本ｈ
$app->get('/m/app/version', ['as' => 'm_app_ver', 'uses' => 'App\Controller\Mobile\MobileController@appVersion']);
$app->get('/m/app/versionIOS', ['as' => 'm_app_ver_ios', 'uses' => 'App\Controller\Mobile\MobileController@appVersionIOS']);
$app->post('/m/app/version', ['as' => 'm_app_ver', 'uses' => 'App\Controller\Mobile\MobileController@appVersion']);
$app->post('/m/app/versionIOS', ['as' => 'm_app_ver_ios', 'uses' => 'App\Controller\Mobile\MobileController@appVersionIOS']);
/** 获取配置 */
$app->get('/m/conf',['as'=>'m_conf', 'uses'=>'App\Controller\Mobile\RoomController@getConf']);
$app->get('/m/room/conf',['as'=>'m_room_conf', 'uses'=>'App\Controller\Mobile\RoomController@getRoomConf']);
$app->get('/m/room/{rid}/checkAccess', ['as' => 'm_room_checkAccess', 'uses' => 'App\Controller\Mobile\RoomController@getRoomAccess']);
//移动端登录验证
$app->routeDecorator(['decorator' => ['App\Controller\Mobile\MobileController@notLoginJson']], function () use ($app,$openWeb) {
//    $app->get('/m/logintest', ['as' => 'm_logintest', 'uses' => 'App\Controller\Mobile\MobileController@logintest']);

    //关注列表
    $app->get('/m/user/following', ['as' => 'm_userfollowing', 'uses' => 'App\Controller\Mobile\MobileController@userFollowing']);
    //用户信息
    $app->get('/m/user/info', ['as' => 'm_userinfo', 'uses' => 'App\Controller\Mobile\MobileController@userInfo']);
    //用户特权
    $app->get('/m/user/privilege', ['as' => 'm_userprivilege', 'uses' => 'App\Controller\Mobile\MobileController@userPrivilege']);
    //座驾列表
    $app->get('/m/user/mount/list', ['as' => 'm_usermountlist', 'uses' => 'App\Controller\Mobile\MobileController@mountList']);
    $app->post('/m/user/mount/{gid}', ['as' => 'm_usermount', 'uses' => 'App\Controller\Mobile\MobileController@mount']);
    $app->post('/m/user/unmount/{gid}', ['as' => 'm_userunmount', 'uses' => 'App\Controller\Mobile\MobileController@unmount']);
    //关注
    $app->addRoute(['POST', 'GET'], '/m/follow', ['as' => 'm_follow', 'uses' => 'App\Controller\Mobile\MobileController@follow']);
    //预约列表 type=1 一对一，type=2 一对多,type=3 所有
    $app->get('/m/room/reservation/{type}', ['as' => 'm_userroomreservation', 'uses' => 'App\Controller\Mobile\RoomController@listReservation']);
    //隐身
    $app->get('/m/user/stealth[/{status:\d+}]', ['as' => 'm_stealth', 'uses' => 'App\Controller\Mobile\MobileController@stealth']);
    //购买一对一
    $app->post('/m/room/buyOneToOne', ['as' => 'm_buyOneToOne', 'uses' => 'App\Controller\Mobile\RoomController@buyOneToOne']);
    //购买一对多
//    $app->post('/m/room/buyOneToMany', ['as' => 'm_buyOneToOne', 'uses' => 'App\Controller\Mobile\RoomController@buyOneToMany']);
    $app->post('/m/room/buyOneToMany', ['as' => 'm_buyOneToOne', 'uses' => 'App\Controller\Mobile\RoomController@makeUpOneToMore']);
    /** 检查密码房密码 */
    $app->post('/m/room/checkPwd', ['as' => 'm_room_checkpwd', 'uses' => 'App\Controller\Mobile\RoomController@checkPwd']);
    /** 获取RTMP地址 */
    $openWeb or $app->get('/m/room/rtmp/{rid:\d+}',['as'=>'m_room_rtmp','uses'=>'App\Controller\RoomController@getRTMP']);
});
$app->get('/m/find', ['as' => 'm_find', 'uses' => 'App\Controller\Mobile\MobileController@searchAnchor']);
$app->addRoute(['POST', 'GET'], '/m/pay/{action}', ['as' => 'm_pay', 'uses' => 'App\Controller\Mobile\PaymentController@action']);
//统计接口
$app->addRoute(['POST', 'GET'], '/m/statistic', ['as' => 'm_statistic', 'uses' => 'App\Controller\Mobile\MobileController@statistic']);

$app->get('/oort2bunny',['as'=>'guanggao','uses'=>'App\Controller\AdsController@getAd']);//广告接口
$app->post('/send_crash',['as'=>'send_crash','uses'=>'App\Controller\Mobile\MobileController@saveCrash']);//app报错接口


//
$app->addRoute(['POST', 'GET'], '/return', function () {
    return new \Symfony\Component\HttpFoundation\Response('4455566');
});

$app->addRoute(['POST', 'GET'], '/pay/g2p',['as'=>'pay_g2p','uses'=>'App\Controller\Pay\PayController@index']);
$app->addRoute(['POST', 'GET'], '/pay/{action}', ['as'=>'pay_notify','uses'=>'App\Controller\Pay\PayController@notify']);
//$app->addRoute(['POST', 'GET'], '/pay/{action}', function(){
//    echo json_encode($_POST);
//    file_put_contents(BASEDIR.'/app/logs/inner.log',json_encode($_POST));
//});
$app->addRoute(['POST', 'GET'], '/getss', ['as'=>'getss','uses'=>'App\Controller\ApiController@getLog']);
