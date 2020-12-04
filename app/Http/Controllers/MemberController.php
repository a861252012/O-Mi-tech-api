<?php
/**
 * 會員中心
 * @apiDefine Member 會員中心
 */
namespace App\Http\Controllers;

use App\Constants\LvRich;
use App\Facades\SiteSer;
use App\Facades\UserSer;
use App\Libraries\SuccessResponse;
use App\Models\AgentsPriv;
use App\Models\AgentsRelationship;
use App\Models\Anchor;
use App\Models\CarGame;
use App\Models\CarGameBetBak;
use App\Models\Goods;
use App\Models\LevelRich;
use App\Models\MallList;
use App\Models\GiftList;
use App\Models\Messages;
use App\Models\Pack;
use App\Models\Recharge;
use App\Models\RedEnvelopeGet;
use App\Models\RedEnvelopeSend;
use App\Models\RoomAdmin;
use App\Models\RoomDuration;
use App\Models\RoomOneToMore;
use App\Models\RoomStatus;
use App\Models\TimecostMallList;
use App\Models\Transfer;
use App\Models\UserBuyGroup;
use App\Models\UserBuyOneToMore;
use App\Models\UserCommission;
use App\Models\UserGroup;
use App\Models\Users;
use App\Models\UserSignin;
use App\Models\Usersall;
use App\Models\WithDrawalList;
use App\Services\FirstChargeService;
use App\Services\I18n\PhoneNumber;
use App\Services\Message\MessageService;
use App\Services\Room\RoomService;
use App\Services\ShareService;
use App\Services\Sms\SmsService;
use App\Services\System\SystemService;
use App\Services\User\UserService;
use App\Services\User\SigninService;
use App\Services\UserAttrService;
use App\Services\UserGroup\UserGroupService;
use DB;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Mews\Captcha\Facades\Captcha;

class MemberController extends Controller
{
    private $_menus = [
        /**
         * role: 0: 所有角色都有， 1, 普通用户才有， 2, 主播才有, 3, 需要后台人工设置
         */
        [
            'role' => 0,
            'action' => 'index',
            'name' => '基本信息',
        ],
        [
            'role' => 0,
            'action' => 'invite',
            'name' => '邀请注册',
        ],
        [
            'role' => 0,
            'action' => 'attention',
            'name' => '我的关注',
        ],
        [
            'role' => 0,
            'action' => 'scene',
            'name' => '我的道具',
        ],
        [
            'role' => 0,
            'action' => 'charge',
            'name' => '充值记录',
        ],
        [
            'role' => 0,
            'action' => 'consume',
            'name' => '消费统计',
        ],
        //收入统计，主播
        [
            'role' => 2,
            'action' => 'income',
            'name' => '收入统计',
        ],
        [
            'role' => 0,
            'action' => 'redEnvelope',
            'name' => '红包明细',
        ],
        [
            'role' => 0,
            'action' => 'vip',
            'name' => '贵族体系',
        ],
        [
            'role' => 0,
            'action' => 'password',
            'name' => '密码管理',
        ],//主播才有
        [
            'role' => 0,
            'action' => 'reservation',
            'name' => '我的预约',
        ],//主播才有
        [
            'role' => 2,
            'action' => 'roomset',
            'name' => '房间管理',//房间管理员
        ],//主播才有
        [
            'role' => 3,
            'action' => 'transfer',
            'name' => '转账',
        ],//主播才有
        [
            'role' => 2,
            'action' => 'withdraw',
            'name' => '提现',
        ],
        /*   [
               'role' => 2,
               'action' => 'anchor',
               'name' => '主播中心',
           ],*///主播才有
        [
            'role' => 0,
            'action' => 'game',
            'name' => '房间游戏',
        ],//主播才有
        [
            'role' => 2,
            'action' => 'commission',
            'name' => '佣金统计',
        ],//主播才有
        [
            'role' => 2,
            'action' => 'live',
            'name' => '直播记录',
        ],//主播才有
        [
            'role' => 0,
            'action' => 'message',
            'name' => '收件夹',
        ],
        [
            'role' => 0,
            'action' => 'agents',
            'name' => '代理数据',
        ],
    ];

    protected $shareService;

    public function __construct(ShareService $shareService)
    {
        $this->shareService = $shareService;
    }

    public function getMenu()
    {
        $user = Auth::user();
        $hasAgentsPriv = AgentsPriv::where('uid', Auth::user()->uid)->count();

        $params['menus_list'] = [];

        foreach ($this->_menus as $key => $item) {
            //后台设置转帐菜单
            //if ((!isset($this->userInfo['transfer']) || !$this->userInfo['transfer']) && $item['action'] == 'transfer') continue;

            //代理菜单
            if (!$hasAgentsPriv && $item['action'] == 'agents') {
                continue;
            }

            //role == 0 为所有用户权限
            if ($item['role'] == 0) {
                $params['menus_list'][] = $item;
                //continue;
            }

            //role == 1 普通用户 role == 2 主播
            //如果是主播
            if ($user['roled'] == 3 && $item['role'] == 2) {
                $params['menus_list'][] = $item;
            }

            //如果不是主播
            if ($user['roled'] != 3 && $item['role'] == 1) {
                $params['menus_list'][] = $item;
            }

            //如果需要人工设置
            if ($item['role'] == 3 && (isset($user[$item['action']]) && $user[$item['action']])) {
                $params['menus_list'][] = $item;
            }

        }
        return JsonResponse::create(['status' => 1, 'data' => $params]);
    }

    /**
     * 用户中心 基本信息
     */
    public function index()
    {
        $userService = resolve(UserService::class);
        $modNickName = $userService->getModNickNameStatus();
        return JsonResponse::create(['status' => 1, 'data' => ['modNickName' => $modNickName]]);
    }

    /**
     * 用户中心 消息 列表页面
     *
     * @param int $type 消息类型
     *                  1 系统消息
     *                  2 私信
     * @return JsonResponse
     * @author Nicholas
     */
    public function msglist($type = 1)
    {
        // 调用消息服务
        $msg = resolve(MessageService::class);

        // 根据用户登录的uid或者用户消息的分页数据
        $ms = $msg->getMessageByUidAndType(Auth::id(), $type, '', Auth::user()->lv_rich);

        // 更新读取的状态
        $msg->updateMessageStatus(Auth::id());

        return JsonResponse::create([
            'status' => 1,
            'data' => ['list' => $ms],
        ]);
    }

