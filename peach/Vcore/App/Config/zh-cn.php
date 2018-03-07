<?php
/**
 * Created by PhpStorm.
 * User: nicholas
 * Date: 2016/9/22
 * Time: 12:52
 */
if (!defined('BASEDIR')) {
    exit('File not found');
}
//define('TEST_DEF',"常量");
return [
    'TEST'=>'你好世界',
    'ACTIVITY' => [

    ],
    'API' => [
        'INVALID_USER' => '无效用户',
        'FOLLOW' => [
            'YES' => '已关注',
            'NO' => '未关注',
            'LIMIT' => '您已经关注了1000人了，已达上限，请清理一下后再关注其他人吧',
            'SUCCESS' => '关注成功',
            'REPEAT' => '请勿重复关注',
            'CANCEL' => [
                'YES' => '取消关注成功',
                'NO' => '取消关注失败'
            ]
        ],
        'LETTER' => [
            'NOUSER' => '该用户不存在',
            'MESSAGE_LIMIT' => '内容不能为空且字符长度限制200字符以内',
            'MESSAGE_PERMIT' => '财富等级达到二富才能发送私信哦，请先去给心爱的主播送礼物提升财富等级吧.',
            'DAILY_LIMIT' => '本日发送私信数量已达上限，请明天再试！',
            'SEND_FAIL' => '发送失败',
            'SUCCESS' => '发送成功！',
        ],
        'ACTIVITY' => [
            'FAIL' => [
                'ACTIVITY_ENDED' => '活动已经停止！',
                'INVALID_SUBMIT' => '非法提交！',
                'INVALID_USER' => '充值用户不能为空',
                'INVALID_AMOUNT' => '充值金额不能为空'
            ],
            'SUCCESS' => [
                '1' => '恭喜您获得首次充值【100元以上】送【100钻石】+【3888钻猪猪快跑】',
                '2' => '恭喜您获得充值【老用户回馈 恩客专享】4月1日前消费满【10000RMB】的用户, 一次性充值【5000元及以上】送【20000钻布加迪座驾】',
                '3' => '恭喜您获得充值【800元~999元】送688钻石+【白尊】+【3888钻战斗机座驾】',
                '4' => '恭喜您获得充值【1000元~9999元】送888钻石+【白尊】+【5888钻航母坐骑】',
                '5' => '恭喜您获得充值【10000元及以上】送1888钻石+【墨尊】+【8888钻天马坐骑】',
            ]
        ],
        'LOTTERY' => [
            'NO_BALANCE' => '抱歉，您无法抽奖。只有新注册用户才可参加该活动，或是您的抽奖次数已经用完',
            'OUT_OF_STOCK' => '该奖品已经抽完',
            'WIN' => '恭喜中奖！',
            'ENDED' => '活动已经关闭！',
        ],
        'FLASH_COUNT' => [
            'INVALID_PARAMETER' => '传入参数有问题'
        ],
        'CLICK_AD' => [
            'SUCCESS' => '成功',
            'FAIL' => '失败'
        ]

    ],
    'BASE' => [
        'NOT_LOGIN_JSON' => [
            'FAIL' => [
                'NOT_LOGIN' => '亲，请先登录哦！',
            ]
        ],
        'EDIT_USER_INFO' => [
            'FAIL' => [
                'PARAM' => '信息错误',
                'NICKNAME1' => '注册昵称不能使用/:;\空格,换行等符号！(2-8位的昵称)',
                'NICKNAME2' => '昵称中含有非法字符，请修改后再提交!',
                'NICKNAME_SAME' => '昵称重复！',
                'NICKNAME_NOT_MODIFY' => '你已经不能修改昵称了！',
            ],
            'SUCCESS' => '更新成功!',
        ],
    ],
    'CHARGE'=>[
        'CODE_MSG'=>[
            -1 => '未知',
            0 => '已接受',
            1 => '处理中',
            2 => '处理成功',
            3 => '处理失败'
        ],
        'ORDER'=>[
            'TYPE0'=>'尊敬的用户，您好，恭喜您成为今日幸运之星，请点击在线客服领取钻石，感谢您的支持与理解！',
            'TYPE1'=>[0=>'需要充值请',1=>'联系客服'],
            'ORDER'=>[
                'TITLE'=>'钻石充值',
                'CHARGE_TITLE_AMOUNT'=>'选择您要充值的金额',
                'PRICE_UNIT'=>'元',
                'CHARGE_TITLE_CHANEL'=>'选择充值通道',
                'BTN_PAY'=>'去 支 付',
            ]
        ],
        'DEL'=>[
            'NOT_OWNER'=>'这条记录不是你的!',
            'SUCCESS'=>'删除成功！',
            'UNAUTHORIZED'=>'非法操作',
        ],
        'BACK2CHARGE'=>[
            0=>' 已接受！',
            1=>' 处理中！',
            2=>' 处理成功！',
            3=>' 处理失败！',
        ],
        'PAY'=>[
            'WRONG_AMOUNT'=>'请输入正确的金额!',
            'WRONG_CHANEL'=>'请选择充值渠道!',
            'PAY'=>[
                'PAGE_EXPIRED'=>[
                    0=>'该页面已经过期',
                    1=>'请前往充值',
                ]
            ]
        ],
        'FAIL_ORDER'=>[
            'INVALID_DATA'=>'传入的数据存在问题',
            'UNAUTHORIZED'=>'非法操作！',
        ],
        'CHECK_KEEP_VIP'=>[
            'INVALID_DATA'=>'传入的数据存在问题',
        ],
        'CHECK_CHARGE'=>[
            'ORDER_NOT_EXIST'=>'该订单号不存在！',
            'SUCCESS'=>'该订单号已经成功支付,请返回会员中心的"充值记录"查看！',
            'FAIL'=>'该订单号支付已经失败,请返回会员中心的"充值记录"查看！',
            'API_FAIL'=>'充提查询接口出问题：',
            'API_DATA_ERROR'=>'充提返回数据有问题',
        ],

    ],
    'INDEX' => [
        'GET_WHITE_LIST_ARR' => [
            'SUCCESS' => [
                'SELF' => "主播自己可直播进入",
                'IN_WHITE' => "在白名单内，可以进入房间!",
            ],
            'FAIL' => [
                'NOT_ENTER_ROOM' => "您不在白名单内，无法进入房间！若想进入该房间请联系客服。"
            ]
        ],
        'REGISTER' => [
            'FAIL' => [
                'LOGINING' => "已经在登录状态中！",
                'VERFRIY_CODE_ERROR' => "验证码错误！请重新刷新",
                'MAIL' => "注册邮箱不符合格式！(5-30位的邮箱)",
                'MAIL_SAME' => "注册邮箱重复，请换一个试试！",
                'NICKNAME' => "注册昵称不能使用/:;\空格,换行等符号！(2-8位的昵称)",
                'NICKNAME_SAME' => "注册昵称重复，请换一个试试！",
                'PASSWORD_NOT_SAME' => "两次输入的密码不相同！",
                'PASSWORD' => "注册密码不符合格式！",
            ],
            'SUCCESS' => '恭喜您注册成功!',
        ],
        'CHECK_UNIQUE_NAME' => [
            'FAIL' => [
                'PARAM' => '传递的参数非法！',
                'EMAIL' => '注册邮箱不符合格式！(5-30位的邮箱)',
                'EMAIL_USED' => '该邮箱已被使用，请换一个试试！',
                'NICK_NOT_USE' => "注册昵称不能使用/:;\空格,换行等符号！(2-8位的昵称)",
                'NICK_USED' => '该昵称已被使用，请换一个试试！',
            ],
            'SUCCESS' => [
                'EMAIL' => '恭喜该邮箱可以使用。',
                'NICK' => '恭喜该昵称可以使用。',
            ],
        ],
        'GET_INDEX_INFO' => [
            'FAIL' => [
                'NOT_LOGIN' => '没有登录！',
                'NOT_EXIST_USER' => '无效的用户！',
            ]
        ],
        'COMPLAINTS' => [
            'FAIL' => [
                'NOT_LOGIN' => '还没有登录，请登录！',
                'PROCESS_SUCCESS' => '处理成功！',
                'COMPLAINT_NOT_EMPTY' => '缺少投诉内容！',
            ],
            'SUCCESS' => '处理成功!',
        ]
    ],
    'PASSWORD' => [
        'MAIL_VERIFIED' => [
            'FAIL' => [
                'VERIFIED' => '你已验证过安全邮箱!'
            ],
        ],
        'MAIL_SEND' => [
            'FAIL' => [
                'VERIFIED' => '你已验证过安全邮箱!',
                'IS_NOT_MAIL' => '安全邮箱地址格式不正确!',
                'SEND_ERROR' => '邮件发送失败!',
            ],
        ],
        'VERIFY_SAFE_MAIL' => [
            'FAIL' => [
                'LINK_EXPIRE' => '验证链接已过期!',
                'MAIL_VERIFIED' => '该用户已验证过安全邮箱!',
                'MAIL_BIND' => '对不起！该邮箱已绑定帐号！',
                'UPDATE_MAIL_ERROR' => '更新安全邮箱失败!',
            ]
        ],
        'GET_PWD_SUCCESS' => [
            'FAIL' => [
                'IS_NOT_MAIL' => '安全邮箱地址格式不正确!
                ',
                'MAIL_NOT_VERIFIED' => '该邮箱没有通过安全邮箱验证, 验证安全邮箱才能使用此功能!',
                'MAIL_SEND_ERROR' => '邮件发送失败请与客服联系！',
            ],
            'SUCCESS' => '<h2>密码找回邮件已经发送到您的安全邮箱，请查收！</h2><p>如未收到邮件请在“垃圾箱”查找或重新发送密码找回邮件。<br/>点击邮件后请及时在“个人中心”中修改密码。</p>'
        ],
        'RESET_PASSWORD' => [
            'FAIL' => [
                'LINK_NOT_USE' => '该链接无效!',
                'LINK_EXPIRE' => '该链接已过期!',
                'PASSWORD_FORMAT_ERROR' => '密码格式无效!',
                'TWO_TIMES_NOT_SAME' => '两次输入密码不一致!',
                'MODIFY_PASSWORD_ERROR' => '修改密码失败更新错误!',
            ],
            'SUCCESS' => '<h2>密码修改成功,请返回首页登录！</h2>'
        ],
        '_GET_PASSWORD_EMAIL_TEMPLATE' => '',
         'GETPWD'=>[
            //模板文件名
            'GETPWD'=>[
                'GET_PASSWORD'=>'安全邮箱找回密码',
                'YOUR_EMAIL'=> '您的邮箱：',
                'SAFE_EMAIL'=>'请输入安全邮箱地址',
                'ATTENTION'=>'注意：点击确认后，系统将会发送一封密码找回邮件到您的安全邮箱。点击邮件中的链接可完成密码找回。',
                'YES'=>'确 定'
            ]
        ],
        'GETPWDSUCCESS'=>[
             'GETPWDFAIL'=>[
                            'GETPWDFAIL'=>'密码找回失败',
                            'RETURN'=> '返 回'
                        ],
             'GETPWDSUCCESS'=>[
                                'FINDMAIL'=>'安全邮箱找回密码',
                                'RETURNINDEX'=>'返 回 首 页'
             ]

        ],
        'MAILVERIFIC'=>[
            'MAILFAIL'=>[
                         'MAILFAIL'=>'安全邮箱验证失败',
                         'ERRTIP'=>'安全错误提示！',
                         'RETURNINDEX'=>'返 回 首 页'
              ],
            'MAILVERIFIC'=>[
                        'SAFEMAIL'=>'安全邮箱验证',
                        'MAILSAFE'=>'安全邮箱：',
                        'PLACEHOLDER'=>'请输入您的安全邮箱地址',
                        'ATTENTION'=>'注意：保存并确认安全邮箱后，系统将会发送一封验证邮件到您的安全邮箱。点击验证邮件中的链接可完成安全邮箱验证，安全邮箱一旦完成验证，将不可再被修改。',
                        'YES'=>'确 定'
            ]
        ],
        'MAILSEND'=>[
            'MAILSEND'=>[
                        'HAVESEND'=>'验证邮件已发送',
                        'SAFEMAIL'=>'安全邮箱验证',
                        'HAVESENDTOYOU'=>'验证邮件已经发送到您的邮箱，请查收！',
                        'IFFAIL'=>'如未收到邮件请在',
                        'RUBBINMAIL'=>'垃圾邮件箱',
                        'RESEND'=>'查找或重新发送验证邮件。',
                        'IFHAVECOMPLETE'=>' 如果安全邮箱验证已经完成，可',
                        'RETURNINDEX'=>'返 回 首 页',
                        'SFAEMAIL'=>'安全邮箱：',
                        'TENMINUTES'=>'10分钟后可再次修改',
                        'RESENDMAIL'=>'重新发送验证邮件',
                        'TENMINUTES-RESEND'=>'10分钟',
                        'SENDAGAIN'=>'后可再次发送'
            ]
        ],
        'VERIFYSAFEMAIL'=>[
            'MAILSUCCESS'=>[
                            'SAFEMAILSUCCESS'=>'安全邮箱验证成功',
                            'CONGRATULATION'=>'恭喜您！您的邮箱',
                            'HAVE-OK'=>'已验证成功。',
                            'RETURNINDEX'=>'返 回 首 页'
            ]
        ],
        'RESETPASSWORD'=>[
            'RESETPWD'=>[
                        'FINDPWD'=>'找回密码',
                        'RESETPWD'=>'密码重置',
                        'SETNEWPWD'=>'创建您的新密码',
                        'NEWPWD'=>'新密码：',
                        'INNEWPWD'=>'请输入新密码',
                        'SURENEWPWD'=>'确认新密码：',
                        'REINNEWPWD'=>'请再次输入新密码',
                        'YES'=>'确 定'
            ]
        ]
    ],
    'SPACE' => [
        '_GET_AJAX_ANCHOR' => [
            'NO' => '无',
            'NAME' => '名称',
            'DESCRIBE' => '描述',
            'TIME' => '时间',
        ],
        'INDEX'=>[
            'INDEX'=>[
                'SPACETITLE'=>'个人空间',
                'PICTURE'=>'相册',
                'LIVE'=>'主播',
                'LIVING'=>'我正在直播中，亲！快进入我房间吧~',
                'RESTING'=>'我正在休息中，暂未直播，但是请继续支持和关注我哦！',
                'INTOROOM'=>'进入房间',
                'NICHENG'=>'昵称：',
                'SEX'=>'性别：',
                'MAN'=>'男',
                'WOMAN'=>'女',
                'STAR'=>'星座：',
                'SECRET'=>'保密',
                'ADDRESS'=>'所在地：',
                'FIRESTAR'=>'火星',
                'SIGN'=>'签名：',
                'WRITEDOWN'=>'主人犯懒，什么都没写!',
                'STATE'=>'升级状态：',
                'LEVEL'=>'在主播的直播房间内消费可以帮助主播升级哦！',
                'STILLNEED'=>'还需要',
                'UPLEVEL'=>'升级',
                'NOTHAVE'=>'无',
                'FOCUS'=>'关注',
                'PHOTO'=>'照片',
                'PHOTOWALL'=>'的照片墙',
                'SHARE'=>'共分享了',
                'PHOTONUMBER'=>'张照片'
            ]
        ]
    ],
    'BUSINESS' => [
        'SIGN_UP' => [
            'SUCCESS' => '提交申请成功，请耐心等待1ROOM团队审核。',
            'FAIL' => [
                'NOT_LOG_IN' => '对不起，你未登录，请登录后申请主播功能！',
                'REPEAT' => '对不起，你已申请了主播功能！',
                'INCOMPLETE' => '请把资料填写完整!',
                'AUDIT_REPEAT' => '对不起，你已申请了主播功能,请等待审核！',
                'AUDIT_CANCELED' => '你之前的主播身份已被取消，现重新提交申请成功，请等待审核',
                'AUDIT_REJECTED' => '你之前的申请已被驳回，现重重新提交申请，请等待审核！',
            ]
        ],
        'INDEX' => [
            'CO' => [
                'WORK_JOIN'=>'合作加盟',
                'HOST_JOIN'=>"主播招募：运营小七",
                'HOST_QQ'=>"QQ：3351891965",
                'HOST_MAIL'=>"邮箱：3351891965@qq.com",
                'AD_TALK'=>'广告洽谈：苏女士',
                'AD_QQ'=>'QQ：3194435729',
                'AD_MAIL'=>'邮箱：3194435729@qq.com',
                'NETWORK_JOIN'=>'网盟推广：奥先生',
                'NETWORK_QQ'=>'QQ: 2913182232',
                'NETWORK_MAIL'=>'邮箱：2913182232@qq.com',
                'NETWORK_AGENT_QQ'=>'客户代理请联系QQ：2516894147',
            ],
            'JOINING'=>[
                'TITLE'=>'主播申请入驻',
            ],
            'JOINING_TOP'=>[
                'TITLE'=>'主播入驻流程',
            ],
        ],
        'EXTEND'=>[
            'EXTEND'=>[
                'TITLE'=>'extend',
            ],
        ],
        'SIGNUP'=>[
            'SIGNUP'=>[
                'HOST_APPLY'=>'主播申请',
                'WRITE_INFO'=>'填写基本资料',
                'APPLY_SUCCESS'=>'申请通过',
                'APPLY_CHECK'=>'申请审批期',
                'APPLY_JOIN'=>'申请入驻账号:',
                'NAME'=>'真实姓名:',
                'SEX'=>'性别:',
                'MAIL'=>'男',
                'FEMALE'=>'女',
                'BIRTHDAY'=>'出生日期:',
                'MOBILE_PHONE'=>'手机号码:',
                'QQ'=>'QQ号码:',
                'CREDIT'=>'银行卡卡号:',
                'BANK_NAME'=>'银行卡开户名:',
                'BANK_ADDR'=>'银行卡开户地址:',
                'SUBMIT'=>'提交申请',
            ],
        ],
    ],
    'LOGIN' => [
        'INDEX' => [
            'FAIL' => [
                'LOGIN_ALREADY' => '已经在登录状态中！',
                'CAPTCHA' => '验证码错误！请重新刷新验证码',
                'EMPTY_CREDENTIALS' => '用户名或密码不能为空！',
                'INVALID_CREDENTIALS' => '用户名或者密码错误！',
                'FORBIDDEN' => '您的账号已经被禁止登录，请联系客服！'
            ],
        ],
    ],
    'OMS' => [
        'GET_URL' => [
            'FAIL' => [
                'NOT_EXIST' => '不存在此代理商'
            ]
        ]
    ],
    'PAGE'=>[
        'NOBLE'=>[
            'TITLE'=>'贵族',
            0=>'贵族特色',
            1=>'更多贵族权限',
            'NOBLE'=>[
                'NOBLE'=>'贵族',
                'NOBLECOLOR'=>'贵族特色',
                'NOBLEROOT'=>'更多贵族权限'
            ]
        ],
        'INDEX'=>[
            'ABOUTUS'=>[
                'ABOUTUS'=>'关于我们',
                'PEFESSIONAL'=>'第一坊是首个面向全球提供精品视频才艺直播服务的专业平台。',
                'BYSELF'=>'我们通过自主研发的实时视频交互技术，让全球各地热爱视频直播的表演爱好者得以能够实时、自由、尽情地向不同地区、不同文化背景、不同兴趣爱好的用户展示自己的才艺，为其带来精彩纷呈的视听娱乐体验。',
                'TYPE'=>'第一坊提供多元化的直播表演类型，包括但不限于唱歌、演奏、舞蹈、功夫、脱口秀、竞猜等才艺展示及互动娱乐形式。',
                'STYLE'=>'表演风格多样，尺度大胆，用户可以欣赏甚至参与表演。',
                'SERVICE'=>'第一坊还会在线上线下组织超级派对、才艺比拼、明星互动等主题活动，为用户提供全方位、多元化的资讯、娱乐及增值服务。'
            ],
            'STATE'=>[
                'STATE'=>'免责条款',
                'DETAILONE'=>'1. 注册用户符合18岁以上的年龄要求，且所在国家和居住地区法律法规允许拥有成人性爱等相关性质媒体。',
                 'DETAILTWO'=>'2. 注册用户对在网站/电脑中传输及阅览成人內容的所有行为，负完全的责任。',
                 'DETAILTHREE'=>'3. 用户注册成功后，该用户的帐号和密码由用户负责保管;用户应当对该帐号进行的所有言论和行为负责。',
                 'DETAILFOUR'=>'4. 由于用户将个人密码告知他人或与他人共享注册帐户，由此导致的任何个人资料泄露及财产损失，本网站不负任何责任。',
                 'DETAILFIVE'=>'5. 本网站如因系统维护或升级而需暂停服务时，将事先公告。若因线路及非本公司控制范围外的硬件故障或其它不可抗力而导致暂停服务，于暂停服务期间造成的一切不便与损失，本网站不负任何责任。',
                 'DETAILSIX'=>'6. 任何由于黑客攻击、计算机病毒侵入或发作、因政府管制而造成的暂时性关闭等影响网络正常经营的不可抗力而造成的个人资料泄露、丢失、被盗用或被窜改等，本网站均得免责。',
                 'DETAILSEVEN'=>'7. 用户不得将其注册账号进行贩卖、租用、借用、转让、赠与等行为，如有此类行为，由此带来的损失，网站不承担任何责任。'
            ]
        ],
    ],
    'TASK' => [
        'BILL' => [
            'SUCCESS' => '领取成功！',
            'FAIL' => [
                'NOT_LOGIN' => '未登录',
                'REPEAT' => '领取失败！请查看任务是否完成或已经领取过了！'
            ]
        ]
    ],
    'MEMBER' => [
        'TITLE'=>'会员中心',
        'MENU' => [
            'INDEX' => '基本信息',
            'INVITE' => '邀请注册',
            'ATTENTION' => '我的关注',
            'SCENE' => '我的道具',
            'CHARGE' => '充值记录',
            'CONSUMERD' => '消费记录',
            'VIP' => '贵族体系',
            'PASSWORD' => '密码管理',
            'MYRESERVATION' => '我的预约',
            'ROOMSET' => '房间管理',
            'TRANSFER' => '转帐',
            'WITHDRAW' => '提现',
            'ANCHOR' => '主播中心',
            'GAMELIST' => '房间游戏',
            'GIFT' => '礼物统计',
            'COMMISSION' => '佣金统计',
            'LIVE' => '直播记录',
            'MSGLIST' => '消息',
            'AGENTS' => '代理数据',
        ],
        'AGENTS'=>[
            'TITLE'=>'代理数据',
            'SEARCH'=>'查询',
            'USERNAME'=>'代理帐号',
            'NICKNAME'=>'代理昵称',
            'MEMBERS'=>'注册人数',
            'RECHARGE_POINTS'=>'团队充值(钻石)',
            'REBATE_POINTS'=>'返点(钻石)',
            'MESSAGE'=>'本充值金额包括了【活动赠送】【充值赠送】等，与实际金额略有不同。'
        ],
        'MSG'=>[
            'MSG1'=>'系统消息',
            'MSG2'=>'私信',
            'USER_NO_EXIST'=>'用户不存在',
            'SELF'=>'不能给自己发私信',
            'CONTENT_LIMIT'=>'输入为空或者输入内容过长，字符长度请限制200以内！',
            'LV_RICH_LIMIT'=>'财富等级达到二富才能发送私信哦，请先去给心爱的主播送礼物提升财富等级吧。',
            'MAX_LIMIT'=>'本日发送私信数量已达上限，请明天再试！',
            'OK'=>'私信发送成功',
            'NO'=>'私信发送失败'

        ],
        'TRANSFER'=>[
            'TITLE'=>'转账',
            'BALANCE'=>'当前余额：',
            'UNSET_PASSWORD'=>'您还未设置交易密码，没有设置交易密码无法转账',
            'SET_PASSWORD'=>'立即设置',
            'SEARCH'=>'查询',
            'SEARCH_TIME'=>'查询时间',
            'TO'=>'至',
            'AS'=>'截至:',
            'ORDER'=>'转账单号',
            'TIME'=>'时间',
            'FROM_USER'=>'转账人账号',
            'TO_USER'=>'收款人账号',
            'TOTAL_AMOUNT'=>'转帐总额',
            'TOTAL_NUMBER'=>'共%s笔',
            'DESCRIPTION'=>'备注',
            'AMOUNT'=>'金额',
            'POINTS'=>'钻',
            'REQUIRED'=>'*为必填项',
            'SELF'=>'不能转给自己',
            'PASSWORD_ERROR'=>'交易密码错误',
            'CAPTCHA_ERROR'=>'验证码错误',
            'AMOUNT_ERROR'=>'转帐金额错误',
            'USER_NOT_EXIST'=>'该用户不存在',
            'NO_AUTHORITY'=>'该用户不存在',
            'POINTS_NOT_ENOUGH'=>'您的钻石余额不足!',
            'CONFIRM'=>'确认',
            'CONFIRM_USER'=>'确认帐号',
            'INPUT_CODE'=>'请输入验证码',
            'INPUT_PASSWORD'=>'请输入交易密码',
            'CODE'=>'验证码',
            'NEW_CODE'=>'换一换',
            'PASSWORD'=>'交易密码：',
            'OK'=>'您成功转出%s钻石',
            'NO'=>'对不起！转帐失败!',
            'MSG_CONFIRM_TRANSFER'=>'确认转账',
            'MSG_FROM_USER'=>'提示：请填写收款人',
            'MSG_TO_USER'=>'提示：收款人账号不一致',
            'MSG_EMPTY_AMOUNT'=>'提示：请填写转账金额',
            'MSG_INT_AMOUNT'=>'提示：转账金额请输入1-10000之间的数字',
        ],
        'ROOMADMIN'=>[
            'TITLE'=>'管理员设置',
            'ADMIN_SET'=>'管理员设置',
            'ROOM_SET'=>'房间设置',
            'ADMIN_COUNT'=>'管理员数量：',
            'ADMIN_MAX'=>'最大数量：',
            'NICKNAME'=>'管理员昵称',
            'ADMIN_LEVEL'=>'管理员等级',
            'LAST_LOGIN'=>'最后登录时间',
            'OPERATION'=>'操作',
            'REMOVE'=>'删 除',
            'DELETE'=>'删除成功!'
        ],
        'ROOMSET'=>[
            'TITLE'=>'房间设置',
            'ROOM_SET'=>'房间设置',
            'ADMIN_SET'=>'管理员设置',
            'ONE2ONE'=>'预约房间（一对一）',
            'START_DATE'=>'开播日期：',
            'START_TIME'=>'开播时间：',
            'DURATION'=>'时长：',
            'POINTS'=>'钻石数：',
            'OPTION'=>'选项',
            'INPUT'=>'手动输入',
            'OK'=>'确定',
            'ONE2MANY'=>'预约房间（一对多）',
            'PASSWORD_ROOM'=>'密码房间',
            'PASSWORD'=>'使用密码：',
            'OLD_PASSWORD'=>'现在的密码：',
            'ROOM_TYPE_LIST'=>'房间类型列表',
            'ROOM'=>'开播房间',
            'PRICE'=>'费用',
            'STATUS'=>'状态',
            'OPERATION'=>'操作',
            'RESERVATION_ROOM'=>'预约房间',
            'UNAVAILABLE'=>'房间已预约',
            'AVAILABLE'=>'房间未被预约',
            'EDIT'=>'修改',
            'DEL'=>'删除',
            'ON'=>'开启',
            'OFF'=>'关闭',
            'RESERVATION_MAN'=>'预约人',
            'ONETOONE'=>'一对一',
            'ONETOMANY'=>'一对多',
            'RESERVATION_USER'=>'人预约',
            'CHECKDETAIL'=>'查看详情'
        ],
        'SCENE'=>[
            'TITLE'=>'我的道具',
            'BALANCE'=>'当前余额：',
            'ONLY_ONE'=>'（只能同时装备一个道具）',
            'NAME'=>'道具',
            'DESCRIPTION'=>'说明',
            'VALID_DATE'=>'有效期',
            'OVER_DATE'=>'已过期',
            'STATUS'=>'状态',
            'OPERATION'=>'操作',
            'NO_SET'=>'不可装备',
            'IS_SET'=>'已装备',
            'UN_SET'=>'未装备',
            'TO_SET'=>'立即装备',
            'CANCEL_SET'=>'取消装备',
            'RECHARGE'=>'【前去续费】',
            'NO_PAY'=>'您目前没有购买记录',
            'BUY_NOW'=>'立即购买',
            'ERROR'=>'操作出现未知错误！',
            'FOR_ROOM'=>'该道具限房间内使用,不能装备！',
            'OK'=>'装备成功',
            'CANCEL'=>'取消成功'
        ],
        'PASSWORD'=>[
            'TITLE'=>'密码管理',
            'LOGIN_PASSWORD'=>'修改登录密码',
            'OLD_PASSWORD'=>'现在的密码：',
            'NEW_PASSWORD1'=>'设置新密码：',
            'NEW_PASSWORD2'=>'重复新密码：',
            'TRADE_PASSWORD'=>'支付密码',
            'ISSET_TRADE_PASSWORD'=>'您的支付密码已设置，如需更改，请联系客服。',
            'UNSET_TRADE_PASSWORD'=>'您的支付密码尚未设置',
            'SET_PASSWORD'=>'设置交易密码',
            'SUBMIT'=>'提 交',
            'ISSET'=>'你已设置交易密码,需要修改请联系客服进行重置!',
            'LOGIN_PASSWORD_ERROR'=>'登录密码不正确!',
            'TRADE_PASSWORD_OK'=>'交易密码设置成功',
            'TRADE_PASSWORD_NO'=>'交易密码设置失败',
            'TRADE_PASSWORD_EMPTY'=>'交易密码不能为空',
            'OLD_PASSWORD_EMPTY'=>'原始密码不能为空',
            'OLD_PASSWORD_ERROR'=>'原始密码错误',
            'PASSWORD_LIMIT'=>'请输入大于或等于6位字符串长度',
            'PASSWORD_DIFF'=>'新密码两次输入不一致!',
            'PASSWORD_SAME'=>'新密码和原密码相同!',
            'NO'=>'修改失败',
            'OK'=>'修改成功!请重新登录'
        ],
        'GIFT'=>[
                    'TITLE'=>'礼物统计',
                    'RECEIVE'=>'收到的礼物',
                    'SEND'=>'送出的礼物',
                    'NAME'=>'礼物名称',
                    'PRICE'=>'礼物单价（钻石数）',
                    'NUMBER'=>'礼物数量',
                    'AMOUNT'=>'礼物总额',
                    'TIME'=>'收礼时间',
                    'SEND_USER'=>'送礼人',
                    'RECEIVE_USER'=>'收礼人',
                    'ROOM'=>'房间',
                    'SEARCH'=>'查询',
                    'SEARCH_TIME'=>'查询时间',
                    'TO'=>'至',
                    'RECEIVE_TOTAL'=>'共收到%s个礼物，礼物总计：%s颗钻石',
                    'SEND_TOTAL'=>'共送出%s个礼物，礼物总计：%s颗钻石',
                    'BOOKING_ROOM'=>'预约房间',
                ],
        'ROOMSETDURATION'=>[
            'ERROR'=>'请求错误',
            'LIMIT'=>'手动设置的钻石数必须大于1万',
            'LIMIT_7'=>'只能设置未来七天以内',
            'LIMIT_OVERFLOW'=>'不能设置过去的时间',
            'OK'=>'添加成功',
            'EDIT'=>'修改成功',
            'DURATION'=>'你这段时间和一对一或一对多有重复的房间'
        ],
        'ROOMSETPWD'=>[
            'EMPTY'=>'密码不能为空',
            'FORMAT'=>'密码格式不对',
            'CLOSE'=>'密码关闭成功',
            'OK'=>'密码修改成功'
        ],
        'CHECKROOMPWD'=>[
            'LOGIN'=>'登陆成功',
            'ROOM_ERROR'=>'房间号错误',
            'CAPTCHA_EMPTY'=>'请输入验证码',
            'CAPTCHA_ERROR'=>'验证码错误',
            'PASSWORD_FORMAT'=>'密码格式错误',
            'PASSWORD_ERROR'=>'密码错误'
        ],
        'DORESERVATION'=>[
            'ERROR'=>'错误',
            'NOT_EXIST'=>'您预约的房间不存在',
            'ROOM_OFFLINE'=>'当前的房间已经下线了',
            'BOOKED'=>'当前的房间已经被预定了，请选择其他房间。',
            'SELF'=>'自己不能预约自己的房间',
            'POINTS_NOT_ENOUGH'=>'余额不足哦，请充值！',
            'CONFIRM'=>'您这个时间段有房间预约了，您确定要预约么',
            'OFFLINE'=>'当前的房间已经下线了，请选择其他房间。',
            'OK'=>'预定成功'
        ],
        'DOONETOMORE'=>[
            'END'=>'您预约的一对多房间已经结束',
            'HAS_ROOM'=>'你已经预约了此房间，请在预约时间段从“我的预约”进入观看。',
        ],
        'WITHDRAW'=>[
            'TITLE'=>'提现',
            'YUAN'=>'元',
            'BALANCE_POINTS'=>'可申请提款钻石：',
            'BALANCE_YUAN'=>'可申请提款钻石：',
            'AMOUNT'=>'提现金额：',
            'REGISTER'=>'申请提取',
            'SEARCH'=>'查询',
            'TO'=>'至',
            'SEARCH_TIME'=>'查询时间',
            'STATUS'=>'申请状态：',
            'ORDER'=>'申请流水号',
            'TIME'=>'申请提款时间',
            'WITHDRAW_TIME'=>'提款时间',
            'MESSAGE'=>'温馨提示：主播每月至少要有一次提现，提款金额为50的整数倍，最低提现额度为200元。注意：每月25~31号可提交提款申请，申请款将与工资一同发放！',
            'APPROVING'=>'审批中',
            'APPROVED'=>'已审批',
            'REJECT'=>'拒绝',
            'MIN_LIMIT'=>'每次提现不能少于200',
            'MAX_LIMIT'=>'提现金额不能大于可用余额',
            'OK'=>'申请成功！请等待审核'

        ],
        'ANCHOR'=>[
            'TITLE'=>'主播中心',
            'OK'=>'操作成功',
            'NO'=>'操作失败',
            'EDIT'=>'编辑',
            'DEL'=>'删除',
            'UPLOAD'=>'+上传照片',
            'MYPHOTO'=>'我的相片',

        ],
        'ATTENTION'=>[
            'TITLE'=>'我的关注',
            'CANCEL'=>'取消关注',

        ],
        'VIP'=>[
            'TITLE'=>'贵族体系',
            'NAME'=>'贵族头衔',
            'OPEN_TIME'=>'开通时间',
            'R00M_NUMBER'=>'房间号',
            'VALID_DATE'=>'有效期',
            'OVER_DATE'=>'有效期',
            'STATUS'=>'状态',
            'KEEP_LEVEL'=>'保级条件(钻）',
            'KEEP_OK'=>'保级成功',
            'NORMAL'=>'正常',
            'RECHARGE'=>'已充值（钻）',
            'OPERATION'=>'操作',
            'CHARGE'=>'充值',
            'MESSAGE'=>'您目前没有开通过贵族的记录',
            'BUYNOW'=>'立即购买',
            'EXCEPTION'=>'该贵族状态异常,请联系客服！',
            'POINTS_NOT_ENOUGH'=>'亲,你的钻石不够啦！赶快充值吧！',
            'OPENED'=>'你已开通过此贵族，你可以保级或者开通高级贵族！',
            'VALID'=>'请现有等级过期后再开通，或开通高等级的贵族！',
            'NO'=>'可能由于网络原因，开通失败！',
            'OK'=>'开通成功',
            'GET_IT'=>'你已经获取过了该坐骑',
            'BY_VIP'=>'此坐骑专属贵族所有！',
            'LEVEL_LIMIT'=>'你还不够领取此级别的坐骑',
        ],
        'HIDDEN'=>[
            'URL_ERROR'=>'参数错误',
            'USER_NOT_EXIST'=>'用户错误',
            'NO_PERMISSION'=>'没有权限',
            'OK'=>'操作成功'
        ],
        'PAY'=>[
            'NO'=>'购买失败！可能钱不够',
            'OK'=>'购买成功'
        ],
        'CHARGE'=>[
            'TITLE'=>'充值记录',
            'BALANCE'=>'当前余额：',
            'CHARGE'=>'我要充值',
            'CHARGE_ONLINE'=>'在线充值',
            'CHARGE_OTHER'=>'其它充值',
            'TRADE_NUMBER'=>'交易号',
            'TRADE_TIME'=>'交易时间',
            'CHARGE_POINTS'=>'充值金额（钻石）',
            'CHARGE_TYPE'=>'充值方式',
            'PAY_AMOUNT'=>'支付金额（元）',
            'TRADE_STATUS'=>'交易状态',
            'OPERATION'=>'操作',
            'PAY_STATUS_OK'=>'付款成功',
            'PAY_STATUS_ON'=>'付款失败',
            'PAY'=>'付款',
            'DEL'=>'删除',
            'OK'=>'成功',
            'NO'=>'失败'
        ],
        'COMMISSION'=>[

            'TITLE'=>'佣金统计',
            'TOTAL'=>'共计',
            'YUAN'=>'元',
            'SEARCH'=>'查询',
            'SEARCH_TIME'=>'查询时间',
            'TO'=>'至',
            'TIME'=>'时间',
            'NICKNAME'=>'用户昵称',
            'VIP_LEVEL'=>'开通贵族等级',
            'COMMISSION'=>'佣金提成(元)'
        ],
        'CONSUMERD'=>[
            'TITLE'=>'消费记录',
            'BALANCE'=>'当前余额：',
            'CHARGE'=>'我要充值 ',
            'PROPS'=>'道具',
            'NUMBER'=>'数量',
            'PRICE'=>'价格（钻石）',
            'TIME'=>'消费时间',
            'BOOKING_ROOM'=>'预约房间',
            'EMPTY'=>'您目前没有购买记录',
            'BUY_NOW'=>'立即购买'
        ],
        'GAMELIST'=>[
            'TITLE'=>'房间游戏',
            'BANKER_HORSE'=>'我做庄-跑马',
            'PLAYER_HORSE'=>'我玩的-跑马',
            'BANKER_BOXING'=>'我做庄-划拳',
            'PLAYER_BOXING'=>'我玩的-划拳',
            'GAME_CONTENT'=>'游戏内容',
            'BANKER'=>'庄家',
            'PLAYER'=>'玩家',
            'BET'=>'押注',
            'POINTS'=>'获得钻石',
            'ROOM'=>'消费房间',
            'TIME'=>'消费时间',
            'JOCKEY_CLUB'=>'跑马会',
            'BOXING'=>'划拳'

        ],
        'INVITE'=>[
            'TITLE'=>'推广链接',
            'MY_INVITE'=>'我的邀请链接',
            'COPY_INVITE'=>'复制链接'
        ],
        'LIVE'=>[
            'TITLE'=>'直播记录',
            'SEARCH_TIME'=>'查询时间',
            'SEARCH'=>'查询',
            'TO'=>'至',
            'DATE'=>'日期',
            'START_TIME'=>'开始时间',
            'END_TIME'=>'结束时间',
            'DURATION'=>'直播时长',
            'TOTAL'=>'您%s到%s的直播时长总计为%s'
        ],
        'MYRESERVATION'=>[
            'TITLE'=>'预约管理',
            'LIST'=>'我的预约列表',
            'ROOM_TYPE'=>'房间类型',
            'START_TIME'=>'开播时间',
            'DURATION'=>'直播时长',
            'PRICE'=>'费用',
            'USER'=>'主播',
            'STATUS'=>'状态',
            'ROOM'=>'一对一房间',
            'MINUTE'=>'分钟',
            'ENTER'=>'进入房间',
            'RECOMMEND'=>'推荐的一对一主播',
            'LIVE'=>'直播',
            'POINTS'=>'钻',
            'MEETING'=>'立即约会'
        ]
    ],
    'SYSTEM_SERVICE' => [
        'UPLOAD' => [
            'FAIL' => [
                'USER_INFO' => '用户信息获取失败!',
                'NULL_IMG_SERVER' => '获取图片服务器失败',
                'IMG_ERROR' => '上传图片错误',
                'IMG_FORMAT' => '图片格式错误',
                'IMG_SIZE_EXCEED' => '图片上传超过限制大小',
                'NOT_ENOUGH_SPACE' => '你的个人相册空间不足！',
                'NOT_SUPPORTED' => '系统错误,不支持上传功能!',
            ]
        ]
    ],
    'WEB_API' => [
        'LOGIN' => [
            'NETWORK_BUSY' => '网络繁忙！',
            'DB_ERROR' => '数据库异常！',
            'EMPTY_CREDENTIALS' => '用户名或密码不能为空',
            'INVALID_CREDENTIALS' => '帐号密码错误!',
            'FORBIDDEN' => '您的账号已经被禁止登录，请联系客服！',
            'LOGIN_ALREADY' => '已经在登录状态中！',
            'EXCEED_IP_LIMIT' => '此IP地址本日注册帐号达到上限，请明天再试！',
            'INVALID_CAPTCHA' => '验证码错误！请重新刷新验证码',
            'PASSWORD_CONFIRMATION_ERROR' => '两次输入的密码不相同！',
            'INVALID_EMAIL_FORMAT' => '注册邮箱不符合格式！(5-30位的邮箱)',
            'EMAIL_EXISTS' => '注册邮箱重复，请换一个试试！',
            'INVALID_NICKNAME_CHAR' => '注册昵称不能使用/:;\\空格,换行等符号！(2-8位的昵称)',
            'NICKNAME_EXISTS' => '注册昵称重复，请换一个试试！',
            'NICKNAME_FILTER_EXCEPTION' => '昵称中含有非法字符，请修改后再提交!',
            'INVALID_PASSWORD_FORMAT' => '注册密码不符合格式!',
            'REG_SUCCESS' => '恭喜您注册成功！',
        ],
        'GIFT'=>[
            'TITLE'=>'礼物统计',
            'RECEIVE'=>'收到的礼物',
            'SEND'=>'送出的礼物',
            'NAME'=>'礼物名称',
            'PRICE'=>'礼物单价（钻石数）',
            'NUMBER'=>'礼物数量',
            'AMOUNT'=>'礼物总额',
            'TIME'=>'收礼时间',
            'SEND_USER'=>'送礼人',
            'RECEIVE_USER'=>'收礼人',
            'ROOM'=>'房间',
            'SEARCH'=>'查询',
            'SEARCH_TIME'=>'查询时间',
            'TO'=>'至',
            'RECEIVE_TOTAL'=>'共收到%s个礼物，礼物总计：%s颗钻石',
            'SEND_TOTAL'=>'共送出%s个礼物，礼物总计：%s颗钻石',
            'BOOKING_ROOM'=>'预约房间',
        ]
    ],
    'HTML'=>[
        'HEADER'=>[
            'INDEX'=>'首页',
            'RANK'=>'排行榜',
            'LIVE'=>'美女直播',
            'ACTIVE'=>'活动中心',
            'SHOP'=>'商城',
            'NOBLE'=>'贵族',
            'JOINING'=>'招募',
        ]
    ],
    //用于提供给首页静态化的语言包
    'STATIC_HTML'=>[

        //error 错误页面
        'ERROR_TITLE'=>'钻石充值',
        'ERROR_TIPS_TITLE'=>'友情提示',

        //error404
        'ERROR404_TITLE'=>'找不到页面',
        'ERROR404_DOC_TITLE'=>'抱歉，你访问的页面地址有误，或者该页面不存在',
        'ERROR404_DOC_P'=>'点击以下链接继续浏览网站',
        'ERROR404_BTN_BACK'=>'> 返回上一页面',
        'ERROR404_BTN_HOME'=>'> 返回网站首页',

        //footer
        'FOOTER_ABOUT'=>'关于我们',
        'FOOTER_CO'=>'代理招募',
        'FOOTER_HELP'=>'投诉建议',
        'FOOTER_ITEMS'=>'免责条款',
        'FOOTER_BOTTOM'=>'Copyright © 2014-2016 第一坊 All Right Reserved. 联系：yifangkefu@gmail.com',

        //header
        'HEADER_INDEX'=>'首页',
        'HEADER_RANK'=>'排行榜',
        'HEADER_LIVE'=>'美女直播',
        'HEADER_ACTIVITY'=>'活动中心',
        'HEADER_SHOP'=>'商城',
        'HEADER_NOBLE'=>'贵族',
        'HEADER_JOIN'=>'招募',

        'HEADER_BTN_LOGIN'=>'登录',
        'HEADER_BTN_REG'=>'注册',
        'HEADER_BTN_CHARGE'=>'充值',
        'HEADER_CHARGE_TIP'=>'请登录后再进行充值。',
        //meta
        'META_KEY' => '第一坊,1room,第一坊华人视频直播平台是首屈一指的华语美女视频直播秀场、华语版FC2、中国MFC、华人的Livejasmin.最具诱惑的免费秀场,独家私秀,高清流畅福利直播,最流畅的美女直播秀私家夜蒲',
        'META_DES' => '第一坊直播,1room,直播,FC2,直播,MFC,直播,Livejasmin,直播,美女免费,福利直播,门户大秀场,YY大秀,美女免费直播,美女互动直播,性感视频直播,全球领先视频福利直播平台',
        //title
        'TITLE'=> '第一坊-第一坊华人视频直播平台-全球领先视频福利直播平台'
    ],
    //controller文件名
    'RANK'=>[
        'INDEX'=>[
            'INDEX'=>[
                'RANKTITLE'=>'排行榜',
                'RANKDAY'=>'日榜',
                'RANKWEEK'=>'周榜',
                'RANKMONTH'=>'月榜',
                'RANKTOTAL'=>'总榜'
            ]
        ]
    ],
    'SHOP'=>[
        'INDEX'=>[
            'INDEX'=>[
                'SHOPTITLE'=>'商城',
                'MONEY'=>'余额',
                'CHARGE'=>'充值',
                'MYPLAY'=>'我的道具',
                'RIDE'=>'奢华座驾',
                'NOBLE'=>'贵族专属',
                'OPENNOBLE'=>'开通贵族',
                'BUY'=>'购买',
                'MONTH'=>'月',
                'RIDEHORSE'=>'坐骑',
                'GET'=>'领取',
                'FIRSTPRICE'=>'首次开通价格:',
                'OPEN'=>'开通'
            ]
        ]
    ]
];