    /**
     * [transfer 转帐功能]
     */
    public function transfer(Request $request)
    {
        $uid = Auth::id();
        $user = Auth::user();
        //转帐菜单
        if (!$user->transfer) {
            return JsonResponse::create(['status' => 0, 'msg' => __('messages.permission_denied')]);
        }
        /**
         * 转帐处理过程
         * @todo 待优化
         */
        if (!Captcha::check($request->get('captcha'))) {
            return new JsonResponse(['status' => 0, 'msg' => __('messages.captcha_error')]);
        };
        //收款人信息
        $username = $request->get('username');
        $points = $request->get('points');
        $content = $request->get('content');
        //判断交易密码
        if (!resolve(UserService::class)->checkUserTradePassword($uid, $request->get('trade_password'))) {
            return new JsonResponse(['status' => 0, 'msg' => __('messages.Member.transfer.wrong_pwd')]);
        }

        if (/*$username == $user['username'] || */
            $username == $user['uid']) {
            return new JsonResponse(['status' => 0, 'msg' => __('messages.Member.transfer.transfer_to_owner')]);
        }

        if (intval($points) < 1) {
            return new JsonResponse(['status' => 0, 'msg' =>  __('messages.Member.transfer.wrong_points')]);
        }

        //获取转到用户信息   clark已确认，转账只能uid  2018/7/20
        $userTo = (array)DB::table((new Users)->getTable())->where('uid', $username)->first();
        /*  if (!$userTo) {
              $userTo = (array)DB::table((new Users)->getTable())->where('uid', $username)->first();
          }*/

        if (!$userTo) {
            return new JsonResponse(['status' => 0, 'msg' => __('messages.Member.transfer.wrong_user')]);
        }

        if (!$user['transfer']) {
            return new JsonResponse(['status' => 0, 'msg' => __('messages.Member.transfer.permission_denied')]);
        }

        if ($user['points'] < $points) {
            return new JsonResponse(['status' => 0, 'msg' => __('messages.out_of_money')]);
        }

        //开始转帐事务处理
        DB::beginTransaction();
        try {
            DB::table((new Users)->getTable())->where('uid', $uid)->decrement('points', $points);
            //update(array('points' => $this->userInfo['points'] - $points));

//            DB::table((new Users)->getTable())->where('uid', $userTo['uid'])->increment('points', $points);
            //update(array('points' => $user['points'] + $points));

            //本次紀錄時間
            $S_update_time = date('Y-m-d H:i:s');

            //驗證是否符合首充豪禮條件
            $trendNo = 'transfer_' . $uid . '_to_' . $userTo['uid'] . '_' . uniqid();

            DB::table((new Users)->getTable())->where('uid', $userTo['uid'])->increment('points', $points);

            //记录转帐
            DB::table((new Transfer)->getTable())->insert([
                'by_uid' => $uid,
                'by_nickname' => $user['nickname'],
                'to_uid' => $userTo['uid'],
                'to_nickname' => $userTo['nickname'],
                'points' => $points,
                'content' => $content,
                'datetime' => $S_update_time,
                'status' => 1,
                'site_id' => SiteSer::siteId()
            ]);

            //写入recharge表方便保级运算
            DB::table((new Recharge)->getTable())->insert([
                'uid' => $userTo['uid'],
                'points' => $points,
                'paymoney' => round($points / 10, 2),
                'created' => $S_update_time,
                'order_id' => $trendNo,
                'del' => 0,//190418stanly添加
                'pay_type' => 7,
                'pay_status' => 2,
                'nickname' => $userTo['nickname'],
                'site_id' => $userTo['site_id']
            ]);

            //发送成功消息给转帐人
            $from_user_transfer_message = [
                'mail_type' => 3,
                'rec_uid'   => $uid,
                'content'   => __(
                    'messages.Member.transfer.send_reminder_msg',
                    [
                        'points' => $points,
                        'username' => $username
                    ]
                ),
                'site_id'   => $user['site_id'],
                'locale'    => App::getLocale()
            ];
            resolve(MessageService::class)->sendSystemtranslate($from_user_transfer_message);

            //发送成功消息给收帐人
            $to_user_transfer_message = [
                'mail_type' => 3,
                'rec_uid'   => $userTo['uid'],
                'content'   => __(
                    'messages.Member.transfer.receive_reminder_msg',
                    [
                        'sender' => $user['nickname'],
                        'points' => $points
                    ]
                ),
                'site_id'   => $userTo['site_id'],
                'locale'    => App::getLocale()
            ];
            resolve(MessageService::class)->sendSystemtranslate($to_user_transfer_message);

            DB::commit();//事务提交

            //检查收款人用户VIP保级状态 一定要在事务之后进行验证
            $this->checkUserVipStatus($userTo);

            //驗證首充資格及贈送首充禮物
            resolve(FirstChargeService::class)->firstCharge($username);

            // remove cache
            $userService = resolve(UserService::class);
            $userService->cacheUserInfo($uid, null);
            $userService->cacheUserInfo($userTo['uid'], null);
            return new JsonResponse([
                'status' => 1,
                'msg'    => __('messages.Member.transfer.transfer_success', ['points' => $points])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();//事务回滚
            logger()->debug($e);
            return new JsonResponse(['status' => 0, 'msg' => __('messages.Member.transfer.transfer_failed')]);
        }
    }

    public function transferHistory(Request $request)
    {
        $mintime = $request->get('mintime');
        $maxtime = $request->get('maxtime');
        $uid = Auth::id();
        $transfers = Transfer::where(function ($query) use ($uid) {
            $query->where('by_uid', $uid)->orWhere('to_uid', $uid);
        });
        if ($mintime && $maxtime) {
            $v['mintime'] = date('Y-m-d 00:00:00', strtotime($mintime));
            $v['maxtime'] = date('Y-m-d 23:59:59', strtotime($maxtime));
            $transfers->where('datetime', '>=', $v['mintime'])->where('datetime', '<=', $v['maxtime']);
        }
        $transfersall = $transfers->orderBy('datetime', 'desc')->pluck('points');
        $total_amount=0;
        foreach ($transfersall as $transfersallval) {
            $total_amount += $transfersallval;
        }
        $transfers = $transfers->orderBy('datetime', 'desc')->paginate(10)
            ->appends(['mintime' => $mintime, 'maxtime' => $maxtime]);
        return new JsonResponse(['status' => 1, 'data' => ['list' => $transfers,'total_amount'=>$total_amount]]);

    }

    public function transferList(Request $request)
    {
        $mintime = $request->get('starttime');
        $maxtime = $request->get('endtime');
        $uid = Auth::id();
        //if(isset($uid)){
        $transfers = Transfer::where(function ($query) use ($uid) {
            $query->where('by_uid', $uid)->orWhere('to_uid', $uid);
        });
        if ($mintime && $maxtime) {
            $v['mintime'] = date('Y-m-d 00:00:00', strtotime($mintime));
            $v['maxtime'] = date('Y-m-d 23:59:59', strtotime($maxtime));
            $transfers->where('datetime', '>=', $v['mintime'])->where('datetime', '<=', $v['maxtime']);
        }

        $transfers = $transfers->orderBy('datetime', 'desc')->get();
        $transfersall = array();
        $total_amount=0;
        foreach ($transfers as $transfersval) {
            $total_amount += $transfersval['points'];
            $O = (object) array();
            $O->odd_number=(string) $transfersval['auto_id'];
            $O->time=(string) $transfersval['datetime'];
            $O->account_transfer=(string) $transfersval['by_uid'];
            $O->account_receipt=(string) $transfersval['to_uid'];
            $O->diamond=(string) $transfersval['points'];
            $O->marks=(string) $transfersval['content'];
            array_push($transfersall, $O);
        }
        return new JsonResponse([
            'status' => 1,
            'data'   => ['list' => $transfersall, 'total_amount' => $total_amount],
            'msg'    => __('messages.success')
        ]);
    }

    /**
     * 用户中心 代理数据
     * @author raby
     * @update 2016.06.30
     * @return JsonResponse
     */
    public function agents()
    {

        $agentsPriv = AgentsPriv::where('uid', Auth::user()->uid)->with("agents")->first();

        if (!$agentsPriv) {
            // return new RedirectResponse('/');
            return new JsonResponse(['status' => 0, 'msg' => '']);
        }

        $mintimeDate = $this->request()->get('mintimeDate') ?: date('Y-m-d', strtotime('-1 month'));
        $maxtimeDate = $this->request()->get('maxtimeDate') ?: date('Y-m-d');
        $mintime = date('Y-m-d H:i:s', strtotime($mintimeDate));
        $maxtime = date('Y-m-d H:i:s', strtotime($maxtimeDate . ' 23:59:59'));
        //查询充值成功的记录，而不是处理中的记录
        $recharge = resolve(Recharge::class);
        $paymoney = $recharge->getSummaryPaymoney($agentsPriv->aid, $mintime, $maxtime);
        $recharge_points = $paymoney * 10;
        $rebate_points = ($recharge_points * $agentsPriv->agents->rebate) / 100;

        $agent_members = AgentsRelationship::where('aid', $agentsPriv->aid)
            ->when($maxtime || $mintime, function ($query) use ($mintime, $maxtime) {
                $query->join('video_user','video_agent_relationship.uid','=','video_user.uid')->whereBetween('video_user.created', [$mintime, $maxtime]);
            })->count();

        $list = [
            [
                'aid' => $agentsPriv->aid,
                'username' => $agentsPriv->agents->agentaccount,
                'nickname' => $agentsPriv->agents->nickname,
                'members' => $agent_members,
                'recharge_points' => $recharge_points,
                'rebate_points' => $rebate_points,
            ],
        ];


        return new JsonResponse([
            'data' => [
                'list' => $list,
                'mintimeDate' => $mintimeDate,
                'maxtimeDate' => $maxtimeDate
            ]
        ]);
    }

    /**
     * [roomadmin 个人中心房间管理员管理]
     *
     * @author  dc <dc#wisdominfo.my>
     * @version 2015-11-17
     * @return  JsonResponse
     */
    public function roomadmin()
    {
        $rid = Auth::id();
        //管理员数据列表
        $v['roomadmin'] = RoomAdmin::where('rid', $rid)->where('dml_flag', '!=', 3)->with('user')->paginate(30);
        $res = $v['roomadmin']->toArray();
        foreach ($res['data'] as $key => $value) {
            if (empty($value['user'])) {
                $reset = Users::where('uid', $value['uid'])->allSites()->first();
                $res['data'][$key]['user'] = $reset ? $reset->toArray() : [];
            }
        }

        return new JsonResponse([
            'status' => 1,
            'data'   => ['list' => $res],
            'msg'    => __('messages.successfully_obtained')
        ]);
    }

    public function roomadmindelete()
    {
        $rid = Auth::id();
        $request = $this->make('request');
        $uid = $request->get('uid');
        if (!$uid) {
            return new JsonResponse(['status' => 0, 'msg' => __('messages.Api.rankListGift.empty_uid')]);
        }
        //管理员软删除操作
        RoomAdmin::where('rid', $rid)->where('uid', $uid)->update(['dml_flag' => 3]);
        //删除redis管理员记录
        $this->make('redis')->srem('video:manage:' . $rid, $uid);
        return new JsonResponse(['status' => 1, 'msg' => __('messages.Charge.del.success')]);
    }

    public function vip_list()
    {
        //获取购买vip的所有信息
        $data = UserBuyGroup::with('group')
            ->where('uid', Auth::id())
            ->where('status', 1)
            ->orderBy('end_time', 'desc')
            ->paginate();

        $list = $data->toArray();

        $filtered = collect($list['data'])->map(function ($item, $key) {
            $item['group']['level_name'] = __("messages.user.ViplevelName." . $item['group']['level_id']);
            return $item;
        });

        $list['data'] = $filtered->all();

        return JsonResponse::create([
            'status' => 1,
            'data' => ['list' => $list, 'type' => 'vip']
        ]);
    }

    /**
     * 用户中心 贵族体系
     */
    public function vip()
    {

        /**
         * 获取开通过的日志 最新一条就是当前
         */
        $log = [];
        $user = Auth::user();
        // 如果用户还是贵族状态的话  就判断充值的情况用于保级
        $startTime = strtotime($user->vip_end) - 30 * 24 * 60 * 60;

        if ($user->vip) {
            $group = LevelRich::where('level_id', $user->vip)->first();

            if (!$group) {
                return SuccessResponse::create('', $status = 1, $msg = __('messages.success'));// 用户组都不在了没保级了
                //return true;// 用户组都不在了没保级了
            }

            $userGid = $group->gid;

            //$log = UserBuyGroup::with('group')->where('uid', Auth::id())
            //dc修改增加status字段筛选
            $log = UserBuyGroup::with('group')->where('uid', Auth::id())->where('status', 1)
                ->where('gid', $userGid)->orderBy('end_time', 'desc')->first();

            // 获取最近一个月充值的总额
            if ($startTime < time()) {
                //如果还没保级成功的话
                $charge = Recharge::where('uid', Auth::id())
                    ->where('created', '>=', date('Y-m-d H:i:s', $startTime))
                    ->where('pay_status', 2)->where(function ($query) {
                        //$query->orWhere('pay_type', 1)->orWhere('pay_type', 4);
                        //@author dc 修改，增加转帐保级统计
                        $query->orWhere('pay_type', 1)->orWhere('pay_type', 4)->orWhere('pay_type', 7);
                    })->sum('points');
            } else {
                // 如果已经保级过了，就应该再往前减一个月才是当前月充值的
                $charge = Recharge::where('uid', Auth::id())
                    ->where('created', '>=', date('Y-m-d H:i:s', $startTime - 30 * 24 * 60 * 60))
                    ->where('pay_status', 2)->where(function ($query) {
                        //$query->orWhere('pay_type', 1)->orWhere('pay_type', 4);
                        //@author dc 修改，增加转帐保级统计
                        $query->orWhere('pay_type', 1)->orWhere('pay_type', 4)->orWhere('pay_type', 7);
                    })
                    ->sum('points');
            }
            //dc修改容错处理
            if ($log) {
                $log->charge = $charge;
                $log->group->level_name = __('messages.user.ViplevelName.' . $user->vip);//貴族名稱做多語化字串處理
            }
        }
        $data = [];
        $data['item'] = $log;
        return SuccessResponse::create($data, $status = 1, $msg = __('messages.success'));
    }

    /**
     * 用户中心 主播佣金统计
     */
    public function commission(Request $request)
    {
        $type = 'open_vip';
        $uid = Auth::id();
        $mintimeDate = $request->get('mintime') ?: date('Y-m-d', strtotime('-1 year'));
        $maxtimeDate = $request->get('maxtime') ?: date('Y-m-d');
        $mintime = date('Y-m-d H:i:s', strtotime($mintimeDate));
        $maxtime = date('Y-m-d H:i:s', strtotime($maxtimeDate . ' 23:59:59'));

        $all_data = UserCommission::where('uid', $uid)
            ->allSites()
            ->where('create_at', '>', $mintime)->where('create_at', '<', $maxtime)->where('type', $type)
            ->where('dml_flag', '!=', 3)
            ->orderBy('create_at', 'desc')->with([
                'user' => function ($q) {
                    $q->selectRaw('uid, username, roled, lv_exp, lv_rich')->allSites();
                }
            ])
            ->with('userGroup')->paginate(10);

        $total = UserCommission::selectRaw('sum(points) as points')
            ->where('uid', $uid)
            ->allSites()
            ->where('create_at', '>', $mintime)->where('create_at', '<', $maxtime)->where('type', $type)
            ->where('dml_flag', '!=', 3)
            ->first();

        $total_points = ceil($total->points / 10);
        $all_data->appends(['mintime' => $mintimeDate, 'maxtime' => $maxtimeDate]);

        return JsonResponse::create([
            'status' => 1,
            'data' => [
                'list' => $all_data,
                'total_points' => $total_points,
            ],
        ]);
    }

    /**
     * 用户中心 邀请推广
     * @api {get} /member/invite 分享網址
     * @apiGroup Member
     * @apiName invite
     * @apiVersion 1.0.0
     *
     * @apiError (Error Status) 999 API執行錯誤
     *
     * @apiSuccess {String} url 分享網址
     * @apiSuccess {String} title 標題
     *
     * @apiSuccessExample {json} 成功回應
     * {
    "status": 1,
    "msg": "OK",
    "data": "http:\/\/10.2.121.179:81\/126\/static\/landingpage\/8.html?scode=6U90DC24",
    "title": "第一坊全球最大成人直播平台。"
    }
     */
    public function invite()
    {
        try {
            /* 取得隨機網域 */
            $domain = $this->shareService->randomDoamin();

            /* 產生分享代碼 */
            $scode = $this->shareService->genScode(Auth::id());

            $title = SiteSer::siteConfig('name', SiteSer::siteId()) . '全球最大成人直播平台。';

            $this->setStatus(1, 'OK');
            $this->setRootData('data', $domain . '?scode=' . $scode);
            $this->setRootData('title', $title);
            return $this->jsonOutput();
        } catch (\Exception $e) {
            report($e);
            $this->setStatus('999', __('messages.apiError'));
            return $this->jsonOutput();
        }
    }

    /**
     * 通过新浪微薄平台生成短网址
     * @param $url
     * @return bool|string
     * @author  D.C
     * @update  2015-02-05
     * @version 1.0
     * @todo    目前由于该api-key身份验证问题，新浪有一定的流量限制，无法保证所有用户都能生成短网址
     */
    private function _buildWeiboShortUrl($url)
    {
        if (!$url) {
            return false;
        }
        $api = 'http://api.t.sina.com.cn/short_url/shorten.json';
        $key = '942825741';
        $api_url = sprintf($api . "?source=%s&url_long=%s", $key, $url);

        $uid = Auth::id();
        $redis = $this->make('redis');
        if ($redis->hexists('user_url_t', $uid)) {
            return $redis->hget('user_url_t', $uid);
        } else {

            $result = @file_get_contents($api_url);
            if (!is_null(json_decode($result))) {
                $result = json_decode($result);
                if (isset($result[0]->url_short)) {
                    $url_short = trim($result[0]->url_short);
                    if ($url_short) {
                        $redis->hset('user_url_t', $uid, $url_short);
                        return $url_short;
                    } else {
                        return false;
                    }

                } else {
                    return false;
                }

            } else {
                return false;
            }


        }
    }

    /**
     * 用户中心 我的关注接口
     * @Author Nicholas
     * @return Response
     */
    public function attention(Request $request)
    {
        $page = $request->get('page', 1);
        $amt = $request->get('amt', 15);
        $amt = max(15, min(1000, $amt));
        $userServer = resolve(UserService::class);
        $data = $userServer->getUserAttens(Auth::id(), $page, $fid = true, $perPage = $amt)
            ->setPath($request->getPathInfo());
        return JsonResponse::create(['data' => ['list' => $data]]);
    }

    public function sceneToggle(Request $request)
    {
        $action = $request->get('action', 'true');
        if ($action == 'true') {
            $handle = $this->_getEquipHandle($request->get('gid'));
            if (is_array($handle)) {
                return new JsonResponse($handle);
            }
            return JsonResponse::create(['status' => 0, 'msg' => __('messages.apiError')]);
        } elseif ($action == 'false') {
            return $this->cancelScene();
        }
        return JsonResponse::create(['status' => 0]);
    }

    /**
     * 装备操作逻辑处理
     * @param $gid
     * @return array|bool
     * @author D.C
     * @update 2014.12.10
     */
    private function _getEquipHandle($gid)
    {

        $uid = Auth::id();
        if (!$gid || !$uid) {
            return false;
        }

        $pack = Pack::where('uid', $uid)->where('gid', $gid)->first();
        if (!$pack) {
            return false;
        }

        /**
         * 判定道具类型,
         * @todo 跟[Antony]确认，规定category字段1000-1999ID范围为可装备道具,增加查询道具类型。
         */
        $goods = Goods::find($gid);
        if ($goods['gid'] < 120001 || $goods['gid'] > 121000) {
            return ['status' => 0, 'msg' => __('messages.Member._getEquipHandle.equipment_room_only')];
        }

        /**
         * 使用Redis进行装备道具
         * @todo   目前道具道备只在Redis上实现，并未进行永久化存储。目前产品部【Antony】表示保持现状。
         * @update 2014.12.15 14:35 pm (Antony要求将道具改为同时只能装备一个道具！)
         */
        $redis = resolve('redis');
        $redis->del('user_car:' . $uid);
        $redis->hset('user_car:' . $uid, $gid, $pack['expires']);
        return ['status' => 1, 'msg' => __('messages.Member._getEquipHandle.equip_success')];
    }

    /**
     * 取消装备道具
     * @return JsonResponse
     * @Author Orino
     */
    public function cancelScene()
    {
        Redis::del('user_car:' . Auth::id());//检查过期直接删除对应的缓存key
        return new JsonResponse(['status' => 1, 'msg' => __('messages.success')]);
    }

    /**
     * 用户中心 我的道具
     */
    public function scene(Request $request)
    {
        $uid = Auth::id();
        $data = Pack::with('mountGroup')->where('uid', $uid)->paginate();
        //判断是否过期
        $equip = Redis::hgetall('user_car:' . $uid);
        if ($equip != null && current($equip) < time()) {
            Redis::del('user_car:' . $uid);//检查过期直接删除对应的缓存key
        }
        return JsonResponse::create([
            'status' => 1,
            'data' => ['list' => $data, 'equip' => $equip,],
        ]);
    }

    /**
     * 用户中心 个人充值列表
     *
     * @author cannyco<cannyco@weststarinc.co>
     * @update 2015.01.30
     */
    public function charge(Request $request)
    {
        $mintime = $request->get('mintime');
        $maxtime = $request->get('maxtime');

        $status = $request->get('status');
        //获取用户ID
        $uid = Auth::id();
        //获取下用户信息
        $chargelist = Recharge::where('uid', $uid)->where('del', 0);

        if ($mintime && $maxtime) {
            $v['mintime'] = date('Y-m-d 00:00:00', strtotime($mintime));
            $v['maxtime'] = date('Y-m-d 23:59:59', strtotime($maxtime));
            $chargelist->where('created', '>=', $v['mintime'])->where('created', '<=', $v['maxtime']);
        }

        if ($status) {
            $chargelist->where('pay_status', $status);
        }
        //统计钻石
        $chargelistall = $chargelist->orderBy('created', 'desc')->pluck('points');
        $total_amount=0;
        foreach ($chargelistall as $chargelistallval) {
            $total_amount += $chargelistallval;
        }
        //统计金额
        $chargelistall2 = $chargelist->orderBy('created', 'desc')->pluck('paymoney');
        $total_money=0;
        foreach ($chargelistall2 as $chargelistallval2) {
            $total_money += $chargelistallval2;
        }

        $chargelists = $chargelist->orderBy('id', 'DESC')->paginate(10)
            ->appends(['mintime' => $mintime, 'maxtime' => $maxtime]);

        return JsonResponse::create(['status' => 1, 'data' => ['list' => $chargelists,'total_amount'=>$total_amount,'total_money'=>$total_money]]);
    }

    /**
     * @api {get} /api/[m]/consume 用户中心 消费记录
     *
     * @apiDescription mobile版URL前綴: /api/m
     *
     * pc版URL前綴: /api
     * @apiGroup Member
     * @apiName consume
     * @apiVersion 1.1.0
     *
     * @apiHeader (Mobile Header) {String} Authorization Mobile 須帶入 JWT Token
     * @apiHeader (Web Header) {String} Cookie Web 須帶入登入後的 SESSID
     *
     * @apiParam {String} [startTime] 起日(不帶則預設一個月前) (Y-m-d)
     * @apiParam {String} [endTime] 迄日(不帶則預設為當前時間) (Y-m-d)
     * @apiParam {int} [page] 第幾頁 (不帶則預設第一頁)
     *
     * @apiError (Error Status) 0 时间区间错误
     *
     * @apiSuccess {int} status 執行狀態(1為成功,0為時間區間錯誤)
     * @apiSuccess {Array} data
     * @apiSuccess {Array} data.list
     * @apiSuccess {int} data.list.current_page 目前頁碼
     * @apiSuccess {Array} data.list.date
     * @apiSuccess {int} data.list.date.id 流水號
     * @apiSuccess {int} data.list.date.send_uid 送禮的用戶id
     * @apiSuccess {int} data.list.date.rec_uid 收禮的用戶id
     * @apiSuccess {int} data.list.date.gid 禮物id
     * @apiSuccess {int} data.list.date.gnum 禮物數量
     * @apiSuccess {String} data.list.date.created 送禮時間
     * @apiSuccess {int} data.list.date.rid 主播id
     * @apiSuccess {int} data.list.date.points 消費鑽石數
     * @apiSuccess {int} data.list.date.rate 分成比例
     * @apiSuccess {int} data.list.date.origin 用戶來源
     * @apiSuccess {int} data.list.date.site_id 站點
     * @apiSuccess {int} data.list.date.guard_id 守護id
     * @apiSuccess {int} data.list.date.guard_days 守護天數
     * @apiSuccess {String} data.list.date.name 禮物名稱
     * @apiSuccess {int} data.list.date.price 禮物價格
     * @apiSuccess {String} data.list.date.desc 禮物詳細資訊
     * @apiSuccess {String} data.list.date.category_name 禮物種類名稱
     * @apiSuccess {int} data.list.date.category 禮物類別
     * @apiSuccess {int} data.list.date.unit_type 購買單位方式,1=計件;2=計時
     * @apiSuccess {int} data.list.date.is_show 是否上架 （0=下架,1=上架)
     * @apiSuccess {int} data.list.date.is_show 是否上禮物跑到 （0=不上,1=上)
     * @apiSuccess {String} data.list.date.create_time 禮物上線時間
     * @apiSuccess {int} data.list.date.sort_order 商品排序
     * @apiSuccess {int} data.list.date.time
     * @apiSuccess {String} data.list.date.playType
     * @apiSuccess {String} data.list.date.type
     * @apiSuccess {int} data.list.date.x x偏移
     * @apiSuccess {int} data.list.date.y y偏移
     * @apiSuccess {int} data.list.date.xScale x縮放
     * @apiSuccess {int} data.list.date.yScale y縮放
     *
     * @apiSuccess {Int} data.list.first_page_url 第一頁url
     * @apiSuccess {Int} data.list.from 從第幾頁換頁
     * @apiSuccess {Int} data.list.last_page 最後一頁頁碼
     * @apiSuccess {Int} data.list.last_page_url 最後一頁url
     * @apiSuccess {Int} data.list.next_page_url 下一頁url
     * @apiSuccess {Int} data.list.path
     * @apiSuccess {Int} data.list.per_page 筆數
     * @apiSuccess {Int} data.list.prev_page_url 前一頁url
     * @apiSuccess {Int} data.list.to 跳轉到到第幾頁
     * @apiSuccess {Int} data.list.total 總頁數
     *
     * @apiSuccessExample {json} 成功回應
     * {
    "status": 1,
    "data": {
    "list": {
    "current_page": 3,
    "data": [
    {
    "id": 16780810,
    "send_uid": 9493715,
    "rec_uid": 9493607,
    "gid": 120001,
    "gnum": 1,
    "created": "2020-03-13 16:23:52",
    "rid": 9493607,
    "points": 1,
    "rate": 50,
    "origin": 12,
    "site_id": 1,
    "guard_id": 3,
    "guard_days": 30,
    "name": "私人直升机（月）",
    "price": 1500,
    "desc": "坐骑",
    "category_name": "坐骑",
    "category": 1002,
    "unit_type": 2,
    "is_show": 1,
    "is_cast": 0,
    "create_time": "2016-06-02",
    "sort_order": 1,
    "time": 6,
    "playType": "",
    "type": "swf",
    "x": -181,
    "y": -198,
    "xScale": 1,
    "yScale": 1
    }
    ],
    "first_page_url": "http:\/\/localhost\/api\/m\/consume?page=1",
    "from": 31,
    "last_page": 3,
    "last_page_url": "http:\/\/localhost\/api\/m\/consume?page=3",
    "next_page_url": null,
    "path": "http:\/\/localhost\/api\/m\/consume",
    "per_page": 15,
    "prev_page_url": "http:\/\/localhost\/api\/m\/consume?page=2",
    "to": 31,
    "total": 31
    }
    },
    "msg": ""
    }
     */
    public function consume(Request $request)
    {
        $uxStart = strtotime($request->startTime);
        $uxEnd = strtotime($request->endTime);

        /* 開始時間處理 */
        if (empty($uxStart)) {
            $start = date('Y-m-d 00:00:00', strtotime('-1 month'));
        } else {
            $start = date('Y-m-d 00:00:00', $uxStart);
        }

        /* 結束時間處理 */
        if (empty($uxEnd)) {
            $end = date('Y-m-d 23:59:59');
        } else {
            $end = date('Y-m-d 23:59:59', $uxEnd);
        }

        /* 驗證時間區間 */
        if ($uxStart > $uxEnd) {
            return JsonResponse::create(
                [
                    'status' => 0,
                    'msg' => __('messages.Roulette.getHistory.date_range_error')
                ]
            );
        }

        $data = MallList::query()->leftJoin('video_goods', function ($leftJoin) {
            $leftJoin->on('video_goods.gid', '=', 'video_mall_list.gid');
        })
            ->when(!empty($start) && !empty($end), function ($query) use ($start, $end) {
                $query->whereBetween('video_mall_list.created', [$start, $end]);
            })
            ->where('video_mall_list.send_uid', Auth::id())
            ->where('video_mall_list.gid', '>', 0)
            ->where('video_goods.category', '=', 1002)
            ->orderBy('video_mall_list.created', 'DESC')
            ->paginate();

        $list = $data->toArray();
        $list['data'] = $this->langTransfer($list['data']);

        return JsonResponse::create([
            'status' => 1,
            'data' => ['list' => $list],
        ]);
    }

    /**
     * 用户中心 密码管理
     * @param Request $request
     * @return JsonResponse
     */
    public function passwordChange(Request $request)
    {
        $uid = Auth::id();
        $post = $request->all();
        /** @noinspection PhpUndefinedClassInspection */
        if (!Captcha::check(Input::get('captcha'))) {
            return JsonResponse::create(['status' => 0, 'msg' => __('messages.Api.reg.captcha_error')]);
        }

        if (empty($post['password'])) {
            return JsonResponse::create([
                'status' => 0,
                'msg'    => __('messages.Mobile.passwordChange.old_password_required')
            ]);
        }


        if (strlen($post['password1']) < 6 || strlen($post['password2']) < 6) {
            return JsonResponse::create([
                'status' => 0,
                'msg'    => __('messages.Mobile.passwordChange.more_or_equal_than_six_char_length')
            ]);
        }

        if ($post['password1'] != $post['password2']) {
            return JsonResponse::create([
                'status' => 0,
                'msg'    => __('messages.Mobile.passwordChange.new_password_is_not_the_same')
            ]);
        }

        $old_password = resolve(UserService::class)->getUserInfo($uid, 'password');
        $new_password = md5($post['password2']);
        if (md5($post['password']) != $old_password) {
            return JsonResponse::create([
                'status' => 0,
                'msg'    => __('messages.Mobile.passwordChange.old_password_is_wrong')
            ]);
        }
        if ($old_password == $new_password) {
            return JsonResponse::create([
                'status' => 0,
                'msg'    => __('messages.Mobile.passwordChange.new_and_old_is_the_same')
            ]);
        }

        $user = Users::find($uid);
        $user->password = $new_password;
        if (!$user->save()) {
            return JsonResponse::create(['status' => 0, 'msg' =>  __('messages.Mobile.passwordChange.modify_failed')]);
        }
        resolve(UserService::class)->getUserReset($uid);
        Auth::logout();
        return new JsonResponse(['status' => 1, 'msg' => __('messages.Member.passwordChange.successfully_modified')]);
    }

    /**
     *用户中心  房间设置  TODO  尼玛
     * @author TX
     * @update 2015.4.16
     * @return Response
     */
    public function roomset()
    {
        //$type = $this->request()->get('type');
        $page1 = $this->request()->input('page1', 1);
        $page2 = $this->request()->input('page2', 1);
        $tab = $this->request()->input('tab', 'one2one');
        $status = [];
        for ($i = 1; $i <= 7; $i++) {//获取房间状态，从2到7的状态
            $ROOM = $this->getRoomStatus(Auth::id(), $i);//获取对应的房间权限状态
            if (!empty($ROOM)) {
                array_push($status, $ROOM);
            }
        }
        $result['types'] = $status;
        $result['tab'] = $tab;
        Paginator::currentPageResolver(function () use ($page2) {
            return $page2;
        });
        $result['roomlistOneToMore'] = RoomOneToMore::where('uid', Auth::id())->where('status', 0)
            ->orderBy('starttime', 'DESC')
            ->paginate(10)->appends(['tab' => 'one2many', 'page1' => $page1])->setPageName('page2')->setPath('');
        Paginator::currentPageResolver(function () use ($page1) {
            return $page1;
        });
        $result['roomlistOneToOne'] = RoomDuration::where('uid', Auth::id())->where('status', 0)
            ->orderBy('starttime', 'DESC')
            ->paginate(10)->appends(['tab' => 'one2one', 'page2' => $page2])->setPageName('page1')->setPath('');
        /* for ($i = 0; $i < 25; $i++) {//生成前端的小时下拉框
             if ($i < 10) $result['hoption'][$i]['option'] = '0' . $i;
             else $result['hoption'][$i]['option'] = $i;
         }
         for ($i = 0; $i < 12; $i++) {//生成前端分钟的下拉框,每五分钟一次
             if ($i < 2) $result['ioption'][$i]['option'] = '0' . ($i * 5);
             else $result['ioption'][$i]['option'] = $i * 5;
         }*/

        //时长房间
        $roomStatus = $this->getRoomStatus(Auth::id(), 6);
        $result['roomStatus'] = $roomStatus;
        $res['data'] = $result;
        $res['status'] = 1;
        $res['msg'] = __('messages.success');
        return new JsonResponse($res);

    }

    /**
     * 一对多设置
     * @return JsonResponse
     */

    /**
     * @description 获取房间权限
     * @author      TX
     * @date        2015.4.20
     */
    public function getRoomStatus($uid, $tid)
    {
        $hasname = 'hroom_status:' . $uid . ':' . $tid;
        $status = $this->make('redis')->hget($hasname, 'status');

        if (!empty($status) || ($status == 0 && $tid == 2)) {

            if ($status == 1 || ($status == 0 && $tid == 2)) {

                $data = $this->make('redis')->hgetall($hasname);
            } else {
                return null;
            }
        } else {
            //            $datas =  $this->getDoctrine()->getRepository('Video\ProjectBundle\Entity\VideoRoomStatus')->createQueryBuilder('r')
            //                ->where('r.uid='.$uid.'  and  r.tid='.$tid.' and r.status = 1')
            //                ->orderby('r.id','ASC')
            //                ->getQuery();
            //            $roomdata = $datas->getResult();
            $data = RoomStatus::where('uid', $uid)
                ->where('tid', $tid)->where('status', 1)
                ->orderBy('id', 'ASC')->first();
            /**
             * dc修改，有数据时再转换数组
             */
            $data = $data ? $data->toArray() : $data;
            /*
             * 因上面$roomdata被注释,改用eloquent查询方式.忘记注释判断,导致房间获取失败
             * 现添加注释
             * @author dc
             * @version 20160407
             * */
            /*if (empty($roomdata)) {
                return null;
            }*/

            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    $this->make('redis')->hset('hroom_status:' . $uid . ':' . $tid, $key, $value);
                }
            }
        }

        return $data;
    }

    /**
     * 一对多记录详情-购买用户
     * @return JsonResponse
     */
    public function getBuyOneToMore()
    {
        $onetomore = $this->make('request')->get('onetomore');
        $userService = resolve(UserService::class);

        $buyOneToMore = UserBuyOneToMore::where('onetomore', $onetomore)->where('type', 2)->allSites()->get();
        $buyOneToMore->map(function (&$item) use ($userService) {
            $user = $userService->getUserByUid($item->uid);
            $item->nickname = isset($user['nickname']) ? $user['nickname'] : '';
        });
        return new JsonResponse(['status' => 1, 'data' => $buyOneToMore, 'msg' => __('messages.success')]);
    }

    /**
     * 开发：
     * 1.代码增加来源----------------无需处理
     * 2.代码修改hroom_whitelist_key------添加，删除ok
     * 3.主播设置一对多金额--------前端处理(待处理)，后台功能ok
     *
     * todo 增加默认值---ok
     *
     *
     * @return JsonResponse
     */
    public function roomOneToMore(Request $request)
    {
        $data = [];
        $data = $request->only(['mintime', 'hour', 'minute', 'tid', 'duration', 'points', 'origin']);

        $data['uid'] = Auth::guard()->id();
        //  var_dump($data);exit;

        /*检查是否已开启一对多
        $flashVersion = SiteSer::config('publish_version');
        $oneManyRooms = Redis::get('home_one_many_' . $flashVersion . ':' . SiteSer::siteId());
        $oneManyRooms = str_replace(['cb(', ');'], ['', ''], $oneManyRooms);
        $oneManyRooms = json_decode($oneManyRooms, true);
        $S_check = 0;
        foreach ($oneManyRooms['rooms'] as $S_room) {
            if($S_room['uid']==$data['uid']){
                $S_check++;
            }
        }
        if($S_check>0){
            return new JsonResponse(['status' => 0, 'msg' => '开启一对多房间时，无法设定']);
        }*/

        $A_rotm = RoomOneToMore::where('uid', Auth::id())->where('endtime', '>',date("Y-m-d H:i:s"))->where('status', 0)->pluck('live_status');

        if(count($A_rotm)>0){
            return new JsonResponse(['status' => 0, 'msg' => __('messages.Member.passwordChange.can_not_setting')]);
        }

        $roomservice = resolve(RoomService::class);
        $result = $roomservice->addOnetomore($data);
        return new JsonResponse($result);

    }

    /**
     * 时长房间金额设置
     * @author raby
     * @create 2016.9.16
     */
    public function roomSetTimecost()
    {
        return JsonResponse::create(['status' => 0, 'msg' => __('messages.illegal_operation')]);//禁止用户修改
        $timecost = $this->make('request')->get('timecost');
        if ($timecost <= 0 || $timecost > 999999) {
            return new JsonResponse(['status' => "301", 'msg' => __('messages.Member.roomSetTimecost.timecost_wrong')]);
        }

        //todo 时长房直播，并且开启时，不处理
        $timecost_status = $this->make("redis")->hget("htimecost:" . Auth::id(), "timecost_status");
        if ($timecost_status == 1) {
            return new JsonResponse(['status' => "302", 'msg' => __('messages.Member.roomSetTimecost.can_not_setting')]);
        }

        RoomStatus::where("uid", Auth::id())->where("tid", 6)->where("status", 1)->update(['timecost' => $timecost]);

        $this->make("redis")->hset("hroom_status:" . Auth::id() . ":6", "timecost", $timecost);
        return new JsonResponse(['status' => 1, 'msg' => __('messages.set_successfully')]);
    }

    /**
     *含时长房间设置  TODO 优化。。。。
     * @author TX
     * @update 2015.4.27
     * @return JsonResponse
     */
    public function roomSetDuration(Request $request)
    {

        $data = [];
        $data = $request->only(['mintime', 'hour', 'minute', 'tid', 'duration', 'origin']);
        if (empty($data['origin'])) {
            $data['origin'] = 11;
        }
        if (!in_array($data['duration'], [25, 55])) {
            return new JsonResponse(['status' => 0, 'msg' => __('messages.Api.reg.invalid_request')]);
        }
        // 判断是否为手动输入 如果手动输入需要大于2000钻石才行

        $input_points = $request->get('input_points');
        if ($input_points > 99999) {
            return new JsonResponse(['status' => 0, 'msg' => __('messages.Member.roomSetDuration.max_setting')]);
        }
        if ($input_points < 2000) {
            return new JsonResponse(['status' => 0, 'msg' => __('messages.Member.roomSetDuration.min_setting')]);
        } else {
            $points = $input_points;
        }

        $data['points'] = $points;

        $roomservice = resolve(RoomService::class);
        $result = $roomservice->addOnetoOne($data);
        return new JsonResponse($result);


    }

    /**
     *房间密码设置
     * @author TX
     * @update 2015.4.16
     * @return JsonResponse
     */
    public function roomSetPwd()
    {
        $room_radio = $this->make('request')->get('room_radio');
        $password = '';
        if ($room_radio == 'true') {
            $password = $this->make('request')->get('password');
        }
        $password = $this->decode($password);

        if (empty($password) && $room_radio == 'true') {
            return new JsonResponse(['status' => 2, 'msg' => __('messages.Member.roomSetPwd.pwd_empty')]);
        }
        if ($room_radio == 'true') //判断密码格式,密码格式和用户注册的密码格式是一样的
        {
            if ($room_radio == 'true' && strlen($password) < 6 || strlen($password) > 22 || !preg_match('/^\w{6,22}$/', $password)) {
                return new JsonResponse(['status' => 3, 'msg' => __('messages.Member.roomSetPwd.wrong_pwd_format')]);
            }
        }
        //        $this->getRedis();

        //        $em = $this->getDoctrine()->getManager();
        //        $roomtype =  $em->getRepository('Video\ProjectBundle\Entity\VideoRoomStatus')->findOneBy(array('uid'=>$this->_uid,'tid'=>2));
        //        $roomtype->setPwd($password);
        //        $em->persist($roomtype);
        //        $em->flush();


        if ($room_radio == 'false') {
            $this->make('redis')->hset('hroom_status:' . Auth::id() . ':2', 'status', 0);
            $this->make('redis')->hset('hroom_status:' . Auth::id() . ':2', 'pwd', $password);
            $roomtype = RoomStatus::where('uid', Auth::id())->where('tid', 2)->update([
                'status' => 0,
                'pwd' => $password
            ]);
            return new JsonResponse(['status' => 1, 'msg' => __('messages.Member.roomSetPwd.close_pwd_success')]);

        }
        $this->make('redis')->hset('hroom_status:' . Auth::id() . ':2', 'status', 1);
        $this->make('redis')->hset('hroom_status:' . Auth::id() . ':2', 'pwd', $password);
        $roomtype = RoomStatus::where('uid', Auth::id())->where('tid', 2)->update(['pwd' => $password, 'status' => 1]);
        return new JsonResponse(['status' => 1, 'msg' => __('messages.Password.pwdResetSubmit.modify_success')]);
    }

    /**
     *房间密码验证
     * @author TX
     * updata 2015.4.16
     * @return JsonResponse
     */
    public function checkroompwd()
    {
        $password = $this->request()->get('password');
        $rid = $this->request()->get('roomid');
        $type = $this->getAnchorRoomType($rid);
        $password = $this->decode($password);
        if ($type != 2) {
            return new JsonResponse(['status' => 0, 'msg' => __('messages.Member.checkroompwd.pwd_room_error')]);
        }
        if (empty($rid)) {
            return new JsonResponse(['status' => 0, 'msg' => __('messages.Member.checkroompwd.wrong_roomID')]);
        }
        if (empty($password)) {
            return $this->geterrorsAction();
        }
        //        $this->get('session')->start();
        $sessionid = $this->request()->getSession()->getId();
        //房间进入密码，超过五次就要输入验证码，这个五次是通过phpsessionid来判断的
        $roomstatus = $this->getRoomStatus($rid, 2);
        $keys_room = 'keys_room_passwd:' . $sessionid . ':' . $rid;
        $times = $this->make('redis')->get($keys_room);
        if ($times >= 5) {
            $captcha = $this->request()->get('captcha');
            if (empty($captcha)) {
                return new JsonResponse([
                    'status' => 4,
                    'msg'    => __('messages.Member.checkroompwd.please_enter_verify_pwd'),
                    'data'   => $times
                ]);
            }
            if (!Captcha::check($captcha)) {
                return new JsonResponse(['status' => 0, 'msg' => __('messages.captcha_error'), 'data' => $times]);
            }
        }
        if (strlen($password) < 6 || strlen($password) > 22 || !preg_match('/^\w{6,22}$/', $password)) {
            $this->make('redis')->set($keys_room, $times + 1);
            $this->make('redis')->expire($keys_room, 3600);
            return new JsonResponse([
                "status" => 0,
                "msg" => __('messages.Member.checkroompwd.wrong_pwd_format'),
                'data' => $times + 1,
            ]);
        }

        if ($password != $roomstatus['pwd']) {
            if (empty($times)) {
                $this->make('redis')->set($keys_room, 1);
                $this->make('redis')->expire($keys_room, 3600);
            } else {
                $this->make('redis')->set($keys_room, $times + 1);
                $this->make('redis')->expire($keys_room, 3600);
            }
            return new JsonResponse([
                "status" => 0,
                "msg" => __('messages.Member.checkroompwd.wrong_pwd'),
                'data' => $times + 1,
            ]);
        }
        $this->make('redis')->hset('keys_room_passwd:' . $rid . ':' . $sessionid, 'status', 1);
        return new JsonResponse(['status' => 1, 'msg' => __('messages.Member.checkroompwd.verified_successfully')]);
    }

    /**
     *房间密码错误次数请求
     * @author TX
     * updata 2015.4.16
     * @return JsonResponse
     */
    public function geterrorsAction()
    {
        $rid = $this->request()->get('roomid');
        if (empty($rid)) {
            return new JsonResponse(['code' => 2, 'msg' => __('messages.Member.checkroompwd.wrong_roomID')]);
        }
        //        $this->get('session')->start();
        $session_name = $this->request()->getSession()->getName();
        if (isset($_POST[$session_name])) {
            $this->request()->getSession()->setId($_POST[$session_name]);
        }
        $sessionid = $this->request()->getSession()->getId();
        $keys_room = 'keys_room_errorpasswd:' . $sessionid . ':' . $rid;
        $times = $this->make('redis')->hget($keys_room, 'times');
        if (empty($times)) {
            $times = 0;
        }
        return new JsonResponse(['code' => 1, 'times' => $times]);
    }

    /**
     *用户中心 我的预约
     */
    public function reservation(Request $request)
    {
        $type = $request->get('type', 1);
        $rooms = [];
        $recommend = [];
        $data = [
            'list' => &$rooms,
            'recommend' => &$recommend,
        ];
        $userServer = resolve(UserService::class);
        switch ($type) {
            case 2:
                $rooms = UserBuyOneToMore::where('uid', Auth::id())->orderBy('starttime', 'DESC')->paginate();
                break;
            case 1:
            default:
                $rooms = RoomDuration::where('reuid', Auth::id())
                    ->where('starttime', '>', time() . '-duration')
                    ->orderBy('starttime', 'DESC')
                    ->paginate();
        }
        $items = $rooms->getCollection();
        foreach ($items as &$item) {
            $rid = $type == 2 ? $item->rid : $item->uid;
            $userinfo = $userServer->getUserByUid($rid);
            $item['nickname'] = $userinfo['nickname'];
            $item['starttime'] = date('Y-m-d H:i:s', strtotime($item['starttime']));
            $item['endTime'] = date('Y-m-d H:i:s', strtotime($item->starttime) + ($item->duration));
            //date('Y-m-d H:i:s',strtotime($time1)+30*60);//注意引号内的大小写,分钟是i不是m
            $item['duration'] = ceil($item->duration / 60);
            $item['points'] = $item->points;
            $item['uid'] = $userinfo['uid'];
            $item['headimg'] = $userinfo['headimg'];
            $item['now'] = date('Y-m-d H:i:s');
            $item['url'] = '/' . $userinfo['uid'];
        }
        $page = $request->get('page') ?: 1;
        //我的预约推荐是从redis中取数据的，先全部取出数据，做排序
        $recommend = collect($this->getReservation(Auth::id()))->forPage($page, 6);

        $redis = resolve('redis');
        $image_server = SiteSer::config('img_host') . '/';

        foreach ($recommend as $keys => &$value) {
            $userinfo = resolve(UserService::class)->getUserByUid($value['uid']);

            $value = array_merge($value, ['nickname' => $userinfo->nickname]);//todo 字段过滤
            $value['duration'] = ceil($value['duration'] / 60);
            $value['datenu'] = date('YmdHis', strtotime($value['starttime']));
            $value['roomid'] = $value['id'];
            $value['headimg'] = $userinfo['headimg'];
            $cover = $redis->get('shower:cover:version:' . $userinfo['uid']);
            $value['cover'] = $cover ? $image_server . $cover : false;
        }
        return JsonResponse::create(['data' => $data]);
    }

    /**
     * 获取我的预约推荐列表TODO
     * hroom_duration socket定时任务删除: 1.时间到没人预约 2.有人预约的场次结束
     * todo 尼玛
     * @param $uid
     * @return array
     * @Author TX
     */
    public function getReservation($uid)
    {
        $uids = $this->getRoomUid($uid);
        $rooms = [];
        $user_key = [];
        array_push($user_key, 'hroom_duration:' . $uid . ':4');
        $keys = RoomDuration::query()->whereRaw('starttime<DATE_SUB(now(),INTERVAL duration  SECOND)')->get()->pluck('uid')->map(function (
            $id
        ) {
            return 'hroom_duration:' . $id;
        })->toArray();
        if ($keys == false) {
            $keys = [];
        }

        $room_reservation = [];
        foreach ($uids['reservation'] as $value) {
            array_push($user_key, 'hroom_duration:' . $value . ':4');
            $roomlist = Redis::hGetAll('hroom_duration:' . $value . ':4');
            foreach ($roomlist as $item) {
                $room = json_decode($item, true);
                if ($room['status'] == 0 && $room['reuid'] == 0 && $room['uid'] != $uid && strtotime($room['starttime']) > time()) {
                    array_push($room_reservation, $room);
                }
            }
            foreach ($room_reservation as $key => $row) {
                $edition[$key] = $row['starttime'];
            }
            if (count($room_reservation) > 0) {
                array_multisort($edition, SORT_ASC, $room_reservation);
            }
        }
        $room_attens = [];
        foreach ($uids['attens'] as $value) {
            array_push($user_key, 'hroom_duration:' . $value . ':4');
            $roomlist = Redis::hGetAll('hroom_duration:' . $value . ':4');
            foreach ($roomlist as $item) {
                $room = json_decode($item, true);
                if ($room['status'] == 0 && $room['reuid'] == 0 && $room['uid'] != $uid && strtotime($room['starttime']) > time()) {
                    array_push($room_attens, $room);
                }
            }
            foreach ($room_attens as $key => $row) {
                $edition_attens[$key] = $row['starttime'];
            }
            if (count($room_attens) > 0) {
                array_multisort($edition_attens, SORT_ASC, $room_attens);
            }
        }
        $keys = array_diff($keys, $user_key);
        $room_list = [];
        foreach ($keys as $value) {
            array_push($user_key, $value);
            $roomlist = Redis::hGetAll($value);
            foreach ($roomlist as $item) {
                $room = json_decode($item, true);
                if ($room['status'] == 0 && $room['reuid'] == 0 && $room['uid'] != $uid && strtotime($room['starttime']) > time()) {
                    $room['rid'] = $room['uid'];
                    $users = resolve(UserService::class)->getUserInfo($room['uid']);
                    $room['headimg'] = $users['headimg'] . '.jpg';
                    $room['nickname'] = $users['nickname'];
                    $room['lv_exp'] = $users['lv_exp'];
                    $room['cover'] = $users['cover'];
                    array_push($room_list, $room);
                }
            }
            foreach ($room_list as $key => $row) {
                $edition_list[$key] = $row['starttime'];
            }
            if (count($room_list) > 0) {
                array_multisort($edition_list, SORT_ASC, $room_list);
            }
        }
        foreach ($room_reservation as $value) {
            array_push($rooms, $value);
        }
        foreach ($room_attens as $value) {
            array_push($rooms, $value);
        }
        foreach ($room_list as $value) {
            array_push($rooms, $value);
        }
        return $rooms;
    }

    /**
     *立即预约 TODO 优化优化再优化 要重写
     * @author TX
     * @update 2015.4.27
     * @return JsonResponse
     */
    public function doReservation()
    {
        $roomid = $this->request()->get('rid');
        $flag = $this->request()->get('flag');
        if (empty($roomid) || empty($flag)) {
            return new JsonResponse(['status' => 0, 'msg' => __('messages.request_error')]);
        }

        $duroom = RoomDuration::query()->where('id', '=', $roomid)->first();

        if (empty($duroom)) {
            return new JsonResponse(['status' => 0, 'msg' => __('messages.request_error')]);
        }
        if (empty($duroom)) {
            return new JsonResponse([
                'status' => 0,
                'msg'    => __('messages.Member.doReservation.reserved_room_not_exist')
            ]);
        }
        if ($duroom['status'] == 1) {
            return new JsonResponse(['status' => 0, 'msg' => __('messages.Member.doReservation.room_offline')]);
        }
        if ($duroom['reuid'] != '0') {
            return new JsonResponse([
                'status' => 0,
                'msg'    => __('messages.Member.doReservation.room_already_reserved')
            ]);
        }
        if ($duroom['uid'] == Auth::id()) {
            return new JsonResponse([
                'status' => 0,
                'msg'    => __('messages.Member.doReservation.room_forbidden_yourself')
            ]);
        }
        if (Auth::user()->points < $duroom['points']) {
            return new JsonResponse(['status' => 0, 'msg' => __('messages.out_of_money')]);
        }
        //关键点，这个时段内有没有其他的房间重复，标志位为flag 默认值为false 当用户确认后传入的值为true
        if (!$this->checkRoomUnique($duroom, Auth::id()) && $flag == 'false') {
            return new JsonResponse([
                'status' => 101,
                'msg'    => __('messages.Member.doReservation.room_reservation_repeat')
            ]);
        }
        $duroom['reuid'] = Auth::id();
        $duroom['invitetime'] = time();
        $duroom->save();
        $this->set_durationredis($duroom);
        //记录一个标志位，在我的预约列表查询中需要优先显示查询已经预约过的主播，已经预约过的主播的ID会写到这个redis中类似关注一样的
        if (!($this->checkUserAttensExists(Auth::id(), $duroom['uid'], true, true))) {
            Redis::zadd('zuser_reservation:' . Auth::id(), time(), $duroom['uid']);
        }
        Users::where('uid', Auth::id())->update([
            'points' => (Auth::user()->points - $duroom['points']),
            'rich' => (Auth::user()->rich + $duroom['points'])
        ]);
        resolve(UserService::class)->getUserReset(Auth::id());// 更新redis TODO 好屌
        //增加消费记录查询
        MallList::create([
            'send_uid' => Auth::id(),
            'rec_uid' => $duroom['uid'],
            'gid' => 311028,
            //$duroom['roomtid'],irwin
            'gnum' => 1,
            'created' => date('Y-m-d H:i:s'),
            'rid' => $duroom['uid'],
            'points' => $duroom['points'],
        ]);
        // 用户增加预约排行榜的排名
        Redis::zIncrBy('zrank_appoint_month' . date('Ym'), 1, $duroom['uid']);
        //修改用户日，周，月排行榜数据
        //zrank_rich_history: 用户历史消费    zrank_rich_week ：用户周消费   zrank_rich_day ：用户日消费  zrank_rich_month ：用户月消费
        $expire_day = strtotime(date('Y-m-d 00:00:00', strtotime('next day'))) - time();
        $expire_week = strtotime(date('Y-m-d 00:00:00', strtotime('next week'))) - time();
        $zrank_user = ['zrank_rich_history', 'zrank_rich_week', 'zrank_rich_day', 'zrank_rich_month:' . date('Ym')];
        foreach ($zrank_user as $value) {
            Redis::zIncrBy($value, $duroom['points'], Auth::id());
            if ('zrank_rich_day' == $value) {
                Redis::expire('zrank_rich_day', $expire_day);
            }
            if ('zrank_rich_week' == $value) {
                Redis::expire('zrank_rich_week', $expire_week);
            }
        }
        //修改主播日，周，月排行榜数据
        //zrank_pop_history ：主播历史消费   zrank_pop_month  ：主播周消费 zrank_pop_week ：主播日消费 zrank_pop_day ：主播月消费
        $zrank_pop = ['zrank_pop_history', 'zrank_pop_month:' . date('Ym'), 'zrank_pop_week', 'zrank_pop_day'];
        foreach ($zrank_pop as $value) {
            Redis::zIncrBy($value, $duroom['points'], $duroom['uid']);
            if ('zrank_pop_day' == $value) {
                Redis::expire('zrank_pop_day', $expire_day);
            }
            if ('zrank_pop_week' == $value) {
                Redis::expire('zrank_pop_week', $expire_week);
            }
        }
        Redis::lPush('lanchor_is_sub:' . $duroom['uid'], date('YmdHis', strtotime($duroom['starttime'])));
        Log::channel('room')->info('buyOneToOne', $duroom->toArray());

        return new JsonResponse(['code' => 1, 'msg' => __('messages.Member.doReservation.reserve_successfully')]);
    }

    /**
     * 发送私信
     */
    public function domsg(Request $request)
    {
        // $fid = $this->get('request')->get('fid');
        $tid = $request->get('tid');
        if (Auth::id() == $tid) {
            return JsonResponse::create(['status' => 0, 'msg' => __('messages.Member.domsg.can_not_send_to_yourself')]);
        }
        if (empty($tid) || !UserSer::userExists($tid)) {
            return JsonResponse::create(['status' => 0, 'msg' => __('messages.Member.domsg.receiver_not_exist')]);
        }

        $content = $request->get('content');
        $len = $this->count_chinese_utf8($content);
        if ($len < 0 || $len > 200) {
            return JsonResponse::create(['status' => 0, 'msg' => __('messages.Member.domsg.msg_length_limit')]);
        }
        $userInfo = $this->userInfo;
        if ($userInfo['roled'] == 0 && $userInfo['lv_rich'] < 3) {
            return JsonResponse::create(['status' => 0, 'msg' => __('messages.Member.domsg.lv_rich_limit')]);
        }

        $num = $this->checkAstrictUidDay(Auth::id(), 1000, 'video_mail');//验证每天发帖次数
        if ($num == 0) {
            return JsonResponse::create(['status' => 0, 'msg' => __('messages.Member.domsg.out_of_msg')]);
        }

        $message = new Messages();
        $res = $message->create([
            'send_uid' => Auth::id(),
            'rec_uid' => $tid,
            'content' => htmlentities($content),
            'category' => 2,
            'status' => 0,
            'created' => date('Y-m-d H:i:s'),
        ]);
        if ($res) {
            $this->setAstrictUidDay(Auth::id(), $num, 'video_mail');//更新每天发帖次数
            return JsonResponse::create(['status' => 1, 'msg' => __('messages.Member.domsg.send_msg_successfully')]);
        } else {
            return JsonResponse::create(['status' => 0, 'msg' => __('messages.Member.domsg.send_msg_failed')]);
        }
    }

    /**
     * 用户中心 提现页面
     */
    public function withdrawHistory(Request $request)
    {
        $user = Auth::user();
        if ($user['roled'] != 3) {
            abort(404);
        }
        $mintime = $request->get('mintime') ?: date('Y-m-d', strtotime('-1 month'));
        $maxtime = $request->get('maxtime') ?: date('Y-m-d', strtotime('now'));
        $status = $request->get('status') ?: 0;
        $availableBalance = $this->getAvailableBalance(Auth::id());

        $maxtime = date('Y-m-d' . ' 23:59:59', strtotime($maxtime));

        //        $thispage = $this->make('request')->get('page') ?: 1;
        $data = WithDrawalList::where('uid', Auth::id())->where('created', '<', $maxtime)->where('created', '>',
            $mintime)
            ->where('status', $status)
            ->orderBy('created', 'DESC')
            ->paginate();

        $status_array = [
            '0' => '审批中',
            '1' => '已审批',
            '2' => '拒绝',
        ];
        $data->getCollection()->each(function ($item) use ($status_array) {
            $item['status'] = $status_array[$item->status];
        });

        return JsonResponse::create([
            'status' => 1,
            'data' => ['list' => $data, 'available_balance' => $availableBalance]
        ]);
    }

    /**
     *  提现申请
     */
    public function withdraw(Request $request)
    {
        $money = $request->get('withdrawnum', 0);
        if (empty($money) || $money < 200) {
            return new JsonResponse(['status' => 0, 'msg' => __('messages.Member.withdraw.withdraw_min_limit')]);
        }
        $uid = Auth::id();
        $avila_points = $this->getAvailableBalance($uid);
        if ($money > $avila_points['availmoney']) {
            return new JsonResponse(['status' => 0, 'msg' => __('messages.Member.withdraw.withdraw_max_limit')]);
        }
        $wd = date('ymdhis') . substr(microtime(), 2, 4);
        $withrawal = new WithDrawalList();
        $withrawal->uid = $uid;
        $withrawal->created = date('Y-m-d H:i:s');
        $withrawal->money = $money;
        $withrawal->moneypoints = $this->BalanceToOponts($money, $uid);
        $withrawal->withdrawalnu = $wd;
        $withrawal->status = 0;//0表示审批中
        $withrawal->save();
        return new JsonResponse(['status' => 1, 'msg' => __('messages.Member.withdraw.withdraw_successfully')]);
    }

    /**
     * 用户中心 主播中心
     */
    public function anchor()
    {
        $user = Auth::user();
        if ($user['roled'] != 3) {
            return JsonResponse::create(['status' => 0]);
        }

        //更新相册
        if (in_array($this->make('request')->get('handle'), ['del', 'get', 'set'])) {
            $id = sprintf("%u", $this->make('request')->get('id'));
            if (!$id) {
                return new Response(json_encode(['code' => 101, 'info' => __('messages.request_error')]));
            }
            $result = $this->_anchorHandle($this->make('request')->get('handle'), $id);
            if ($result) {
                $result = is_array($result) ? json_encode($result) : $result;
                return new Response(json_encode(['code' => 0, 'info' => __('messages.success'), 'data' => $result]));
            } else {
                return new Response(json_encode(['code' => 103, 'info' => __('messages.apiError'), 'data' => $result]));
            }
        }

        $data = Anchor::where('uid', Auth::id())->orderBy('jointime', 'DESC')
            ->paginate();
        //        $result['IMGHOST'] = trim($this->container->getParameter('REMOTE_PIC_URL'), '/') . '/';
        $result['gallery'] = $data;
        $result['totals'] = count($result['gallery']);
        $result['userinfo'] = $user;
        $result['userinfo']['headimg'] = $this->getHeadimg($result['userinfo']['headimg'], 180);
        $result['sessid'] = session_id();
        return $this->render('Member/anchor', $result);
    }

    /**
     * @description 主播中心相册操作附加方法
     * @author      D.C
     * @param null $type
     * @param int $id
     * @return array|bool
     * @update      2014.11.7
     */
    private function _anchorHandle($type = null, $id = 0)
    {
        if (!$type || !$id) {
            return false;
        }
        $anchor = Anchor::find($id);
        if (!$anchor) {
            return false;
        }
        $pic_used_size = $this->userInfo['pic_used_size'] - $anchor['size'];

        switch ($type) {
            case 'del':
                //将用户剩余图片空间同步更新数据库及redis
                $uid = Auth::id();
                $data = ['pic_used_size' => $pic_used_size];
                resolve(UserService::class)->updateUserInfo($uid, $data);
                Anchor::find($id)->delete();
                break;

            case 'get':
                $anchor = Anchor::find($id);
                return $anchor ? $anchor->toArray() : [];
                break;

            case 'set':
                //更新图片名称与备注
                Anchor::find($id)->update([
                    'name' => $this->make('request')->get('name'),
                    'summary' => $this->make('request')->get('summary')
                ]);
                break;

            default:
                return false;
        }
        return true;
    }

    /**
     *用户中心 房间游戏
     * @TODO 优化用户信息
     */
    public function gamelist($type = 1)
    {
        $data = [];
        // 我参与的
        if ($type == 1) {
            $data = CarGameBetBak::with([
                'game' => function ($query) {
                    $query->with([
                        'gameMasterUser' => function ($q) {
                            $q->selectRaw('uid,nickname')->allSites();
                        }
                    ]);
                }
            ])->with([
                'gameRoomUser' => function ($q) {
                    $q->selectRaw('uid,nickname')->allSites();
                }
            ])
                ->where('uid', Auth::id())
                ->where('dml_flag', '!=', 3)
                ->orderBy('created', 'desc')
                ->allSites()
                ->paginate();
        }
        // 我做庄的
        if ($type == 2) {
            $data = CarGame::where('uid', Auth::id())
                ->where('dml_flag', '!=', 3)
                ->orderBy('stime', 'DESC')
                ->paginate();

            foreach ($data as $key => $value) {
                $reset = Users::where('uid', $value['rid'])->allSites()->first();
                if ($reset) {
                    $userinfo = $reset->toArray();
                    $game_room_user = array(
                        'nickname' => $userinfo['nickname'],
                        'uid' => $userinfo['uid'],
                    );
                    $data[$key]['game_room_user'] = $game_room_user;
                } else {
                    $data[$key]['game_room_user'] = [
                        'nickname' => '',
                        'uid' => '',
                    ];
                }
            }
        }
        return JsonResponse::create(['status' => 1, 'data' => ['list' => $data]]);
    }

    /**
     * 用户中心 礼物统计
     * @description 礼物统计 TODO 优化可能性
     * @author      D.C
     * @date        2015.2.6
     */
    public function income()
    {
        $uid = Auth::id();
        if (!$uid) {
            throw new HttpResponseException(JsonResponse::create([
                'status' => 0,
                'msg'    => __('messages.not_logged_in')
            ]));
        }
        $type = $this->make('request')->get('type') ?: 'receive';
        $mint = $this->make('request')->get('mintime') ?: date('Y-m-d', strtotime('-1 day'));
        $maxt = $this->make('request')->get('maxtime') ?: date('Y-m-d');

        $mintime = date('Y-m-d H:i:s', strtotime($mint));
        $maxtime = date('Y-m-d H:i:s', strtotime($maxt . ' 23:59:59'));

        $selectTypeName = $type == 'send' ? 'send_uid' : 'rec_uid';
        $uriParammeters = $this->make('request')->query->all();
        $var['uri'] = [];
        foreach ($uriParammeters as $p => $v) {
            if (strstr($p, '?')) {
                continue;
            }
            if (!empty($v)) {
                $var['uri'][$p] = $v;
            }
        }

        $all_data = MallList::select(
            'u1.nickname AS nickname',
            'u2.nickname AS rnickname',
            'video_goods.*',
            'video_mall_list.*'
        )
            ->leftJoin('video_goods', function ($leftJoin) {
                $leftJoin->on('video_goods.gid', '=', 'video_mall_list.gid');
            })
            ->leftJoin('video_user as u1', function ($query) {
                $query->on('u1.uid', '=', 'video_mall_list.send_uid');
            })
            ->leftJoin('video_user as u2', function ($query) {
                $query->on('u2.uid', '=', 'video_mall_list.rec_uid');
            })
            ->when($selectTypeName == 'send_uid', function ($query) {
                return $query->where('video_goods.category', '!=', 1009);
            })
            ->where($selectTypeName, $uid)
            ->where('video_mall_list.created', '>', $mintime)
            ->where('video_mall_list.created', '<', $maxtime)
            ->where('video_mall_list.gid', '>', 10)
            ->where('video_mall_list.gid', '!=', 410001)
            ->where('video_goods.category', '!=', 1002)
            ->orderBy('video_mall_list.created', 'desc')
            ->allSites()
            ->paginate();

        $sum_gift_num = MallList::query()->leftJoin('video_goods', function ($leftJoin) {
            $leftJoin->on('video_goods.gid', '=', 'video_mall_list.gid');
        })
            ->when($selectTypeName == 'send_uid', function ($query) {
                return $query->where('video_goods.category', '!=', 1009);
            })
            ->where($selectTypeName, $uid)
            ->where('video_mall_list.created', '>', $mintime)
            ->where('video_mall_list.created', '<', $maxtime)
            ->where('video_mall_list.gid', '>', 10)
            ->where('video_mall_list.gid', '!=', 410001)
            ->where('video_goods.category', '!=', 1002)
            ->allSites()
            ->sum('gnum');

        $sum_points_num = MallList::query()->leftJoin('video_goods', function ($leftJoin) {
            $leftJoin->on('video_goods.gid', '=', 'video_mall_list.gid');
        })
            ->when($selectTypeName == 'send_uid', function ($query) {
                return $query->where('video_goods.category', '!=', 1009);
            })
            ->where($selectTypeName, $uid)
            ->where('video_mall_list.created', '>', $mintime)
            ->where('video_mall_list.created', '<', $maxtime)
            ->where('video_mall_list.gid', '>', 10)
            ->where('video_mall_list.gid', '!=', 410001)
            ->where('video_goods.category', '!=', 1002)
            ->allSites()
            ->sum('points');
        $sum_gift_num = $sum_gift_num ?: 0;
        $sum_points_num = $sum_points_num ?: 0;
        /*  $twig = clone $this->make('view');
          $twig->setLoader(new \Twig_Loader_String());

          $function = new \Twig_SimpleFunction('getUserName', function ($uid) {
              if (!$uid) return;
              $user = Users::find($uid);
              if ($user) {
                  return $user['nickname'] ?: $user['username'];
              }
          });

          $twig->addFunction($function);*/

        /* $function = new \Twig_SimpleFunction('getGoods', function ($gid) {
             if (!$gid) return false;
             return $this->getGoods($gid);
         });
         $twig->addFunction($function);*/

        //todo author raby
        if ($type == "counttime") {
            $where_uid = ['send_uid' => Auth::id()];
            if (Auth::user()->roled == 3) {
                $where_uid = ['rec_uid' => Auth::id()];
            }

            $timecost = (new TimecostMallList())->getList($where_uid, [$mintime, $maxtime]);
            $points_sum = $timecost->get()->sum("sum_points");
            //$uid_sum = $timecost->distinct('send_uid')->count('send_uid');
            $temp_uid_sum = $timecost->get()->toArray();
            $uid_sum = count(array_unique(array_column($temp_uid_sum, 'send_uid')));
            unset($temp_uid_sum);

            $all_data = $timecost->paginate();
            $all_data->appends(['type' => $type, 'mintime' => $mint, 'maxtime' => $maxt]);

            $sum_gift_num = $uid_sum;
            $sum_points_num = $points_sum;
            //$data['timecost_list'] = $live_list;
            //print_r($all_data); exit;
        }

        $all_data->appends(['type' => $type, 'mintime' => $mint, 'maxtime' => $maxt]);

        $list = $all_data->toArray();
        $list['data'] = $this->langTransfer($list['data']);

        $var['type'] = $type;
        $var['list'] = $list;
        $var['mintime'] = $mint;
        $var['maxtime'] = $maxt;
        $var['sum_gift_num'] = $sum_gift_num;
        $var['sum_points_num'] = $sum_points_num;

        $var = $this->format_jsoncode($var);
        return new JsonResponse(($var));

    }

    /* 消費紀錄多國語轉換 */
    private function langTransfer($data)
    {
        $goodsName = 'name';
        $locale = strtolower(App::getLocale()) ?? 'zh';
        if ('zh' != $locale) {
            $goodsName .= "_{$locale}";
        }

        $filtered = collect($data)->map(function ($item, $key) use ($goodsName) {
            $item['name'] = $item[$goodsName];
            return collect($item)->except(['name_zh_tw', 'name_zh_hk', 'name_en']);
        });

        return $filtered->all();
    }

    public function giftList(Request $request)
    {
        $mintime = $request->get('starttime');
        $maxtime = $request->get('endtime');
        $type = $request->get('type')!=''?$request->get('type'):'rec';

        $uid = Auth::id();

        //if(isset($uid)){
        $gifts = GiftList::where($type.'_uid', $uid);
        if ($mintime && $maxtime) {
            $v['mintime'] = date('Y-m-d 00:00:00', strtotime($mintime));
            $v['maxtime'] = date('Y-m-d 23:59:59', strtotime($maxtime));
            $gifts->where('created', '>=', $v['mintime'])->where('created', '<=', $v['maxtime']);
        }

        /*if($type=='send'){
            $giftsall = $gifts->orderBy('created', 'desc')->paginate(10)->appends(['mintime' => $mintime, 'maxtime' => $maxtime ]);
        }else{*/
        $gifts = $gifts->orderBy('created', 'desc')->get();
        $giftsall = array();
        foreach ($gifts as $giftsval) {
            $good = Goods::find($giftsval['gid']);
            $O = (object) array();

            $O->odd_number=(string) $giftsval['id'];
            $O->good_name=(string) $good->name;
            $O->good_number=(string) $giftsval['gnum'];
            $O->good_id=(string) $giftsval['gid'];
            $O->diamond=(string) $giftsval['points'];
            $O->time=(string) $giftsval['created'];
            if($type=='send'){
                //谁收到
                $user = Users::find($giftsval['rec_uid']);
                $O->receiver=(string) $user->nickname;
            }else{
                //谁送的
                $user = Users::find($giftsval['send_uid']);
                $O->sender=(string) $user->nickname;
            }
            $O->room_id=(string) $giftsval['rid'];
            $O->platform=(string) $giftsval['site_id'];
            array_push($giftsall, $O);
        }

        //}

        return new JsonResponse(['status' => 1, 'data' => ['list' => $giftsall],'msg'=>__('messages.success')]);
    }

    /**
     * 用户中心 直播时间
     */
    public function live(Request $request)
    {
        $uid = Auth::id();
        /**
         * 查询的开始时间 用于获取数据
         * 默认是前一天到现在的
         */
        $start = $request->get('start') ?: $request->session()->get('live_start');
        if (!$start) {
            $start = date('Y-m-d', strtotime("-1 day"));
        } else {
            $request->session()->put('live_start', $start);
        }

        $end = $request->get('end') ?: $request->session()->get('live_end');
        if (!$end) {
            $end = date('Y-m-d');
        } else {
            $request->session()->put('live_end', $end);
        }
        $result = [];
        $end = date('Y-m-d' . ' 23:59:59', strtotime($end));
        $start = date('Y-m-d' . ' 00:00:00', strtotime($start));
        $result['list'] = [];
        /**
         * 获取自播记录 运算时长
         */
        $iEnd = strtotime($end);
        $iStart = strtotime($start);


        $data = (array)DB::select('SELECT * FROM video_live_list WHERE uid=:uid AND ((start_time < :end AND start_time > :start) OR (
          start_time < :start2 AND UNIX_TIMESTAMP(start_time)+duration < :iEnd AND UNIX_TIMESTAMP(start_time)+duration > :iStart))
          ORDER BY start_time DESC', [
            'uid' => $uid,
            'end' => $end,
            'start' => $start,
            'start2' => $start,
            'iEnd' => $iEnd,
            'iStart' => $iStart
        ]);
        $thispage = $this->make("request")->get('page') ?: 1;
        //我的预约推荐是从redis中取数据的，先全部取出数据，做排序
        $total_duration = $data;
        $Count = count($data);
        $data = array_slice($data, ($thispage - 1) * 15, 15);
        $total = 0;
        foreach ($total_duration as $key => $item) {
            /**
             * 如果出现了跨天的，就是只算当天的
             */
            $item = (array)$item;
            $endTime = strtotime($item['start_time']) + ($item['duration']);
            $duration = $item['duration'];
            if ($endTime > strtotime($end)) {
                $endTime = strtotime($end);
                $duration = strtotime($end) - strtotime($item['start_time']);
            }
            if ($item['start_time'] <= $start) {
                $duration = $endTime - strtotime($start);
            }
            $total += $duration;
        }
        unset($total_duration);

        // $total = 0;
        foreach ($data as $key => $item2) {
            $item = (array)$item2;
            $result['list'][$key] = $item;

            $result['list'][$key]['created'] = $item['start_time'];
            /**
             * 如果开始时间是在前一天的
             */
            if ($item['start_time'] <= $start) {
                $result['list'][$key]['startTime'] = $start;
            } else {
                $result['list'][$key]['startTime'] = $item['start_time'];
            }

            /**
             * 如果出现了跨天的，就是只算当天的
             */
            $endTime = strtotime($item['start_time']) + ($item['duration']);
            $duration = $item['duration'];
            if ($endTime > strtotime($end)) {
                $endTime = strtotime($end);
                $duration = strtotime($end) - strtotime($item['start_time']);
            }
            if ($item['start_time'] <= $start) {
                $duration = $endTime - strtotime($start);
            }

            //   $total += $duration;
            $result['list'][$key]['endTime'] = date('Y-m-d H:i:s',
                $endTime);//date('Y-m-d H:i:s',strtotime($time1)+30*60);//注意引号内的大小写,分钟是i不是m
            $result['list'][$key]['duration'] = $this->_sec2time($duration);
        }
        $result['list'] = new LengthAwarePaginator($result['list'], $Count, 15, '',
            ['path' => '/member/live', 'query' => ['start' => $start, 'end' => $end]]);
        //$result['totalTime'] = $this->getTotalTime($uid, $end, $start);
        if (empty($result['list'])) {
            $result['list'] = [];
        }
        $result['totalTime'] = $this->_sec2time($total);
        return JsonResponse::create([
            'data' => $result,
        ]);
    }

    /**
     *含时长房间修改
     * @author TX
     * @update 2015.4.27
     * @return JsonResponse
     */
    public function roomUpdateDuration()
    {
        $start_time = $this->make('request')->get('mintime');
        $durationid = $this->make('request')->get('durationid');
        $hour = $this->make('request')->get('hour');
        $minute = $this->make('request')->get('minute');
        $duration = $this->make('request')->get('duration');
        $points = $this->make('request')->get('points');
        if (empty($durationid) || empty($start_time) || empty($duration) || empty($points)) {
            return new JsonResponse(['code' => 10, 'msg' => __('messages.request_error')]);
        }
        if (!in_array($duration, [25, 55])) {
            return new JsonResponse(['code' => 11, 'msg' => __('messages.request_error')]);
        }
        /** @var  $durationRoom \Video\ProjectBundle\Entity\VideoRoomDuration */
        $durationRoom = RoomDuration::find($durationid);
        if ($durationRoom->reuid != 0) {
            return new JsonResponse([
                'code' => 8,
                'msg'  => __('messages.Member.roomUpdateDuration.room_already_been_reserved')
            ]);
        }
        if (empty($durationRoom)) {
            return new JsonResponse(['code' => 12, 'msg' => __('messages.request_error')]);
        }
        $theday = date("Y-m-d H:i:s", mktime(23, 59, 59, date("m"), date("d") + 7, date("Y")));
        $start_time = date("Y-m-d H:i:s", strtotime($start_time . ' ' . $hour . ':' . $minute . ':00'));
        if ($theday < date("Y-m-d H:i:s", strtotime($start_time))) {
            return new JsonResponse(['code' => 5, 'msg' => __('messages.Member.roomUpdateDuration.set_max_limit')]);
        }
        if (date("Y-m-d H:i:s") > date("Y-m-d H:i:s", strtotime($start_time))) {
            return new JsonResponse(['code' => 6, 'msg' =>  __('messages.Member.roomUpdateDuration.set_min_limit')]);
        }
        $durationRoom->starttime = $start_time;
        $durationRoom->duration = $duration * 60;
        $durationRoom->points = $points;
        if ($this->checkRoomUnique($durationRoom)) {
            //$this->_data_model->updateByEntity('Video\ProjectBundle\Entity\VideoRoomDuration', array('id' => $durationid), array('starttime' => new \DateTime($start_time), 'duration' => $duration * 60, 'points' => $points));
            $durationRoom->save();
            $this->set_durationredis($durationRoom);
            return new JsonResponse(['code' => 1, 'msg' => __('messages.modified_successfully')]);
        } else {
            return new JsonResponse(['code' => 9, 'msg' => __('messages.Member.roomUpdateDuration.time_repeat')]);
        }

    }

    /**
     * 头像上传方法
     */
    public function avatarUpload()
    {
        $user = Auth::user();
        $result = resolve(SystemService::class)->upload($user ? $user->toArray() : []);

        if (isset($result['status']) && $result['status'] != 1) {
            return JsonResponse::create($result);
        }
        if (isset($result['ret']) && $result['ret'] === false) {
            return JsonResponse::create(['data' => $result]);
        }
        //更新用户头像
        Users::where('uid', $user['uid'])->update(['headimg' => $result['info']['md5']]);

        //更新用户redis
        resolve(UserService::class)->getUserReset($user['uid']);

        return JsonResponse::create(['data' => $result]);
    }

    /**
     * [flashUpload 相册上传方法]
     *
     * @author  dc <dc#wisdominfo.my>
     * @version 2015-11-20
     * @return JsonResponse
     */
    public function flashUpload()
    {
        $user = Auth::user();
        $result = resolve(SystemService::class)->upload($user ? $user->toArray() : []);

        if (isset($result['status']) && $result['status'] != 1) {
            return JsonResponse::create($result);
        }
        if (isset($result['ret']) && $result['ret'] === false) {
            return JsonResponse::create(['data' => $result]);
        }

        $anchor = Anchor::create([
            'uid' => Auth::id(),
            'file' => $result['info']['md5'],
            'size' => $result['info']['size'],
            'jointime' => time()
        ]);

        //更新用户空间
        Users::where('uid',
            $user['uid'])->update(['pic_used_size' => $user['pic_used_size'] + $result['info']['size']]);
        //更新用户redis
        resolve(UserService::class)->getUserReset($user['uid']);

        $result['info']['id'] = $anchor->id;
        return JsonResponse::create(['data' => $result]);
    }

    /**
     * 开通贵族
     * TODO 提炼到服务中 现阶段过渡期
     * <p>
     *  code 0 成功
     *      1002 贵族不可用
     *      102 钻石不够《全局通用状态》
     *      1004 同样等级
     *      1005 已开通或重复
     *      1003 系统失败
     * </p>
     */
    public function buyVip()
    {
        $msg = [
            'status' => 1,
            'msg' => __('messages.successfully_opened'),
        ];

        if (!Auth::user()) {
            $msg = [
                'status' => 101,
                'msg' => __('messages.not_logged_in'),
            ];
            return new JsonResponse($msg);
        }
        // 取到开通的贵族的数据 判断价格
        $gid = $this->request()->post('gid');
        // 默认的天数
        //        $day = $this->request()->get('day') ? $this->request()->get('day') : 30;
        $day = 30;
        // 如果在房间内 就会有roomid即为主播uid，默认为0不在房间开通， 用于佣金方面的问题
        $roomId = $this->request()->post('roomId') ? $this->request()->post('roomId') : 0;

        $user = DB::table('video_user')->where('uid', Auth::user()->uid)->first();

        // 用户组服务
        $userGroup = resolve(UserGroupService::class)->getGroupById($gid);


        if (!$userGroup || $userGroup['dml_flag'] == 3) {
            $msg['status'] = 1002;
            $msg['msg'] = __('messages.Member.buyVip.vip_status_error');
            return new JsonResponse($msg);
        }

        // 钱不够
        if ($userGroup['system']['open_money'] > $user->points) {
            $msg['status'] = 102;
            $msg['msg'] = __('messages.out_of_money');
            return new JsonResponse($msg);
        }

        // 已经开通了高等级的 不能再开通低等级的
        if ($userGroup['level_id'] == $user->vip) {
            $msg['status'] = 1004;
            $msg['msg'] = __('messages.Member.buyVip.same_vip_limit');
            return new JsonResponse($msg);
        }
        if ($userGroup['level_id'] < $user->vip) {
            $msg['status'] = 1005;
            $msg['msg'] = __('messages.Member.buyVip.buy_vip_limit');
            return new JsonResponse($msg);
        }

        // 如果价格够了，就直接开通用户组
        try {


            /**
             * 开启事务 牵涉到多个表的操作
             */
            DB::beginTransaction();
            /**
             * 首开礼包 先判断是否已经开通过了此贵族的 TODO 为了解决mysqlnd_ms 的bug问题 强制读主库
             */
            $isBuyThisGroup = DB::select('SELECT * FROM video_user_buy_group WHERE gid=?  AND uid=? AND  site_id=? LIMIT 1',
                [$gid, Auth::id(), SiteSer::siteId()]);

            $userService = resolve(UserService::class)->setUser(Users::find(Auth::id())); // 初始化用户服务
            $u['vip'] = $userGroup['level_id'];
            $exp = date('Y-m-d H:i:s', time() + $day * 24 * 60 * 60);
            // 用户的钻石 减去开通的价格
            $u['points'] = ($user->points - $userGroup['system']['open_money']); // 扣除的钻石
            $user->points = ($user->points - $userGroup['system']['open_money']); // 扣除的钻石
            $u['vip_end'] = $exp; // 过期时间
            $u['hidden'] = 0;
            DB::table('video_user')->where('uid', Auth::id())->update($u);
            // 首开礼包
            if (!$isBuyThisGroup && $userGroup['system']['gift_money']) {
                // 赠送 TODO 整合到用户的服务中去
                $us['points'] = ($user->points + $userGroup['system']['gift_money']);
                DB::table('video_user')->where('uid', Auth::id())->update($us);
                // 写入赠送日志
                $arr = [
                    'uid' => Auth::id(),
                    'created' => date('Y-m-d H:i:s'),
                    'points' => $userGroup['system']['gift_money'],
                    'paymoney' => round($userGroup['system']['gift_money'] / 10, 1),
                    'order_id' => time(),
                    'pay_type' => 5,//服务器送的钱pay_type=5
                    'pay_id' => null,
                    'pay_status' => Recharge::SUCCESS,
                    'nickname' => $user->nickname,
                    'site_id' => SiteSer::siteId(),
                ];
                DB::table('video_recharge')->insert($arr);
                // 赠送后 发送给用户通知消息
                $message = [
                    'mail_type' => 3,
                    'rec_uid' => $user->uid,
                    'content' => __('messages.Member.buyVip.first', [
                        'level_name' => __('messages.user.ViplevelName.' . $userGroup['vip']),
                        'gift_money' => $userGroup['system']['gift_money'],
                    ]),
                ];

                // $this->make('messageServer')->sendSystemToUsersMessage($message);
                $messageService = resolve(MessageService::class);
                $messageService->sendSystemToUsersMessage($message);
            }

            // 写入购买用户组的记录 user_buy_group
            $buyGroup = [
                'uid' => Auth::id(),
                'gid' => $gid,
                'create_at' => date('Y-m-d H:i:s'),
                'rid' => $roomId,
                'end_time' => $exp,
                'status' => 1,
                'open_money' => $userGroup['system']['open_money'],
                'keep_level' => $userGroup['system']['keep_level'],
                'site_id' => SiteSer::siteId(),
            ];
            DB::table('video_user_buy_group')->insertGetId($buyGroup);

            /**
             * 购买贵族后自动送坐骑
             * [添加用户背包判断再进行赠送]
             * @author  dc
             * @version 20151026
             */
            $userPack = Pack::whereUid(Auth::id())->whereGid($userGroup['mount'])->first();
            if (!$userPack) {
                DB::insert('INSERT INTO `video_pack` (uid, gid, expires, num, site_id) VALUES (?, ?, ?, ?, ?)',
                    [Auth::id(), $userGroup['mount'], strtotime($exp), 1, SiteSer::siteId()]);
                /*@todo 待检查为何这个方法插入失败*/
                //Pack::create(array('uid'=>Auth::id(),'gid'=>$userGroup['mount'],'expires'=>strtotime($exp),'num'=>1));
            }

            // 赠送爵位
            if ($userGroup['system']['gift_level']) {
                $userService->modLvRich($userGroup['system']['gift_level']);
            }

            // 开通成功后 发送给用户通知消息
            $message = [
                'category' => 2,
                'mail_type' => 3,
                'rec_uid' => $user->uid,
                'content' => __('Member.buyVip.pass', ['level_name' => $userGroup['level_name'], 'exp' => $exp]),
            ];
            // $this->make('messageServer')->sendSystemToUsersMessage($message);
            $messageService = resolve(MessageService::class);
            $messageService->sendSystemToUsersMessage($message);

            // 如果设置了房间属性 就给主播返现
            $casheback = 0;
            if ($roomId && DB::table('video_user')->where('uid', $roomId)->first()) {
                $commission = array(
                    'uid' => $roomId,
                    'r_uid' => Auth::id(),
                    'r_id' => $gid,
                    'type' => 'open_vip',
                    'title' => '房间内开通贵族返佣',
                    'points' => $userGroup['system']['host_money'],
                    'create_at' => date('Y-m-d H:i:s'),
                    'data' => serialize(array(
                        'gid' => $gid,
                        'vip_end' => $exp,
                    )),
                    'content' => $this->userInfo['nickname'] . '在您的房间' . date('Y-m-d H:i:s') . '开通了什么' . $userGroup['level_name'] . '，您得到' . $userGroup['system']['host_money'] . '佣金！',
                    'status' => 0,
                    'dml_flag' => 1,
                    'site_id' => SiteSer::siteId(),
                );
                DB::table('video_user_commission')->insertGetId($commission);
                $casheback = $userGroup['system']['host_money'];
            }

            // 更新用户redis中的信息
            $userService->getUserReset(Auth::id());

            //赠送完坐骑立即装备
            $redis = $this->make('redis');
            $redis->del('user_car:' . Auth::id());
            $redis->hset('user_car:' . Auth::id(), $userGroup['mount'], strtotime($exp));

            DB::commit();

        } catch (\Exception $e) {
            DB::rollback();
            //记录下日志
            /*      $logPath = base_path() . '/storage/logs/test_' . date('Y-m-d') . '.log';
                  $loginfo = date('Y-m-d H:i:s') . ' uid' . Auth::id() . "\n 购买贵族 事务结果: \n" . $e->getMessage() . "\n";
                  $this->logResult($loginfo, $logPath);*/
            Log::channel('daily')->info('开通贵族',
                [date('Y-m-d H:i:s') . ' uid' . Auth::id() . "\n 购买贵族 事务结果: \n" . $e->getMessage() . "\n"]);


            $msg['status'] = 1003;
            $msg['msg'] = __('messages.Member.buyVip.buy_vip_failed');
            return new JsonResponse($msg);
        }
        $msg['msg'] = __('messages.successfully_opened');
        $msg['data'] = [
            'uid' => Auth::id(),
            'roomid' => $roomId,
            'vip' => $userGroup['level_id'],
            'cashback' => intval($casheback),
            'name' => $user->nickname,
        ];

        return new JsonResponse($msg);
    }

    /**
     * 获取贵族的坐骑
     */
    public function getVipMount()
    {
        // 获取vip坐骑的id
        $mid = $this->make('request')->get('mid');
        $msg = [
            'status' => 0,
            'msg' => __('messages.opened_failed'),
        ];

        // 判断是否已经领过了
        $pack = Pack::where('uid', Auth::id())->where('gid', $mid)->first();
        if ($pack) {
            $msg['status'] = 1002;
            $msg['msg'] = __('messages.Member.getVipMount.already_have_this_ride');
            return new JsonResponse($msg);
        }

        // 判断是否是对应的贵族
        $userGroup = UserGroup::where('type', 'special')->where('mount', $mid)->first();

        if (!$userGroup) {
            $msg['status'] = 1005;
            $msg['msg'] = __('messages.Member.getVipMount.vip_only_ride');
            return new JsonResponse($msg);
        }

        if (Auth::user()->vip < $userGroup['level_id']) {
            $msg['status'] = 1003;
            $msg['msg'] = __('messages.Member.getVipMount.not_qualified_to_ride');
            return new JsonResponse($msg);
        }

        // 领取成功
        $pack = new Pack();
        $pack->uid = Auth::id();
        $pack->gid = $mid;
        $pack->num = 1;
        $pack->expires = strtotime(Auth::user()->vip_end);
        $res = $pack->save();

        if ($res !== false) {
            $msg['msg'] = __('messages.successfully_opened');
            $msg['status'] = 1;
            return new JsonResponse($msg);
        }

    }

    /**
     * 获取用户的钱 主要是用于在商城页面中购买商品的接口
     */
    public function getmoney()
    {
        return new JsonResponse(
            [
                'status' => 0,
                'data' => [
                    'nickname' => Auth::user()->nickname,
                    'money' => Auth::user()->points,
                ],
            ]
        );
    }

    /**
     * [hidden 用户在线、隐身接口]
     *
     * @author  dc <dc#wisdominfo.my>
     * @version 2015-11-11
     * @param   int $status 要设置的状态1=隐身，0=在线
     * @return JsonResponse
     */
    public function hidden($status)
    {
        if (!in_array($status, ['0', '1'])) {
            return new JsonResponse(['status' => 0, 'msg' => __('messages.request_error')]);
        }

        $uid = Auth::id();
        if (!$uid) {
            return new JsonResponse(['status' => 0, 'msg' => __('messages.unknown_user')]);
        }
        $user = Users::where('uid', $uid)->with('vipGroup')->first();

        //判断用户是否有隐身权限
        if (!resolve(UserService::class)->getUserHiddenPermission($user)) {
            return new JsonResponse(['status' => 0, 'msg' => __('messages.permission_denied')]);
        }

        //更新数据库隐身状态
        $hidden = Users::where('uid', $uid)->update(['hidden' => $status]);

        //更新用户redis
        resolve(UserService::class)->getUserReset($uid);

        return new JsonResponse(['status' => 1, 'msg' => __('messages.success')]);
    }


    /**
     * ajax 请求获取信息 TODO 策略
     *
     * @param $act
     * @return JsonResponse
     */
    public function ajax($act)
    {
        //        $act = $this->request()->get('action');
        if (($act != 'getfidinfo') && Auth::guest()) {
            return new RedirectResponse('/', 301);
        }
        //        $this->_initUser();
        $actions = [
            'userinfo' => 'editUserinfo',
            'attenionCancel' => 'attenionCancel',
            'getfidinfo' => 'getfidinfo',
            'delmsg' => 'delmsg',
            'equipHandle' => '_getEquipHandle',
        ];

        if ($act == 'userinfo') {
            $info = $this->$actions[$act]($this->request()->reques->all(), Auth::id());
            return new JsonResponse(json_encode($info));
        } elseif ($act == 'attenionCancel') {
            $this->$actions[$act](Auth::id(), $this->request()->get('tid'));
        } else {
            if ($act == 'getfidinfo') {
                $onlineId = $this->request()->getSession()->get(self::SEVER_SESS_ID);
                $uid = intval($this->request()->get('uid'));

                if ($uid == 0) {
                    return new JsonResponse(json_encode([
                        'status' => 0,
                        'msg'    => __('messages.Member.ajax.uid_empty')
                    ]));
                }
                $data = $this->{$actions[$act]}($this->request()->get('uid'));
                if (!$data) {
                    return new JsonResponse(['status' => 0, 'msg' => __('messages.Member.ajax.data_empty')]);
                } else {

                    $data = $this->getOutputUser($data, 80);
                    unset($data['safemail']);
                    if ($onlineId) {
                        //$data['checkatten'] = $this->_data_model->checkIsattenByuid($uid,$onlineId);
                        $data['checkatten'] = resolve(UserService::class)->checkFollow($onlineId, $uid) ? 1 : 0;
                    } else {
                        $data['checkatten'] = 0;
                    }
                    if ($data['roled'] == 3) {
                        $data['live_status'] = $this->make('redis')->hget('hvediosKtv:' . $data['uid'], 'status');
                    }
                    return new JsonResponse(['status' => 1, 'data' => $data]);
                }
            } else {
                if ($act == 'delmsg') {
                    $data = $this->$actions[$act](Auth::id());
                    //  return;
                } else {
                    if ($act == 'equipHandle') {
                        $this->$actions[$act]($this->request()->get('gid'));
                    }
                }
            }
        }
        return new JsonResponse(json_encode(['status' => 1, 'msg' => __('messages.Member.ajax.success')]));
    }

    /**
     * 付款
     */
    public function pay(Request $request)
    {
        $type = $request->get('type', 0);
        $gid = $request->get('gid');
        $nums = $request->get('num');
        if (!$this->buyGoods($type, $gid, $nums)) {
            $retData = [
                'msg' => __('messages.Member.pay.failed_to_buy'),
                'status' => 0,
            ];
        } else {
            //  $this->_getEquipHandle($this->get('request')->get('gid'));//分布
            $retData = [
                'msg' => __('messages.Member.pay.buy_successfully'),
                'status' => 1,
            ];
        }
        return JsonResponse::create($retData);
    }

    /**
     * 删除一对一房间
     */
    public function delRoomDuration()
    {

        $rid = $this->request()->input('rid');


        if (!$rid) {
            return JsonResponse::create(['status' => 0, 'msg' => __('messages.request_error')]);
        }
        $roomservice = resolve(RoomService::class);
        $result = $roomservice->delOnetoOne($rid);


        return JsonResponse::create($result);
    }

    /**
     * 删除一对多房间
     * @return JsonResponse|Response|static
     */
    public function delRoomOne2Many()
    {
        $rid = $this->request()->input('rid');

        if (!$rid) {
            return JsonResponse::create(['status' => 401, 'msg' => __('messages.request_error')]);
        }
        $room = RoomOneToMore::find($rid);
        if (!$room) {
            return new JsonResponse(['status' => 402, 'msg' => __('messages.Room.index.the_room_is_not_exist')]);
        }
        if ($room->uid != Auth::id()) {
            return JsonResponse::create(['status' => 404, 'msg' => __('messages.illegal_operation')]);
        }//只能删除自己房间
        if ($room->status == 1) {
            return new JsonResponse(['status' => 403, 'msg' => __('messages.Member.delRoomOne2Many.room_deleted')]);
        }
        if ($room->purchase()->exists()) {
            return new JsonResponse([
                'status' => 400,
                'msg'    => __('messages.Member.delRoomOne2Many.room_already_reserved')
            ]);
        }
        $redis = $this->make('redis');
        $redis->sRem('hroom_whitelist_key:' . $room->uid, $room->id);
        $redis->del('hroom_whitelist:' . $room->uid . ':' . $room->id);
        $room->update(['status' => 1]);
        return JsonResponse::create(['status' => 1, 'msg' => __('messages.Charge.del.success')]);
    }

    /**
     * 一对多补票接口
     */
    public function makeUpOneToMore()
    {
        $uid = Auth::id();
        $request = $this->request();
        $rid = (int) $request->input('rid');
        $origin = (int) $request->input('origin') ?: 12;
        if ($rid == $uid) {
            return JsonResponse::create(['status' => 0, 'msg' => __('messages.Member.makeUpOneToMore.limit')]);
        }
        $onetomany = (int) $request->input('onetomore');
        if (empty($onetomany) || empty($uid)) {
            return JsonResponse::create(['status' => 0, 'msg' => __('messages.Room.roommid.param_is_wrong')]);
        }
        /** @var \Redis $redis */
        $redis = $this->make('redis');
        $room = $redis->hgetall("hroom_whitelist:$rid:$onetomany");
        if (empty($room)) {
            return JsonResponse::create(['status' => 0, 'msg' => __('messages.Room.index.the_room_is_not_exist')]);
        }

        $points = $room['points'];
        if (resolve('one2more')->checkBuyUser($uid, $onetomany)) {
            return JsonResponse::create(['status' => 0, 'msg' => __('messages.Member.makeUpOneToMore.buy_ticket')]);
        }
        /** 检查余额 */
        $user = resolve(UserService::class)->getUserByUid($uid);

        /* 守護優惠判斷 */
        if (!empty($user['guard_id']) && time() < strtotime($user['guard_end'])) {
            $showDiscount = $user->guardianInfo->show_discount;
            $points = empty($showDiscount) ? $points : round($points * (100 - $showDiscount) / 100);
        }

        if ($user['points'] < $points) {
            return JsonResponse::create(['status' => 0, 'msg' => __('messages.out_of_money'), 'cmd' => 'topupTip']);
        }

        if ($this->isMobileUrl($request) && $redis->hGet("hvediosKtv:$rid", "status") == 0) {
            return JsonResponse::create(['status' => 0, 'msg' => __('messages.Member.makeUpOneToMore.anchor_offline')]);
        }

        /** 通知java送礼*/
        $redis->publish('makeUpOneToMore',
            json_encode([
                'rid' => $rid,
                'uid' => $uid,
                'onetomore' => $onetomany,
                'origin' => $origin,
                'site_id' => SiteSer::siteId(),
            ]));
        /** 检查购买状态 */
        $timeout = microtime(true) + 4;
        while (true) {
            if (microtime(true) > $timeout) {
                break;
            }
            $tickets = explode(',', $redis->hGet("hroom_whitelist:$rid:$onetomany", 'tickets'));
            if (in_array($uid, $tickets)) {
                return JsonResponse::create(['status' => 1, 'msg' => __('messages.Member.pay.buy_successfully')]);
            }
            usleep(200000);
        }
        return JsonResponse::create(['status' => 0, 'msg' => __('messages.Member.makeUpOneToMore.failed_to_buy')]);
    }

    public function buyModifyNickname()
    {
        $user = Auth::user();
        $uid = Auth::id();
        $price = SiteSer::config('nickname_price');
        $userService = resolve(UserService::class);
        if ($user['points'] < $price) {
            return JsonResponse::create([
                'status' => 0,
                'msg'    => __('messages.Member.buyModifyNickname.out_of_money', ['price' => $price])
            ]);
        }
        $num = $userService->getModNickNameStatus()['num'];
        if ($num >= 1) {
            return JsonResponse::create(['status' => 0, 'msg' => __('messages.Member.buyModifyNickname.qualified')]);
        }
        /** 扣钱给资格 */
        if (Users::where('uid', $uid)->where('points', '>=', $price)->decrement('points', $price)) {
            resolve(UserService::class)->cacheUserInfo($uid, null);
            Redis::hIncrBy('modify.nickname', $uid, 1);
            return JsonResponse::create(['status' => 1, 'msg' => __('messages.Member.pay.buy_successfully')]);
        } else {
            return JsonResponse::create(['status' => 0, 'msg' => __('messages.Member.buyModifyNickname.failed')]);
        }
    }

    public function gift()
    {
        $uid = Auth::id();
        if (!$uid) {
            throw new HttpResponseException(JsonResponse::create([
                'status' => 0,
                'msg'    => __('messages.not_logged_in')
            ]));
        }
        $type = $this->make('request')->get('type') ?: 'receive';
        $mint = $this->make('request')->get('mintime') ?: date('Y-m-d', strtotime('-1 day'));
        $maxt = $this->make('request')->get('maxtime') ?: date('Y-m-d');

        $mintime = date('Y-m-d H:i:s', strtotime($mint));
        $maxtime = date('Y-m-d H:i:s', strtotime($maxt . ' 23:59:59'));

        $selectTypeName = $type == 'send' ? 'send_uid' : 'rec_uid';
        $uriParammeters = $this->make('request')->query->all();
        $var['uri'] = [];
        foreach ($uriParammeters as $p => $v) {
            if (strstr($p, '?')) {
                continue;
            }
            if (!empty($v)) {
                $var['uri'][$p] = $v;
            }
        }

        $all_data = MallList::where($selectTypeName, $uid)
            ->where('created', '>=', $mintime)
            ->where('created', '<=', $maxtime)
            ->where('gid', '>', 10)
            ->paginate();

        $sum_gift_num = MallList::where($selectTypeName, $uid)
            ->where('created', '>=', $mintime)
            ->where('created', '<=', $maxtime)
            ->where('gid', '>', 10)
            ->sum('gnum');
        $sum_points_num = MallList::where($selectTypeName, $uid)
            ->where('created', '>=', $mintime)
            ->where('created', '<=', $maxtime)
            ->where('gid', '>', 10)
            ->sum('points');
        $sum_gift_num = $sum_gift_num ?: 0;
        $sum_points_num = $sum_points_num ?: 0;
        $all_data->appends(['type' => $type, 'mintime' => $mint, 'maxtime' => $maxt]);

        $var['type'] = $type;
        $var['data'] = $all_data;
        $var['mintime'] = $mint;
        $var['maxtime'] = $maxt;
        $var['sum_gift_num'] = $sum_gift_num;
        $var['sum_points_num'] = $sum_points_num;
        $var = $this->format_jsoncode($var);
        return new JsonResponse(($var));

    }

    /*
     * 礼物统计
     */

    /**添加用户到一对多
     * @param     $onetomany
     * @param     $uid
     * @param int $origin
     * @param int $points 0后台添加，-1使用房间价格全额
     * @return array
     */
    protected function addOneToManyRoomUser($rid, $onetomany, $uid, $origin = 13, $points = 0)
    {
        /** @var \Redis $redis */
        $redis = $this->make('redis');
        if (empty($onetomany) || empty($uid)) {
            return [0, __('messages.Room.roommid.param_is_wrong')];
        }
        $room = $redis->hgetall("hroom_whitelist:$rid:$onetomany");
        if (empty($room)) {
            return [0, __('messages.Room.index.the_room_is_not_exist')];
        }
        if ($rid == $uid) {
            return [0, __('messages.Member.addOneToManyRoomUser.purchase_limit')];
        }//不能添加主播自己
        if (strtotime($room['endtime']) < time()) {
            return [0, __('messages.Member.addOneToManyRoomUser.end')];
        }//房间已经结束

        if (in_array($uid, explode(',', $room['uids']))) {
            return [0, __('messages.Member.makeUpOneToMore.buy_ticket')];
        }
        if (UserBuyOneToMore::query()->where('onetomore', $onetomany)->where('uid', $uid)->first()) {
            return [0, __('messages.Member.makeUpOneToMore.buy_ticket')];
        }//redis数据可能出错
        $redis->hIncrBy("hroom_whitelist:$rid:$onetomany", 'nums', 1);//房间人数+1
        $room['nums']++;
        RoomOneToMore::find($onetomany)->increment('tickets', 1);
        if ($points = -1) {//points -1 表示房间价格全额
            $points = $room['points'];
        }
        $buy_item = [
            'rid' => $room['uid'],
            'onetomore' => $onetomany,
            'uid' => $uid,
            'type' => 2,
            'created' => date('Y-m-d H:i:s'),
            'starttime' => $room['starttime'],
            'endtime' => $room['endtime'],
            'duration' => ($duration = strtotime($room['endtime']) - strtotime($room['starttime'])),
            'points' => $points,
            'origin' => $origin,
        ];
        $buy = UserBuyOneToMore::create($buy_item);
        $auto_id = $buy->id;
        $redis = $this->make('redis');
        $redis->hmset('hbuy_one_to_more:' . $rid . ':' . $auto_id, $buy->toArray());
        $redis->expire('hbuy_one_to_more:' . $rid . ':' . $auto_id, $duration + 86400);

        //添加白名单
        $uids = $redis->hget('hroom_whitelist:' . $rid . ':' . $onetomany, 'uids');
        if ($uids) {
            if (!in_array($uid, explode(',', $uids))) {
                $uids .= ',' . $uid;
            }
        } else {
            $uids = $uid;
        }
        $redis->hmset('hroom_whitelist:' . $rid . ':' . $onetomany
            , [
                'uids' => $uids,
            ]);
        return [
            1,
            __('messages.Member.addOneToManyRoomUser.success'),
            'data' =>
                ['points' => $points],
        ];
    }


    /**
     * 用户中心 密码管理
     * @name password
     * @author D.C
     * @version 1.0
     * @return JsonResponse
     */
    public function password()
    {
        $uid = Auth::id();
        $user = Auth::user();
        if ($this->make('request')->getMethod() === 'POST') {
            $post = $this->make('request')->request->all();
            $post['password'] = isset($post['password']) ? $post['password'] : '';
            $post['trade_password'] = isset($post['trade_password']) ? $post['trade_password'] : '';

            /**
             * 设置交易密码
             */
            if (isset($post['type']) && $post['type'] == 'trade_password') {

                if (isset(Auth::user()->trade_password) && strlen(Auth::user()->trade_password) == 32) {
                    return new JsonResponse(array(
                        'status' => 303,
                        'msg'    => __('messages.Member.password.already_set_trade_pwd')
                    ));
                }

                if (!resolve(UserService::class)->checkUserPassword($uid, $post['password'])) {
                    return new JsonResponse(array(
                        'status' => 302,
                        'msg'    => __('messages.Member.password.incorrect_pwd')
                    ));
                }

                if ($post['trade_password']) {
                    if (resolve(UserService::class)->updateUserTradePassword($uid, $post['trade_password'])) {
                        return new JsonResponse(array(
                            'status' => 0,
                            'msg'    => __('messages.Member.password.setting_pwd_success')
                        ));
                    }
                    return new JsonResponse(array(
                        'status' => 301,
                        'msg'    => __('messages.Member.password.setting_pwd_failed')
                    ));
                } else {
                    return new JsonResponse(array('status' => 300, 'msg' => __('messages.Member.password.pwd_empty')));
                }

            }

            if (empty($post['password'])) {
                return new JsonResponse(array(
                    'status' => 301,
                    'msg'    => __('messages.Mobile.passwordChange.old_password_required')
                ));
            }

            if (strlen($post['password1']) < 6 || strlen($post['password2']) < 6) {
                return new JsonResponse(array(
                    'status' => 302,
                    'msg'    => __('messages.Mobile.passwordChange.old_password_required')
                ));
            }

            if ($post['password1'] != $post['password2']) {
                return new JsonResponse(array(
                    'status' => 303,
                    'msg'    => __('messages.Mobile.passwordChange.new_password_is_not_the_same')
                ));
            }

            $old_password = resolve(UserService::class)->getUserInfo($uid, 'password');
            $new_password = md5($post['password2']);
            if (md5($post['password']) != $old_password) {
                return new JsonResponse(array(
                    'status' => status,
                    'msg'    => __('messages.Mobile.passwordChange.old_password_is_wrong')
                ));
            }
            if ($old_password == $new_password) {
                return new JsonResponse(array(
                    'status' => status,
                    'msg'    => __('messages.Mobile.passwordChange.new_and_old_is_the_same')
                ));
            }

            $user = Users::find($uid);
            $user->password = $new_password;
            if (!$user->save()) {
                return new JsonResponse(array(
                    'status' => 304,
                    'msg'    => __('messages.Mobile.passwordChange.modify_failed')
                ));
            }
            /*  $this->_clearCookie();
              $this->_reqSession->invalidate();//销毁session,生成新的sesssID*/

            if (Auth::check()) {
                Auth::logout();
            }
            $request->session()->invalidate();
            //更新用户redis
            resolve(UserService::class)->getUserReset($uid);
            //  $this->make('userServer')->getUserReset($uid);

            return new JsonResponse(array(
                'status' => 1,
                'msg'    => __('messages.Member.passwordChange.successfully_modified')
            ));
        }

        return new JsonResponse(['msg' => 0, 'data' => ['user' => $user]]);
    }

    /**
     * 用户中心 联系信息管理
     * @name contact
     * @author D.C
     * @version 1.0
     * @return JsonResponse
     */
    public function contact()
    {
        //$uid = Auth::id();
        //$user = Auth::user();

        $flashVer = SiteSer::config('publish_version');
        !$flashVer && $flashVer = 'v201504092044';
        $data = [];

        $device = Input::get('device', 1);
        if ($device == 2 || $device == 4) {
            $msg_contact = Redis::hGet('hsite_config:' . SiteSer::siteId(), 'mobile_contact');
            $qr_path = '/api/m/';
        } else {
            $msg_contact = Redis::hGet('hsite_config:' . SiteSer::siteId(), 'pc_contact');
            $qr_path = '/api/';
        }

        $orders = $this->_getOrders();

        $list = Redis::get('home_all_' . $flashVer. ':'.SiteSer::siteId());
        $list = str_replace(['cb(', ');'], ['', ''], $list);
        $J_list = json_decode($list, true);

        foreach ($J_list['rooms'] as $O_list) {
            if ($O_list['live_status'] <= 0) {
                continue;
            }
            // get user data
            $userex = Usersall::select('uid', 'nickname as nick', 'headimg', 'headimg_sagent', 'qrcode_image')
                ->where('transfer', 1)->where('qrcode_image', '<>', '')->find($O_list['uid']);

            if (empty($userex)) {
                continue;
            }

            // headimg_sagent first
            if (!empty($userex['headimg_sagent'])) {
                $userex['headimg'] = $userex['headimg_sagent'] . '.jpg';
            }
            unset($userex['headimg_sagent']);

            // qrcode
            if (strpos($userex['qrcode_image'], '.png') > 0) {
                $A_qr = explode('#', $userex['qrcode_image']);
                $userex['qrcode_image'] = $A_qr[0];
            } else {
                $A_qr = explode('#', $userex['qrcode_image']);
                if ($A_qr[1] == 1) {
                    $userex['url'] = $A_qr[0];
                    unset($userex['qrcode_image']);
                } else {
                    $userex['qrcode_image'] = $qr_path.'contact/qr.png?url='.$A_qr[0];
                }
            }

            // order from redis
            if (isset($orders[$O_list['uid']])) {
                $userex['order'] = $orders[$O_list['uid']];
            } else {
                $userex['order'] = 9999;
            }
            array_push($data, $userex);
        }
        usort($data, [$this, '_compareSAgent']);

        return new JsonResponse(['msg' => 0, 'data' => ['live'=>$data,'info'=>$msg_contact]]);
    }

    private function _getOrders()
    {
        $orders = [];
        $sagentOrder = Redis::get('sagentorder:1'); // use v1 only
        if ($sagentOrder) {
            $sagentOrder = json_decode($sagentOrder);
            foreach ($sagentOrder as $value) {
                list($order, $uid) = explode('_', $value);
                $orders[$uid] = intval($order);
            }
        }
        return $orders;
    }
    private function _compareSAgent($a1, $a2)
    {
        return $a1['order'] < $a2['order'] ? -1 : 1;
    }

    public function modifyMobileSend(Request $req)
    {
        $cc = $req->post('cc');
        $mobile = $req->post('mobile');

        // input validation
        if (empty($cc) || empty($mobile)) {
            return $this->msg('Invalid request');
        }
        $mobile = PhoneNumber::formatMobile($cc, $mobile);

        // check reg mobile exists
        $cc_mobile = $cc.$mobile;
        $site_id = SiteSer::siteId();
        $exists = Users::where('cc_mobile', $cc_mobile)->where('site_id', $site_id)->exists();
        if ($exists) {
            return $this->msg(__('messages.Api.reg.mobile_is_used'));
        }

        $act = SmsService::ACT_MODIFY_MOBILE;
        $result = SmsService::send($act, $cc, $mobile);
        if ($result !== true) {
            return $this->msg($result);
        }
        return $this->msg(__('messages.Sms.send.send_success'), 1);
    }

    public function modifyMobileConfirm(Request $req)
    {
        $cc = $req->post('cc');
        $mobile = $req->post('mobile');
        $code = $req->post('code', '');
        if (empty($cc) || empty($mobile) || empty($code)) {
            return $this->msg('Invalid request');
        }
        $mobile = PhoneNumber::formatMobile($cc, $mobile);

        $result = SmsService::verify(SmsService::ACT_MODIFY_MOBILE, $cc, $mobile, $code);
        if ($result !== true) {
            return $this->msg($result);
        }

        $userService = resolve(UserService::class);
        $userService->modifyMobile(Auth::user(), $cc.$mobile);

        return $this->msg(__('messages.modified_successfully'), 1);
    }

    /**
     * @api {get} /api/m/member/redEnvelopeGet 紅包明細 - 收入 (Mobile)
     * @apiGroup Member
     * @apiName m_redEnvelopeGet
     * @apiVersion 1.3.0
     */
    /**
     * @api {get} /api/member/redEnvelopeGet 紅包明細 - 收入
     * @apiGroup Member
     * @apiName redEnvelopeGet
     * @apiVersion 1.3.0
     *
     * @apiHeader (Mobile Header) {String} Authorization Mobile 須帶入 JWT Token
     * @apiHeader (Web Header) {String} Cookie Web 須帶入登入後的 SESSID
     *
     * @apiParam {String} mintime 起日
     * @apiParam {String} maxtime 迄日
     * @apiParam {int} page 第幾頁
     *
     * @apiSuccess {Object} data
     * @apiSuccess {Object[]} data.data
     * @apiSuccess {String} data.data.create_date 時間
     * @apiSuccess {String} data.data.snickname 發放者暱稱
     * @apiSuccess {String} data.data.rnickname 直播间
     * @apiSuccess {String} data.data.points 获得钻石
     * @apiSuccess {String} data.total_points 總共鑽石數
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *    {
     *        "status": 1,
     *        "data": {
     *            "uri": [],
     *            "list": {
     *            "current_page": 1,
     *            "data": [
     *                {
     *                  "create_date": "2019-09-04 03:00:00",
     *                  "snickname": "Black331169",
     *                  "rnickname": "hunw",
     *                  "points": 32
     *                }
     *            ],
     *            "first_page_url": "http://localhost:8002/api/redEnvelopeGet?page=1",
     *            "from": 1,
     *            "last_page": 2,
     *            "last_page_url": "http://localhost:8002/api/redEnvelopeGet?page=2",
     *            "next_page_url": "http://localhost:8002/api/redEnvelopeGet?page=2",
     *            "path": "http://localhost:8002/api/redEnvelopeGet",
     *            "per_page": 15,
     *            "prev_page_url": null,
     *            "to": 15,
     *            "total": 28
     *            },
     *            "mintime": "2019-09-03",
     *            "maxtime": "2019-09-04",
     *            "total_points" : 32
     *        },
     *        "msg": "获取成功"
     *    }
     */
    public function redEnvelopeGet(Request $req)
    {
        $uid = Auth::id();
        if (!$uid) {
            throw new HttpResponseException(JsonResponse::create([
                'status' => 0,
                'msg'    => __('messages.not_logged_in')
            ]));
        }

        $mint = $req->get('mintime') ?: date('Y-m-d', strtotime('-1 day'));
        $maxt = $req->get('maxtime') ?: date('Y-m-d');
        $mintime = date('Y-m-d H:i:s', strtotime($mint));
        $maxtime = date('Y-m-d H:i:s', strtotime($maxt . ' 23:59:59'));

        $uriParammeters = $req->query->all();
        $var['uri'] = [];
        foreach ($uriParammeters as $p => $v) {
            if (strstr($p, '?')) {
                continue;
            }
            if (!empty($v)) {
                $var['uri'][$p] = $v;
            }
        }

        $query = RedEnvelopeGet::select(
            DB::raw("DATE_FORMAT(video_get_red_envelope_record.create_date, '%Y-%m-%d %H:%i:%s') as create_date"),
            'u2.nickname AS snickname',
            'u1.nickname AS rnickname',
            'video_get_red_envelope_record.points'
        )
            ->leftJoin('video_user as u1', function ($query) {
                $query->on('u1.uid', '=', 'video_get_red_envelope_record.room_id');
            })
            ->leftJoin('video_user as u2', function ($query) {
                $query->on('u2.uid', '=', 'video_get_red_envelope_record.send_id');
            })
            ->where('user_id', $uid)
            ->where('video_get_red_envelope_record.create_date', '>=', $mintime)
            ->where('video_get_red_envelope_record.create_date', '<=', $maxtime)
            ->orderBy('video_get_red_envelope_record.create_date', 'desc')
            ->allSites();

        /* 計算總共鑽石數 */
        $var['total_points'] = $query->sum('video_get_red_envelope_record.points');

        $all_data = $query->paginate();

        $all_data->appends(['mintime' => $mint, 'maxtime' => $maxt]);
        $var['list'] = $all_data;
        $var['mintime'] = $mint;
        $var['maxtime'] = $maxt;

        $var = $this->format_jsoncode($var);
        return new JsonResponse($var);
    }

    /**
     * @api {get} /api/m/member/redEnvelopeSend 紅包明細 - 支出 (Mobile)
     * @apiGroup Member
     * @apiName m_redEnvelopeSend
     * @apiVersion 1.3.0
     */
    /**
     * @api {get} /api/member/redEnvelopeSend 紅包明細 - 支出
     * @apiGroup Member
     * @apiName redEnvelopeSend
     * @apiVersion 1.3.0
     *
     * @apiHeader (Mobile Header) {String} Authorization Mobile 須帶入 JWT Token
     * @apiHeader (Web Header) {String} Cookie Web 須帶入登入後的 SESSID
     *
     * @apiParam {String} mintime 起日
     * @apiParam {String} maxtime 迄日
     * @apiParam {int} page 第幾頁
     *
     * @apiSuccess {Object} data
     * @apiSuccess {Object[]} data.data
     * @apiSuccess {String} data.data.create_date 時間
     * @apiSuccess {String} data.data.rnickname 直播间
     * @apiSuccess {int} data.data.total_point 發放總數
     * @apiSuccess {int} data.data.tax_point 稅
     * @apiSuccess {int} data.data.return_point 退钻
     * @apiSuccess {int} data.data.point 總支出
     * @apiSuccess {String} data.data.status 狀態
     * @apiSuccess {Int} data.total_points 總共鑽石數
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *    {
     *        "status": 1,
     *        "data": {
     *            "uri": [],
     *            "list": {
     *            "current_page": 1,
     *            "data": [
     *                {
     *                  "create_date": "2019-09-04 00:55:49",
     *                  "rnickname": "hunw",
     *                  "total_point": 500,
     *                  "tax_point": 25,
     *                  "return_point": 150,
     *                  "point": 375,
     *                  "status": "已结束"
     *                }
     *            ],
     *            "first_page_url": "http://localhost:8002/api/redEnvelopeSend?page=1",
     *            "from": 1,
     *            "last_page": 2,
     *            "last_page_url": "http://localhost:8002/api/redEnvelopeSend?page=2",
     *            "next_page_url": "http://localhost:8002/api/redEnvelopeSend?page=2",
     *            "path": "http://localhost:8002/api/redEnvelopeSend",
     *            "per_page": 15,
     *            "prev_page_url": null,
     *            "to": 15,
     *            "total": 28
     *            },
     *            "mintime": "2019-09-03",
     *            "maxtime": "2019-09-04",
     *            "total_point": 375
     *        },
     *        "msg": "获取成功"
     *    }
     */
    public function redEnvelopeSend(Request $req)
    {
        $uid = Auth::id();
        if (!$uid) {
            throw new HttpResponseException(JsonResponse::create([
                'status' => 0,
                'msg'    => __('messages.not_logged_in')
            ]));
        }

        $mint = $req->get('mintime') ?: date('Y-m-d', strtotime('-1 day'));
        $maxt = $req->get('maxtime') ?: date('Y-m-d');
        $mintime = date('Y-m-d H:i:s', strtotime($mint));
        $maxtime = date('Y-m-d H:i:s', strtotime($maxt . ' 23:59:59'));

        $uriParammeters = $req->query->all();
        $var['uri'] = [];
        foreach ($uriParammeters as $p => $v) {
            if (strstr($p, '?')) {
                continue;
            }
            if (!empty($v)) {
                $var['uri'][$p] = $v;
            }
        }

        $query = RedEnvelopeSend::select(
            DB::raw("DATE_FORMAT(video_send_red_envelope_record.create_date, '%Y-%m-%d %H:%i:%s') as create_date"),
            'u1.nickname AS rnickname',
            'total_point',
            'tax_point',
            'return_point',
            'delay_time',
            'valid_time',
            DB::raw('UNIX_TIMESTAMP(create_date) as create_time'),
            DB::raw('total_point + tax_point - return_point as point'),
            'video_send_red_envelope_record.status'
        )
            ->leftJoin('video_user as u1', function ($query) {
                $query->on('u1.uid', '=', 'video_send_red_envelope_record.room_id');
            })
            ->where('send_id', $uid)
            ->where('video_send_red_envelope_record.create_date', '>=', $mintime)
            ->where('video_send_red_envelope_record.create_date', '<=', $maxtime)
            ->orderBy('video_send_red_envelope_record.create_date', 'desc')
            ->allSites();

        /* 計算總共鑽石數 */
        $var['total_points'] = $query->sum(DB::raw('total_point + tax_point - return_point'));

        $all_data = $query->paginate();

        foreach ($all_data as &$row) {
            switch ($row->status) {
                case 0:
                    $row->status = __('messages.Member.redEnvelopeSend.send_later');
                    break;

                case 1:
                    $expired = (time() > ($row->create_time + $row->delay_time + $row->valid_time));
                    $row->status = $expired ? __('messages.Member.redEnvelopeSend.wait_for_refund') :
                        __('messages.Member.redEnvelopeSend.is_sending');
                    break;

                default:
                    $row->status = __('messages.Member.redEnvelopeSend.done');
            }
            unset($row->create_time);
            unset($row->delay_time);
            unset($row->valid_time);
        }

        $all_data->appends(['mintime' => $mint, 'maxtime' => $maxt]);
        $var['list'] = $all_data;
        $var['mintime'] = $mint;
        $var['maxtime'] = $maxt;

        $var = $this->format_jsoncode($var);
        return new JsonResponse($var);
    }

    /**
     * @api {get} /api/m/member/signin 用戶簽到資訊 (Mobile)
     * @apiGroup Member
     * @apiName m_signin
     * @apiVersion 2.5.0
     */
    /**
     * @api {get} /api/member/signin 用戶簽到資訊 (PC)
     * @apiGroup Member
     * @apiName signin
     * @apiVersion 2.5.0
     *
     * @apiHeader (Mobile Header) {String} Authorization Mobile 須帶入 JWT Token
     * @apiHeader (Web Header) {String} Cookie Web 須帶入登入後的 SESSID
     *
     * @apiParam {String} clientDate 客戶端日期，範例:<code>2019-10-01</code>
     *
     * @apiSuccess {int} status 狀態<br>
     *                   <code>0</code>: 錯誤<br>
     *                   <code>1</code>: 正常
     * @apiSuccess {Object} data
     * @apiSuccess {int} data.days 連續簽到天數
     * @apiSuccess {int} data.today 當天是否已簽到<br>
     *                              <code>0</code>: 未簽到<br>
     *                              <code>1</code>: 已簽到
     * @apiSuccess {String} msg 錯誤訊息
     *
     * @apiSuccessExample 正常回應
     * {
     *     "status": 1,
     *     "data": {
     *         "days": 0,
     *         "today": 0
     *     }
     * }
     * @apiSuccessExample 沒開放
     * {
     *     "status": 0,
     *     "msg": "签到功能已关闭"
     * }
     *
     */

    /**
     * @api {post} /api/m/member/signin 執行用戶簽到 (Mobile)
     * @apiGroup Member
     * @apiName post_m_signin
     * @apiVersion 2.5.0
     */
    /**
     * @api {post} /api/member/signin 執行用戶簽到 (PC)
     * @apiGroup Member
     * @apiName post_signin
     * @apiVersion 2.5.0
     *
     * @apiHeader (Mobile Header) {String} Authorization Mobile 須帶入 JWT Token
     * @apiHeader (Web Header) {String} Cookie Web 須帶入登入後的 SESSID
     *
     * @apiParam {String} clientDate 客戶端日期，範例:<code>2019-10-01</code>
     *
     * @apiSuccess {int} status 狀態<br>
     *                   <code>0</code>: 錯誤<br>
     *                   <code>1</code>: 正常
     * @apiSuccess {Object} data
     * @apiSuccess {int} data.points 取得點數
     * @apiSuccess {String} msg 錯誤訊息
     *
     * @apiSuccessExample 正常回應
     * {
     *     "status": 1,
     *     "data": {
     *         "points": 1
     *     }
     * }
     * @apiSuccessExample 沒開放
     * {
     *     "status": -1,
     *     "msg": "签到功能已关闭"
     * }
     * @apiSuccessExample 已簽到
     * {
     *     "status": 0,
     *     "msg": "今日已签到"
     * }
     * @apiSuccessExample 日期錯誤
     * {
     *     "status": 0,
     *     "msg": "日期错误，请检查您的系统日期"
     * }
     *
     */
    public function signin(Request $req)
    {
        $uid = Auth::id();
        if (!$uid) {
            throw new HttpResponseException(JsonResponse::create([
                'status' => 0,
                'msg'    => __('messages.not_logged_in')
            ]));
        }

        $signinService = resolve(SigninService::class);
        $clientDate = $req->get('clientDate');
        if (empty($clientDate)) {
            throw new HttpResponseException(JsonResponse::create(['status' => 0, 'msg' => 'clientDate']));
        }
        $clientDate = date('Y-m-d', strtotime($clientDate));

        if ($req->isMethod('post') || $req->get('postsign')) {
            $rtn = $signinService->sign($uid, $clientDate);
        } else {
            $rtn = $signinService->get($uid, $clientDate);
        }
        if ($rtn['status'] <= 0) {
            throw new HttpResponseException(JsonResponse::create($rtn));
        }
        return new JsonResponse($rtn);
    }

    /**
     * @api {get} /api/m/member/roomInfo 取得當前房間暱稱 (Mobile)
     * @apiGroup Member
     * @apiName m_roomInfo
     * @apiVersion 2.8.0
     */
    /**
     * @api {get} /api/member/roomInfo 取得當前房間暱稱 (PC)
     * @apiGroup Member
     * @apiName roomInfo
     * @apiVersion 2.8.0
     *
     * @apiHeader (Mobile Header) {String} Authorization Mobile 須帶入 JWT Token
     * @apiHeader (Web Header) {String} Cookie Web 須帶入登入後的 SESSID
     *
     * @apiSuccess {int} status 狀態<br>
     *                   <code>0</code>: 錯誤<br>
     *                   <code>1</code>: 正常
     * @apiSuccess {Object} data
     * @apiSuccess {int} data.room_info 房間暱稱
     * @apiSuccess {String} msg 錯誤訊息
     *
     * @apiSuccessExample 正常回應
     * {
     *     "status": 1,
     *     "data": {
     *         "room_info": "我的房間暱稱"
     *     }
     * }
     *
     */

    /**
     * @api {post} /api/m/member/roomInfo 修改當前房間暱稱 (Mobile)
     * @apiGroup Member
     * @apiName post_m_roomInfo
     * @apiVersion 2.8.0
     */
    /**
     * @api {post} /api/member/roomInfo 修改當前房間暱稱 (PC)
     * @apiGroup Member
     * @apiName post_roomInfo
     * @apiVersion 2.8.0
     *
     * @apiHeader (Mobile Header) {String} Authorization Mobile 須帶入 JWT Token
     * @apiHeader (Web Header) {String} Cookie Web 須帶入登入後的 SESSID
     *
     * @apiParam {String} roomInfo 房間暱稱，範例: 我的房間暱稱
     * @apiParam {String} feature 特色標籤
     * @apiParam {String} content 內容標籤
     *
     * @apiSuccess {int} status 狀態<br>
     *                   <code>0</code>: 錯誤<br>
     *                   <code>1</code>: 正常
     * @apiSuccess {String} msg 錯誤訊息
     *
     * @apiSuccessExample 正常回應
     * {
     *     "status": 1
     * }
     * @apiSuccessExample 10个字
     * {
     *     "status": -1,
     *     "msg": "最多10个字"
     * }
     *
     */
    public function roomInfo(Request $req)
    {
        $rid = Auth::id();
        if (!$rid) {
            throw new HttpResponseException(JsonResponse::create([
                'status' => 0,
                'msg'    => __('messages.not_logged_in')
            ]));
        }

        $user = Auth::user();
        if ($user['roled'] != 3) {
            throw new HttpResponseException(JsonResponse::create([
                'status' => 0,
                'msg'    => __('messages.Member.roomInfo.anchor_only')
            ]));
        }

        $roomService = resolve('roomService');
        if ($req->isMethod('post') || $req->get('postRoomInfo')) {
            $rtn = $roomService->setInfo(
                $rid,
                $req->get('roomInfo'),
                $req->get('feature'),
                $req->get('content')
            );

            if ($rtn) {
                $this->setStatus(1, 'OK');
            } else {
                $this->setStatus(-1, __('messages.Member.roomInfo.string_length_limit'));
            }
        } else {
            $rtn = $roomService->getInfo($rid);
            $this->setStatus(1, 'OK');
            $this->setRootData('data', $rtn);
        }

        return $this->jsonOutput();
    }

    /**
     * @api {get} /member/getUserLevelInfo 取得用戶等級資訊
     *
     * @apiDescription mobile版URL前綴: /api/m
     *
     * pc版URL前綴: /api
     * @apiGroup Member
     * @apiName getUserLevelInfo
     * @apiVersion 1.0.0
     *
     * @apiHeader (Mobile Header) {String} Authorization Mobile 須帶入 JWT Token
     * @apiHeader (Web Header) {String} Cookie Web 須帶入登入後的 SESSID
     *
     * @apiSuccess {Int]} status 執行狀態(1為成功)
     * @apiSuccess {String} msg 回傳訊息
     * @apiSuccess {Object} data
     * @apiSuccess {Array} data.userLevelInfo 用戶等級資訊
     * @apiSuccess {Int} data.userLevelInfo.lv_sub 當前已獲得的經驗值
     * @apiSuccess {Int} data.userLevelInfo.lv_next_exp 夏衣等級所需的經驗值
     * @apiSuccess {Int} data.userLevelInfo.lv_current_exp 當前等級所需的經驗值
     * @apiSuccess {Int} data.userLevelInfo.lv_percent 當前等級的經驗百分比
     * @apiSuccess {Int} data.userLevelInfo.uid 用戶uid
     * @apiSuccess {Int} data.userLevelInfo.lv_rich 用戶當前的財富等級
     * @apiSuccess {Int} data.userGroup 用戶組資訊
     * @apiSuccess {Int} data.userGroup.gid 用戶組ID
     * @apiSuccess {Int} data.userGroup.level_id 等級ID
     * @apiSuccess {String} data.userGroup.level_name 等級名稱
     * @apiSuccess {Int} data.userGroup.chat_limit 聊天文字長度限制
     *
     * @apiSuccessExample {json} 成功回應
    {
    "status": 1,
    "msg": "OK",
    "data": {
    "userLevelInfo": {
    "lv_sub": 38229,
    "lv_next_exp": 113000,
    "lv_current_exp": 63000,
    "lv_percent": 76.458,
    "uid": 9493715,
    "lv_rich": 8
    },
    "userGroup": [
    {
    "gid": 2,
    "level_id": 2,
    "level_name": "屌丝",
    "chat_limit": 1
    },
    {
    "gid": 3,
    "level_id": 3,
    "level_name": "一富",
    "chat_limit": 5
    },
    {
    "gid": 4,
    "level_id": 4,
    "level_name": "二富",
    "chat_limit": 5
    },
    {
    "gid": 5,
    "level_id": 5,
    "level_name": "三富",
    "chat_limit": 5
    },
    {
    "gid": 6,
    "level_id": 6,
    "level_name": "四富",
    "chat_limit": 5
    },
    {
    "gid": 7,
    "level_id": 7,
    "level_name": "五富",
    "chat_limit": 5
    },
    {
    "gid": 8,
    "level_id": 8,
    "level_name": "六富",
    "chat_limit": 5
    },
    {
    "gid": 9,
    "level_id": 9,
    "level_name": "七富",
    "chat_limit": 8
    },
    {
    "gid": 10,
    "level_id": 10,
    "level_name": "八富",
    "chat_limit": 8
    },
    {
    "gid": 11,
    "level_id": 11,
    "level_name": "九富",
    "chat_limit": 8
    },
    {
    "gid": 12,
    "level_id": 12,
    "level_name": "十富",
    "chat_limit": 8
    },
    {
    "gid": 13,
    "level_id": 13,
    "level_name": "男爵",
    "chat_limit": 10
    },
    {
    "gid": 14,
    "level_id": 14,
    "level_name": "子爵",
    "chat_limit": 10
    },
    {
    "gid": 15,
    "level_id": 15,
    "level_name": "伯爵",
    "chat_limit": 10
    },
    {
    "gid": 16,
    "level_id": 16,
    "level_name": "候爵",
    "chat_limit": 10
    },
    {
    "gid": 17,
    "level_id": 17,
    "level_name": "公爵",
    "chat_limit": 10
    },
    {
    "gid": 18,
    "level_id": 18,
    "level_name": "郡公",
    "chat_limit": 10
    },
    {
    "gid": 19,
    "level_id": 19,
    "level_name": "国公",
    "chat_limit": 10
    },
    {
    "gid": 20,
    "level_id": 20,
    "level_name": "王爵",
    "chat_limit": 10
    },
    {
    "gid": 21,
    "level_id": 21,
    "level_name": "藩王",
    "chat_limit": 15
    },
    {
    "gid": 22,
    "level_id": 22,
    "level_name": "郡王",
    "chat_limit": 15
    },
    {
    "gid": 23,
    "level_id": 23,
    "level_name": "亲王",
    "chat_limit": 15
    },
    {
    "gid": 24,
    "level_id": 24,
    "level_name": "国王",
    "chat_limit": 15
    },
    {
    "gid": 25,
    "level_id": 25,
    "level_name": "帝王",
    "chat_limit": 15
    },
    {
    "gid": 26,
    "level_id": 26,
    "level_name": "皇帝",
    "chat_limit": 15
    },
    {
    "gid": 27,
    "level_id": 27,
    "level_name": "天君",
    "chat_limit": 15
    },
    {
    "gid": 28,
    "level_id": 28,
    "level_name": "帝君",
    "chat_limit": 15
    },
    {
    "gid": 29,
    "level_id": 29,
    "level_name": "圣君",
    "chat_limit": 15
    },
    {
    "gid": 41,
    "level_id": 41,
    "level_name": "天尊",
    "chat_limit": 30
    },
    {
    "gid": 42,
    "level_id": 42,
    "level_name": "地尊",
    "chat_limit": 30
    },
    {
    "gid": 43,
    "level_id": 43,
    "level_name": "圣尊",
    "chat_limit": 30
    },
    {
    "gid": 44,
    "level_id": 44,
    "level_name": "神皇",
    "chat_limit": 30
    },
    {
    "gid": 45,
    "level_id": 45,
    "level_name": "创世之神",
    "chat_limit": 30
    }
    ]
    }
    }
     */
    public function getUserLevelInfo()
    {
        $data = resolve(UserGroupService::class)->getUserChatLimit(Auth::user(), 'member');

        $this->setStatus(1, 'OK');
        $this->setRootData('data', $data);

        return $this->jsonOutput();
    }
}
