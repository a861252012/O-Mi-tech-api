<?php

namespace App\Http\Controllers;
use App;
use App\Models\AgentsPriv;
use App\Models\AgentsRelationship;
use App\Models\Anchor;
use App\Models\CarGame;
use App\Models\CarGameBetBak;
use App\Models\Goods;
use App\Models\LevelRich;
use App\Models\MallList;
use App\Models\Messages;
use App\Models\Pack;
use App\Models\Recharge;
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
use App\Models\WithDrawalList;
use Core\Exceptions\NotFoundHttpException;
use DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;


class MemberController extends Controller
{
    private $_menus = array(
        /**
         * role: 0: 所有角色都有， 1, 普通用户才有， 2, 主播才有, 3, 需要后台人工设置
         */
        array(
            'role' => 0,
            'action' => 'index',
            'name' => '基本信息',
        ),
        // array(
        //     'action' => 'invite',
        //     'name' => '推广链接',
        //     'ico' => 7,
        // ),
        array(
            'role' => 0,
            'action' => 'attention',
            'name' => '我的关注',
            'ico' => 6,
        ),
        array(
            'role' => 0,
            'action' => 'scene',
            'name' => '我的道具',
            'ico' => 3,
        ),
        array(
            'role' => 0,
            'action' => 'charge',
            'name' => '充值记录',
            'ico' => 5,
        ),
        array(
            'role' => 0,
            'action' => 'consumerd',
            'name' => '消费记录',
            'ico' => 8,
        ),
        //消费统计，用户
        array(
            'role' => 1,
            'action' => 'count',
            'name' => '消费统计',
            'ico' => 7,
        ),
        //收入统计，主播
        array(
            'role' => 2,
            'action' => 'count',
            'name' => '收入统计',
            'ico' => 7,
        ),
        array(
            'role' => 0,
            'action' => 'vip',
            'name' => '贵族体系',
            'ico' => 8,
        ),
        array(
            'role' => 0,
            'action' => 'password',
            'name' => '密码管理',
            'ico' => 2,
        ),//主播才有
        array(
            'role' => 0,
            'action' => 'myReservation',
            'name' => '我的预约',
            'ico' => 2,
        ),//主播才有
        array(
            'role' => 2,
            'action' => 'roomset',
            'name' => '房间管理',//房间管理员
            'ico' => 2,
        ),//主播才有
        array(
            'role' => 3,
            'action' => 'transfer',
            'name' => '转账',
            'ico' => 8,
        ),//主播才有
        array(
            'role' => 2,
            'action' => 'withdraw',
            'name' => '提现',
            'ico' => 9,
        ),
        array(
            'role' => 2,
            'action' => 'anchor',
            'name' => '主播中心',
            'ico' => 4,
        ),//主播才有
        array(
            'role' => 0,
            'action' => 'gamelist',
            'name' => '房间游戏',
            'ico' => 4,
        ),//主播才有
        array(
            'role' => 2,
            'action' => 'commission',
            'name' => '佣金统计',
            'ico' => 7,
        ),//主播才有
        array(
            'role' => 2,
            'action' => 'live',
            'name' => '直播记录',
            'ico' => 8,
        ),//主播才有
        array(
            'role' => 0,
            'action' => 'msglist',
            'name' => '消息',
            'ico' => '0',
        ),
        array(
            'role' => 0,
            'action' => 'agents',
            'name' => '代理数据',
            'ico' => '0',
        )
    );

    /**
     * 用户中心需要的信息初始化
     *
     * @return JsonResponse
     */
    public function __init__()
    {
        // 调用顺序一定是这样的
        parent::__init__();

        //$params['base_url'] = 'http://' . $_SERVER['HTTP_HOST'];

        //$this->assign($params);

        $this->assignMenu();
    }

    /**
     * [assignMenu 分配用户中心菜单到模板]
     *
     * 添加转帐权限功能做了重构
     * @author dc <dc#wisdominfo.my> && young
     * @version 2016-9-8 显示权限做了重构
     * @return  Response
     */
    protected function assignMenu()
    {

        //主播菜单
        //$anchor = array('anchor', 'live', 'withdraw', 'roomset', 'commission', 'roomadmin');
        $hasAgentsPriv = AgentsPriv::where('uid', $this->userInfo['uid'])->count();

        $params['menus_list'] = array();

        foreach ($this->_menus as $key => $item) {

            //后台设置转帐菜单
            //if ((!isset($this->userInfo['transfer']) || !$this->userInfo['transfer']) && $item['action'] == 'transfer') continue;

            //代理菜单
            if (!$hasAgentsPriv && $item['action'] == 'agents') continue;

            //role == 0 为所有用户权限
            if ($item['role'] == 0) {
                $params['menus_list'][] = $item;
                //continue;
            }

            //role == 1 普通用户 role == 2 主播
            //如果是主播
            if ($this->userInfo['roled'] == 3 && $item['role'] == 2) {
                $params['menus_list'][] = $item;
            }

            //如果不是主播
            if ($this->userInfo['roled'] != 3 && $item['role'] == 1) {
                $params['menus_list'][] = $item;
            }

            //如果需要人工设置
            if ($item['role'] == 3 && (isset($this->userInfo[$item['action']]) && $this->userInfo[$item['action']])) {
                $params['menus_list'][] = $item;
            }

        }

        // TODO 临时解决房间管理页面选中的问题
        if ($this->container->currentMethod == 'roomadmin') {
            $params['curmenu'] = 'roomset';
        } else {
            $params['curmenu'] = $this->container->currentMethod;
        }

        // 分配数据到模板中
        $this->assign($params);
    }

    /**
     * 用户中心 基本信息
     * 此处需要用户信息userInfo，在父类中__init__中已经分配了
     * 只做了昵称修改的权限分配
     * @return \Core\Response
     */
    public function index()
    {
        /**
         * 创建用户服务 传入用户 获取用户修改昵称的权限
         *
         * @var $userServer /App/Service/User/UserService
         */
        $userService = $this->make('userServer');
        $modNickName = $userService->setUser((new Users)->forceFill($userService->getUserByUid(Auth::id())))->getModNickNameStatus();//2017-05-12 nicholas 优化，不直接读库
        //print_r($modNickName);die();
        return $this->render('Member/index', array('modNickName' => $modNickName));
    }

    /**
     * 用户中心 消息 列表页面
     *
     * @param int $type 消息类型 默认2为私信
     *  1 系统消息
     *  2 私信
     * @return \Core\Response
     * @author TX
     */
    public function msglist($type = 1)
    {
        // v2项目中只限制为系统消息
        if ($type != 1) {
            throw new NotFoundHttpException('not found!');
        }
        // 调用消息服务
        $msg = $this->container->make('messageServer');

        // 根据用户登录的uid或者用户消息的分页数据
        $ms = $msg->getMessageByUidAndType(Auth::id(), $type, '', $this->userInfo['lv_rich']);

        // 更新读取的状态
        $msg->updateMessageStatus(Auth::id(), $type);

        // 不同的消息类型做不同的模板 （移除私信 by Young）
        //$tpl = 'Member/msglist' . $type;
        $tpl = 'Member/msglist';

        //移除私信 By Young
        //return $this->render($tpl, array('data' => $ms, 'msglist1' => '系统消息', 'msglist2' => '私信'));
        return $this->render($tpl, array('data' => $ms, 'msglist' => '系统消息'));
    }

    /**
     * [transfer 转帐功能]
     *
     * @author dc <dc#wisdominfo.my>
     * @version 2015-11-13
     * @return  JsonResponse
     */
    public function transfer()
    {
        $request = $this->make('request');
        $uid = Auth::id();
        //转帐菜单
        if (!isset($this->userInfo['transfer']) || !$this->userInfo['transfer']) {
            return new RedirectResponse('/');
        }

        //数据列表、查询、展示处理。
        if ($request->getMethod() != 'POST') {
            $mintime = $request->get('mintime');
            $maxtime = $request->get('maxtime');
            $transfers = Transfer::where(function ($query) use ($uid) {
                $query->where('by_uid', $uid)->orWhere('to_uid', $uid);
            });
            if ($mintime && $maxtime) {
                $v['mintime'] = date('Y-m-d 00:00:00', strtotime($mintime));
                $v['maxtime'] = date('Y-m-d 23:59:59', strtotime($maxtime));
                $transfers->where('datetime', '>=', $mintime)->where('datetime', '<=', $maxtime);

            }
            $v['transfers'] = $transfers->orderBy('datetime', 'desc')->paginate(10);
            $v['transfers']->appends(['mintime' => $mintime, 'maxtime' => $maxtime])->render();
            $v['user'] = $this->userInfo;
            return $this->render('Member/transfer', $v);
        }
        /**
         * 转帐处理过程
         * @todo 待优化
         */
        if (!$this->make('captcha')->Verify($request->get('code'))) return new JsonResponse(array('status' => 0, 'message' => '验证码错误!'));;
        $this->make('captcha')->clear(); // 清空验证码
        //收款人信息
        $username = $request->get('username');
        $points = $request->get('points');
        $content = $request->get('content');

        if ($username == $this->userInfo['username']) return new JsonResponse(array('status' => 0, 'message' => '不能转给自己!'));

        if (intval($points) < 1) return new JsonResponse(array('status' => 0, 'message' => '转帐金额错误!'));

        //获取转到用户信息
        $user = $this->make('userServer')->getUserByUsername($username);

        if (!$user) return new JsonResponse(array('status' => 0, 'message' => '对不起！该用户不存在'));

        if (!$this->userInfo['transfer']) return new JsonResponse(array('status' => 0, 'message' => '对不起！您没有该权限！'));

        if ($this->userInfo['points'] < $points) return new JsonResponse(array('status' => 0, 'message' => '对不起！您的钻石余额不足!'));

        //开始转帐事务处理
        DB::beginTransaction();
        try {
            DB::table((new Users)->getTable())->where('uid', $uid)->decrement('points', $points);
            //update(array('points' => $this->userInfo['points'] - $points));

            DB::table((new Users)->getTable())->where('uid', $user['uid'])->increment('points', $points);
            //update(array('points' => $user['points'] + $points));

            //记录转帐
            DB::table((new Transfer)->getTable())->insert(array('by_uid' => $uid, 'by_nickname' => $this->userInfo['nickname'], 'to_uid' => $user['uid'], 'to_nickname' => $user['nickname'], 'points' => $points, 'content' => $content, 'datetime' => date('Y-m-d H:i:s'), 'status' => 1));

            //写入recharge表方便保级运算
            DB::table((new Recharge)->getTable())->insert(array('uid' => $user['uid'], 'points' => $points, 'paymoney' => round($points/10,2), 'created' => date('Y-m-d H:i:s'), 'order_id' => 'transfer_' . $uid . '_to_' . $user['uid'] . '_' . uniqid(), 'pay_type' => 7, 'pay_status' => 2, 'nickname' => $user['nickname']));


            //发送成功消息给转帐人
            $from_user_transfer_message = array('mail_type' => 3, 'rec_uid' => $uid, 'content' => '您成功转出' . $points . '钻石到 ' . $username . ' 帐户');
            $this->make('messageServer')->sendSystemToUsersMessage($from_user_transfer_message);

            //发送成功消息给收帐人
            $to_user_transfer_message = array('mail_type' => 3, 'rec_uid' => $user['uid'], 'content' => '您成功收到由 "' . $this->userInfo['nickname'] . '" 转到您帐户' . $points . '钻石');
            $this->make('messageServer')->sendSystemToUsersMessage($to_user_transfer_message);

            DB::commit();//事务提交

            //检查收款人用户VIP保级状态 一定要在事务之后进行验证
            $this->checkUserVipStatus($user);
            //更新转帐人用户redis信息
            $this->make('userServer')->getUserReset($uid);

            //更新收款人用户redis信息
            $this->make('userServer')->getUserReset($user['uid']);


            return new JsonResponse(array('status' => 1, 'message' => '您成功转出' . $points . '钻石'));
        } catch (\Exception $e) {
            DB::rollBack();//事务回滚
            return new JsonResponse(array('status' => 0, 'message' => '对不起！转帐失败!'));
        }
    }

    /**
     * 用户中心 代理数据
     * @author raby
     * @update 2016.06.30
     * @return Response
     */
    public function agents()
    {
        $agentsPriv = AgentsPriv::where('uid', $this->userInfo['uid'])->with("agents")->first();
        if (!$agentsPriv) {
            return new RedirectResponse('/');
        }
        $agentsRelationship = AgentsRelationship::where('aid', $agentsPriv->aid)->get()->toArray();
        $uidArray = array_column($agentsRelationship, 'uid');

        $mintimeDate = $this->request()->get('mintimeDate') ?: date('Y-m-d', strtotime('-1 month'));
        $maxtimeDate = $this->request()->get('maxtimeDate') ?: date('Y-m-d');
        $mintime = date('Y-m-d H:i:s', strtotime($mintimeDate));
        $maxtime = date('Y-m-d H:i:s', strtotime($maxtimeDate . '23:59:59'));

        $recharge_points = Recharge::whereIn('uid', $uidArray)->whereBetween('created', [$mintime, $maxtime])
            ->where('pay_status', 1)
            ->whereIn('pay_type', [1, 4, 7])
            ->sum('paymoney');
        $recharge_points = $recharge_points*10;
        $rebate_points = ($recharge_points * $agentsPriv->agents->rebate) / 100;

        $agent_members = AgentsRelationship::where('aid', $agentsPriv->aid)->count();

        $list = array(
            array(
                'aid' => $agentsPriv->aid,
                'username' => $agentsPriv->agents->agentaccount,
                'nickname' => $agentsPriv->agents->nickname,
                'members' => $agent_members,
                'recharge_points' => $recharge_points,
                'rebate_points' => $rebate_points,
            )
        );
        return $this->render('Member/agents', ['data' => $list, 'mintimeDate' => $mintimeDate, 'maxtimeDate' => $maxtimeDate]);
    }

    /**
     * [roomadmin 个人中心房间管理员管理]
     *
     * @author dc <dc#wisdominfo.my>
     * @version 2015-11-17
     * @return  JsonResponse
     */
    public function roomadmin()
    {
        $rid = Auth::id();
        $request = $this->make('request');

        //删除操作
        if ($request->getMethod() == 'POST' && $request->get('type') == 'delete') {
            $uid = $request->get('uid');
            //管理员软删除操作
            RoomAdmin::where('rid', $rid)->where('uid', $uid)->update(array('dml_flag' => 3));
            //删除redis管理员记录
            $this->make('redis')->srem('video:manage:' . $rid, $uid);
            return new JsonResponse(array('status' => 1, 'message' => '删除成功!'));
        }

        //管理员数据列表
        $v['roomadmin'] = RoomAdmin::where('rid', $rid)->where('dml_flag', '!=', 3)->with('user')->paginate(30);
        return $this->render('Member/roomadmin', $v);
    }


    /**
     * 用户中心 贵族体系
     */
    public function vip()
    {

        /**
         * 获取开通过的日志 最新一条就是当前
         */
        $log = array();
        // 如果用户还是贵族状态的话  就判断充值的情况用于保级
        $startTime = strtotime($this->userInfo['vip_end']) - 30 * 24 * 60 * 60;
        if ($this->userInfo['vip']) {
            $group = LevelRich::where('level_id', $this->userInfo['vip'])->first();
            if (!$group) {
                return true;// 用户组都不在了没保级了
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
            if ($log) $log->charge = $charge;
        }
        $data = array();
        $data['item'] = $log;

        return $this->render('Member/vip', $data);
    }

    /**
     * 用户中心 主播佣金统计
     * @author raby
     * @date 2019-9-29
     */
    public function commission()
    {
        $type = 'open_vip';

        $uid = $this->userInfo['uid'];
        if (!$uid) {
            throw $this->createNotFoundException();
        }
        $mintimeDate = $this->request()->get('mintime') ?: date('Y-m-d', strtotime('-1 year'));
        $maxtimeDate = $this->request()->get('maxtime') ?: date('Y-m-d');
        $mintime = date('Y-m-d H:i:s', strtotime($mintimeDate));
        $maxtime = date('Y-m-d H:i:s', strtotime($maxtimeDate . '23:59:59'));


        $all_data = UserCommission::where('uid', $uid)
            ->where('create_at', '>', $mintime)->where('create_at', '<', $maxtime)->where('type', $type)
            ->where('dml_flag', '!=', 3)
            ->orderBy('create_at', 'desc')->with('user')->with('userGroup')->paginate(10);

        $total = UserCommission::selectRaw('sum(points) as points')
            ->where('uid', $uid)
            ->where('create_at', '>', $mintime)->where('create_at', '<', $maxtime)->where('type', $type)
            ->where('dml_flag', '!=', 3)
            ->first();

        $total_points = ceil($total->points / 10);
        $all_data->appends(['mintime' => $mintimeDate, 'maxtime' => $maxtimeDate])->render();

        $var['total_points'] = $total_points;
        $var['data'] = $all_data;
        $var['mintime'] = $mintimeDate;
        $var['maxtime'] = $maxtimeDate;
        return $this->render('Member/commission', $var);
    }

    /**
     * 用户中心 邀请推广
     * @author D.C
     * @update 2014.12.12
     * @return Response
     */
    public function invite()
    {
        //获取用户ID
        $uid = Auth::id();

        //组装推广网址
        $userUrl = rtrim($this->make('request')->getSchemeAndHttpHost(), '/') . '/?u=' . $uid;

        //构建短网址
        //$googleURL = $this->_buildGoogleShortUrl($userUrl);
        $short_url = $this->_buildWeiboShortUrl($userUrl);
        $inviteUrl = $short_url ?: $userUrl;

        return $this->render('Member/invite', array('inviteurl' => $inviteUrl));
    }

    /**
     * 通过新浪微薄平台生成短网址
     * @param $url
     * @return bool|string
     * @author D.C
     * @update 2015-02-05
     * @version 1.0
     * @todo目前由于该api-key身份验证问题，新浪有一定的流量限制，无法保证所有用户都能生成短网址
     */
    private function _buildWeiboShortUrl($url)
    {
        if (!$url) return false;
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
                    if (stristr($url_short, 't.cn')) {
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
     *用户中心 我的关注 TODO 重写分页 优化分页
     * @return Response
     */
    public function attention()
    {
        $curpage = $this->make("request")->get('page') ?: 1;

        $userServer = $this->make('userServer');
        $data = $userServer->getUserAttens(Auth::id(), $curpage);

        return $this->render('Member/attention',
            array(
                'curmenu' => 'attention',
                'attenlist' => $data['data'],
                'pagination' => $data['pagination']
            ));
    }

    /**
     * 用户中心 我的道具
     * scene Action
     * @author D.C
     * @update 2014.11.07
     * @version 1.1
     * @return Response
     */
    public function scene()
    {
        $uid = Auth::id();

        //道具装备操作
        if ($this->make('request')->get('handle') == 'equip') {
            $handle = $this->_getEquipHandle($this->make('request')->get('gid'));
            if (is_array($handle)) {
                return new JsonResponse($handle);
            } else {
                $result = json_encode(array('status' => 101, 'messages' => '操作出现未知错误！'));
            }
        }
        $data = Pack::with('mountGroup')->where('uid', $uid)->paginate();
        $thispage = $this->make("request")->get('page') ?: 1;
        $result['user'] = $this->userInfo;
        $result['data'] = $data;
        $result['equip'] = $this->make('redis')->hgetall('user_car:' . $uid);

        //判断是否过期
        if ($result['equip'] != null && current($result['equip']) < time()) {
            $this->make('redis')->del('user_car:' . $uid);//检查过期直接删除对应的缓存key
        }

        return $this->render('Member/scene', $result);
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
            return array('status' => 2, 'messages' => '对不起！该道具限房间内使用,不能装备！');
        }

        /**
         * 使用Redis进行装备道具
         * @todo目前道具道备只在Redis上实现，并未进行永久化存储。目前产品部【Antony】表示保持现状。
         * @update 2014.12.15 14:35 pm (Antony要求将道具改为同时只能装备一个道具！)
         */
        $redis = $this->make('redis');
        $redis->del('user_car:' . $uid);
        $redis->hset('user_car:' . $uid, $gid, $pack['expires']);
        return array('status' => 1, 'messages' => '装备成功');
    }

    /**
     * 用户中心 个人充值列表
     *
     * @author cannyco<cannyco@weststarinc.co>
     * @update 2015.01.30
     * @return Response
     */
    public function charge()
    {
        //获取用户ID
        $uid = Auth::id();
        //获取下用户信息
        $chargelist = Recharge::where('uid', $uid)->where('del', 0)
            ->orderBy('id', 'DESC')->paginate();

        $userInfo = $this->userInfo;
        $rtn = array(
            'chargelist' => $chargelist,
            'user' => $userInfo,
        );
        return $this->render('Member/charge', $rtn);
    }

    /**
     * 用户中心 消费记录 TODO 优化用户的获取
     *
     * @author D.C
     * @version 1.0
     * @update 2014.11.7
     * @return Response
     */
    public function consumerd()
    {
        $uid = Auth::id();
        $result['user'] = $this->userInfo;
        $result['data'] = MallList::with('goods')->where('send_uid', $uid)
            ->where('gid', '>', 0)
            ->orderBy('created', 'DESC')->paginate();

        return $this->render('Member/consumerd', $result);
    }

    /**
     * 用户中心 密码管理
     * @name password
     * @author D.C
     * @version 1.0
     * @return Response
     */
    public function password()
    {

        if (Auth::guest()) {
            return new Response('Login:TimeOut');
        }
        $uid = Auth::id();
        if ($this->make('request')->getMethod() === 'POST') {
            $post = $this->make('request')->request->all();
            if (!$this->make('captcha')->Verify($post['captcha'])) {
                return new JsonResponse(array('code' => 300, 'msg' => '对不起，验证码错误!'));
            }

            if (empty($post['password'])) {
                return new JsonResponse(array('code' => 301, 'msg' => '原始密码不能为空！'));
            }


            if (strlen($post['password1']) < 6 || strlen($post['password2']) < 6) {
                return new JsonResponse(array('code' => 302, 'msg' => '请输入大于或等于6位字符串长度'));
            }

            if ($post['password1'] != $post['password2']) {
                return new JsonResponse(array('code' => 303, 'msg' => '新密码两次输入不一致!'));
            }

            $old_password = $this->make('redis')->hget('huser_info:' . $uid, 'password');
            $new_password = md5($post['password2']);
            if (md5($post['password']) != $old_password) {
                return new JsonResponse(array('code' => 303, 'msg' => '原始密码错误!'));
            }
            if ($old_password == $new_password) {
                return new JsonResponse(array('code' => 305, 'msg' => '新密码和原密码相同'));
            }

            $user = Users::find($uid);
            $user->password = $new_password;
            if (!$user->save()) {
                return new JsonResponse(array('code' => 304, 'msg' => '修改失败!'));
            }

            // 删除A域名上的session 踢出用户
           // $sid_a = $this->make('redis')->hget('huser_sid_a', Auth::id());
           // $this->make('redis')->del('PHPREDIS_SESSION:' . $sid_a);

            // 删除B域名上的session 踢出用户
            $sid = $this->make('redis')->hget('huser_sid', Auth::id());
            $this->make('redis')->del('PHPREDIS_SESSION:' . $sid);

            $this->_clearCookie();
            $this->_reqSession->invalidate();//销毁session,生成新的sesssID
            //更新用户redis
            $this->make('userServer')->getUserReset($uid);
            return new JsonResponse(array('code' => 0, 'msg' => '修改成功!请重新登录'));
        }

        return $this->render('Member/password');
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
        $status = array();
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
        for ($i = 0; $i < 25; $i++) {//生成前端的小时下拉框
            if ($i < 10) $result['hoption'][$i]['option'] = '0' . $i;
            else $result['hoption'][$i]['option'] = $i;
        }
        for ($i = 0; $i < 12; $i++) {//生成前端分钟的下拉框,每五分钟一次
            if ($i < 2) $result['ioption'][$i]['option'] = '0' . ($i * 5);
            else $result['ioption'][$i]['option'] = $i * 5;
        }

        //时长房间
        $roomStatus = $this->getRoomStatus(Auth::id(), 6);
        $result['roomStatus'] = $roomStatus;
        //      die(json_encode($result['types']));
        //var_dump($status); exit();
        return $this->render('Member/roomset', $result);
    }

    /**
     * @description 获取房间权限
     * @author TX
     * @date 2015.4.20
     */
    public function getRoomStatus($uid, $tid)
    {
        $hasname = 'hroom_status:' . $uid . ':' . $tid;
        $status = $this->make('redis')->hget($hasname, 'status');
        if (!empty($status)) {
            if ($status == 1) {
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
            if (empty($roomdata)) {
                return null;
            }

            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    $this->make('redis')->hset('hroom_status:' . $uid . ':' . $tid, $key, $value);
                }
            }
        }

        return $data;
    }

    /**
     * 一对多设置
     * @return JsonResponse
     */

    /**
     * 一对多记录详情-购买用户
     * @return JsonResponse
     */
    public function getBuyOneToMore()
    {
        $onetomore = $this->make('request')->get('onetomore');
        $userService = $this->make('userServer');
        $buyOneToMore = UserBuyOneToMore::where('onetomore', $onetomore)->where('type', 2)->get();
        $buyOneToMore->map(function (&$item) use ($userService) {
            $user = $userService->getUserByUid($item->uid);
            $item->nickname = isset($user['nickname']) ? $user['nickname'] : '';
        });
        return new JsonResponse(array('code' => 1, 'msg' => $buyOneToMore));
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
    public function roomOneToMore()
    {

       $start_time = $this->make('request')->get('mintime');
        $hour = $this->make('request')->get('hour');
        $minute = $this->make('request')->get('minute');
        $tid = $this->make('request')->get('tid');
        $duration = $this->make('request')->get('duration');
        $points = $this->make('request')->get('points');

        if (!in_array($duration, array(20, 25, 30, 35, 40, 45, 50, 55, 60))) return new JsonResponse(array('code' => 9, 'msg' => '请求错误'));

        if ($points > 99999 || $points <= 0) return new JsonResponse(array('code' => 3, 'msg' => '金额超出范围'));

        if (empty($tid) || empty($start_time) || empty($duration)) return new JsonResponse(array('code' => 4, 'msg' => '请求错误1'));

        $start_time = date("Y-m-d H:i:s", strtotime($start_time . ' ' . $hour . ':' . $minute . ':00'));

        if (date("Y-m-d H:i:s") > date("Y-m-d H:i:s", strtotime($start_time))) return new JsonResponse(array('code' => 6, 'msg' => '不能设置过去的时间'));

        //$room_config = $this->getRoomStatus(Auth::id(),7);
        $endtime = date('Y-m-d H:i:s', strtotime($start_time) + $duration * 60);

        if (!$this->notSetRepeat($start_time, $endtime)) return new JsonResponse(array('code' => 2, 'msg' => '你这段时间和一对一或一对多有重复的房间'));

        //添加
        /** @var \Redis $redis */
        $redis = $this->make('redis');

        $uids = '';
        $tickets = 0;
        //获取主播id
        $auth = Auth::guard();
        $uid = $auth->id();


        //如果结束时间在记录之前并且未结速，则处理。否则忽略
        $now = date('Y-m-d H:i:s');
        $lastRoom = RoomOneToMore::where('uid', $uid)->where('endtime', '>', $now)->where('status', 0)->orderBy('endtime', 'asc')->first();
        //$preRoom = RoomOneToMore::where('starttime','>',$endtime)->where('uid',Auth::id())->where('endtime','>',$now)->where('status',0)->first();
        if (!$lastRoom || strtotime($lastRoom->starttime) > strtotime($endtime)) {
            //当天消费,并且只能向后设置，固不用判断时间大于开始时间情况
            $macro_starttime = strtotime($start_time);
            $h = date('H');
            $etime = '';
            if ($h >= 6) {
                $etime = strtotime(date('Y-m-d')) + 30 * 3600;
            } else {
                $etime = strtotime(date('Y-m-d')) + 6 * 3600;
            }
            if ($macro_starttime < $etime) {
                $user_send_gite = $redis->hGetAll('one2many_statistic:' . Auth::id());
                if ($user_send_gite) {
                    foreach ($user_send_gite as $k => $v) {
                        if ($v >= $points) {
                            $tickets += 1;
                            $uids .= $k . ",";
                        }
                    }
                    $uids = substr($uids, 0, -1);
                }
            }
        }

        //$points = $room_config['timecost'];
        $oneToMoreRoom = new RoomOneToMore();
        $oneToMoreRoom->created = date('Y-m-d H:i:s');
        $oneToMoreRoom->uid = $uid;
        $oneToMoreRoom->roomtid = $tid;
        $oneToMoreRoom->starttime = $start_time;
        $oneToMoreRoom->duration = $duration * 60;
        $oneToMoreRoom->endtime = $endtime;
        $oneToMoreRoom->status = 0;
        $oneToMoreRoom->tickets = $tickets;
        $oneToMoreRoom->points = $points;
        $oneToMoreRoom->save();



        if ($uids) {
            $uidArr = explode(',', $uids);
            $insertArr = [];
            foreach ($uidArr as $k => $v) {
                $temp = [];
                $temp['onetomore'] = $oneToMoreRoom->id;
                $temp['rid'] = $uid;
                $temp['type'] = 2;
                $temp['starttime'] = $start_time;
                $temp['endtime'] = $endtime;
                $temp['duration'] = $duration * 60;
                $temp['points'] = $points;
                $temp['uid'] = $v;
                $temp['origin'] = 12;
                array_push($insertArr, $temp);
            }
            DB::table('video_user_buy_one_to_more')->insert($insertArr);
        }

        //
        $duroom = $oneToMoreRoom;
        /** @var \Redis $redis */
        $redis = $this->make('redis');
        $redis->sAdd("hroom_whitelist_key:" . $duroom['uid'], $duroom->id);

        $temp = [
            'starttime' => $duroom['starttime'],
            'endtime' => $duroom['endtime'],
            'uid' => $duroom['uid'],
            'nums' => $tickets,
            'uids' => $uids,
            'points' => $points,
        ];
        $rs = $this->make('redis')->hmset('hroom_whitelist:' . $duroom['uid'] . ':' . $duroom->id, $temp);

        $logPath =  base_path() . '/storage/logs/one2more_' . date('Ym') . '.log';
        $one2moreLog = 'hroom_whitelist:' . $duroom['uid'] . ':' . $duroom->id . ' ' . json_encode($temp) . "\n";
        $this->logResult('roomOneToMore  ' . $one2moreLog, $logPath);

        return new JsonResponse(array('status' => 1, 'msg' =>'添加成功！'));
    }

    /**
     * 时长房间金额设置
     * @author raby
     * @create 2016.9.16
     */
    public function roomSetTimecost()
    {
        return JsonResponse::create(['code' => 0, 'msg' => '非法操作']);//禁止用户修改
        $timecost = $this->make('request')->get('timecost');
        if ($timecost <= 0 || $timecost > 999999) return new JsonResponse(array('code' => "301", 'msg' => '金额设置有误'));

        //todo 时长房直播，并且开启时，不处理
        $timecost_status = $this->make("redis")->hget("htimecost:" . Auth::id(), "timecost_status");
        if ($timecost_status == 1) return new JsonResponse(array('code' => "302", 'msg' => '时长房直播中,不能设置'));

        RoomStatus::where("uid", Auth::id())->where("tid", 6)->where("status", 1)->update(['timecost' => $timecost]);

        $this->make("redis")->hset("hroom_status:" . Auth::id() . ":6", "timecost", $timecost);
        return new JsonResponse(array('code' => 1, 'msg' => '设置成功'));
    }

    /**
     *含时长房间设置  TODO 优化。。。。
     * @author TX
     * @update 2015.4.27
     * @return Response
     */
    public function roomSetDuration()
    {
        $start_time = $this->make('request')->get('mintime');
        $hour = $this->make('request')->get('hour');
        $minute = $this->make('request')->get('minute');
        $tid = $this->make('request')->get('tid');
        $duration = $this->make('request')->get('duration');
        if (!in_array($duration, array(25, 55))) return new JsonResponse(array('code' => 9, 'msg' => '请求错误'));

        // 判断是否为手动输入 如果手动输入需要大于1万才行
        $select_points = $this->make('request')->get('select-points');
        $input_points = $this->make('request')->get('input-points');
        if (empty($select_points) && $input_points < 10000) {
            return new JsonResponse(array('code' => 2, 'msg' => '手动设置的钻石数必须大于1万'));
        } else {
            $points = $input_points;
        }
        if (!empty($select_points)) {
            $points = $select_points;
        }

        if (empty($tid) || empty($start_time) || empty($duration) || empty($points)) return new JsonResponse(array('code' => 4, 'msg' => '请求错误'));
        $start_time = date("Y-m-d H:i:s", strtotime($start_time . ' ' . $hour . ':' . $minute . ':00'));
        $theday = date("Y-m-d H:i:s", mktime(23, 59, 59, date("m"), date("d") + 7, date("Y")));

        if ($theday < date("Y-m-d H:i:s", strtotime($start_time))) return new JsonResponse(array('code' => 5, 'msg' => '只能设置未来七天以内'));

        if (date("Y-m-d H:i:s") > date("Y-m-d H:i:s", strtotime($start_time))) return new JsonResponse(array('code' => 6, 'msg' => '不能设置过去的时间'));

        $durationRoom = new RoomDuration();
        $durationRoom->created = date('Y-m-d H:i:s');
        $durationRoom->uid = Auth::id();
        $durationRoom->roomtid = $tid;
        $durationRoom->starttime = $start_time;
        $durationRoom->duration = $duration * 60;
        $durationRoom->status = 0;
        $durationRoom->points = $points;

        $endtime = date('Y-m-d H:i:s', strtotime($start_time) + $durationRoom->duration);
        if ($this->notSetRepeat($start_time, $endtime)) {
            $durationRoom->save();
            $this->set_durationredis($durationRoom);
            return new JsonResponse(array('code' => 1, 'msg' => '添加成功'));
        } else {
            return new JsonResponse(array('code' => 2, 'msg' => '你这段时间有重复的房间'));
        }

    }

    /**
     *房间密码设置
     * @author TX
     * @update 2015.4.16
     * @return Response
     */
    public function roomSetPwd()
    {
        $room_radio = $this->make('request')->get('room-radio');
        $password = '';
        if ($room_radio == 'true') $password = $this->make('request')->get('password');
        if (empty($password) && $room_radio == 'true') {
            return new JsonResponse(array('code' => 2, 'msg' => '密码不能为空'));
        }
        if ($room_radio == 'true')//判断密码格式,密码格式和用户注册的密码格式是一样的
            if ($room_radio == 'true' && strlen($password) < 6 || strlen($password) > 22 || !preg_match('/^\w{6,22}$/', $password)) {
                return new JsonResponse(array('code' => 3, 'msg' => '密码格式不对'));
            }
//        $this->getRedis();
        $this->make('redis')->hset('hroom_status:' . Auth::id() . ':2', 'pwd', $password);
//        $em = $this->getDoctrine()->getManager();
//        $roomtype =  $em->getRepository('Video\ProjectBundle\Entity\VideoRoomStatus')->findOneBy(array('uid'=>$this->_uid,'tid'=>2));
//        $roomtype->setPwd($password);
//        $em->persist($roomtype);
//        $em->flush();
        $roomtype = RoomStatus::where('uid', Auth::id())->where('tid', 2)->update(['pwd' => $password]);
        if ($room_radio == 'false') return new JsonResponse(array('code' => 1, 'msg' => '密码关闭成功'));
        return new JsonResponse(array('code' => 1, 'msg' => '密码修改成功'));
    }

    /**
     *房间密码验证
     * @author TX
     * updata 2015.4.16
     * @return Response
     */
    public function checkroompwd()
    {
        $password = $this->request()->get('password');
        $rid = $this->request()->get('roomid');
        $type = $this->getAnchorRoomType($rid);
        if ($type != 2) return new JsonResponse(array('code' => 0, 'msg' => '密码房异常,请联系运营重新开启一下密码房间的开关'));
        if (empty($rid)) return new JsonResponse(array('code' => 0, 'msg' => '房间号错误!'));
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
                return new JsonResponse(array('code' => 4, 'msg' => '请输入验证码!', 'times' => $times));
            }
            if (!$this->make('captcha')->Verify($captcha)) return new JsonResponse(array('code' => 0, 'msg' => '验证码错误!', 'times' => $times));;
        }
        if (strlen($password) < 6 || strlen($password) > 22 || !preg_match('/^\w{6,22}$/', $password)) {
            $this->make('redis')->set($keys_room, $times + 1);
            $this->make('redis')->expire($keys_room, 3600);
            return new JsonResponse(array(
                "code" => 0,
                "msg" => "密码格式错误!",
                'times' => $times + 1
            ));
        }
        if ($password != $roomstatus['pwd']) {
            if (empty($times)) {
                $this->make('redis')->set($keys_room, 1);
                $this->make('redis')->expire($keys_room, 3600);
            } else {
                $this->make('redis')->set($keys_room, $times + 1);
                $this->make('redis')->expire($keys_room, 3600);
            }
            return new JsonResponse(array(
                "code" => 0,
                "msg" => "密码错误!",
                'times' => $times + 1
            ));
        }
        $this->make('redis')->hset('keys_room_passwd:' . $rid . ':' . $sessionid, 'status', 1);
        return new JsonResponse(array('code' => 1, 'msg' => '登陆成功'));
    }

    /**
     *房间密码错误次数请求
     * @author TX
     * updata 2015.4.16
     * @return Response
     */
    public function geterrorsAction()
    {
        $rid = $this->request()->get('roomid');
        if (empty($rid)) return new JsonResponse(array('code' => 2, 'msg' => '房间号错误!'));
//        $this->get('session')->start();
        $session_name = $this->request()->getSession()->getName();
        if (isset($_POST[$session_name])) {
            $this->request()->getSession()->setId($_POST[$session_name]);
        }
        $sessionid = $this->request()->getSession()->getId();
        $keys_room = 'keys_room_errorpasswd:' . $sessionid . ':' . $rid;
        $times = $this->make('redis')->hget($keys_room, 'times');
        if (empty($times)) $times = 0;
        return new JsonResponse(array('code' => 1, 'times' => $times));
    }

    /**
     *用户中心 我的预约 TODO 要优化 尼玛
     * @author TX
     * @update 2015.4.27
     * @update raby 2015.9.11
     * @return Response
     */
    public function myReservation()
    {
        $type = $this->make('request')->get('type') ?: 1;
        $rooms['type'] = $type;
        $rooms['data'] = array();
        $userServer = $this->make('userServer');
        switch ($type) {
            case 2:
                $data = UserBuyOneToMore::where('uid', Auth::id())->orderBy('starttime', 'DESC')->paginate();
                break;
            case 1:
            default:
                $data = RoomDuration::where('reuid', Auth::id())
                    ->where('starttime', '>', time() . '-duration')
                    ->orderBy('starttime', 'DESC')
                    ->paginate();;
        }
        foreach ($data as $key => $item) {
            $rid = $type == 2 ? $item->rid : $item->uid;
            $userinfo = $userServer->getUserByUid($rid);
            $rooms['data'][$key]['nickname'] = $userinfo['nickname'];
            $rooms['data'][$key]['starttime'] = date('Y-m-d H:i:s', strtotime($item['starttime']));
            $rooms['data'][$key]['endTime'] = date('Y-m-d H:i:s', strtotime($item->starttime) + ($item->duration));//date('Y-m-d H:i:s',strtotime($time1)+30*60);//注意引号内的大小写,分钟是i不是m
            $rooms['data'][$key]['duration'] = ceil($item->duration / 60);
            $rooms['data'][$key]['points'] = $item->points;
            $rooms['data'][$key]['uid'] = $userinfo['uid'];
            $rooms['data'][$key]['now'] = date('Y-m-d H:i:s');
            $rooms['data'][$key]['url'] = '/' . $userinfo['uid'];
        }
        $thispage = $this->make("request")->get('page') ?: 1;
        //我的预约推荐是从redis中取数据的，先全部取出数据，做排序
        $roomlists = $this->getReservation(Auth::id());
        $roomlist = array_slice($roomlists, ($thispage - 1) * 6, 6);
        $Count = count($roomlists);
        $rooms['pagination'] = array(
            'page' => $thispage,
            'count' => $Count,
            'pages' => ceil($Count / 6),
        );
        $rooms['room'] = array();

        $redis = $this->make('redis');
        foreach ($roomlist as $keys => $value) {
            $userinfo = $userServer->getUserByUid($value['uid']);
            $merge = array_merge($value, $userinfo);
            $merge['duration'] = ceil($merge['duration'] / 60);
            $rooms['room'][$keys] = $merge;
            $rooms['room'][$keys]['datenu'] = date('YmdHis', strtotime($value['starttime']));
            $rooms['room'][$keys]['points'] = $value['points'];
            $rooms['room'][$keys]['roomid'] = $value['id'];
            $rooms['room'][$keys]['headimg'] = $userinfo['headimg'];
            $cover = $redis->get('shower:cover:version:' . $userinfo['uid']);
            $image_server = rtrim($this->container->config['config.REMOTE_PIC_URL'], '/') . '/';
            $rooms['room'][$keys]['cover'] = $cover ? $image_server . $cover : false;
        }
        $rooms['uri'] = array();
        $uriParammeters = $this->make('request')->query->all();
        foreach ($uriParammeters as $p => $v) {
            if (strstr($p, '?')) continue;
            if (!empty($v)) {
                $rooms['uri'][$p] = $v;
            }
        }
        return $this->render('Member/myReservation', $rooms);
    }

    /**
     * 获取我的预约推荐列表TODO
     *
     * @param $uid
     * @return array
     * @Author TX
     */
    public function getReservation($uid)
    {
        $uids = $this->getRoomUid($uid);
        $rooms = array();
        $user_key = array();
        array_push($user_key, 'hroom_duration:' . $uid . ':4');
        $key = 'hroom_duration:*';
//        $keys = array();
        $keys = $this->make('redis')->getKeys($key);
        if ($keys == false) {
            $keys = array();
        }

        $room_reservation = array();
        foreach ($uids['reservation'] as $value) {
            array_push($user_key, 'hroom_duration:' . $value . ':4');
            $roomlist = $this->make('redis')->hGetAll('hroom_duration:' . $value . ':4');
            foreach ($roomlist as $item) {
                $room = json_decode($item, true);
                if ($room['status'] == 0 && $room['reuid'] == 0 && $room['uid'] != $uid && strtotime($room['starttime']) > time()) {
                    array_push($room_reservation, $room);
                }
            }
            foreach ($room_reservation as $key => $row) {
                $edition[$key] = $row['starttime'];
            }
            if (count($room_reservation) > 0) array_multisort($edition, SORT_ASC, $room_reservation);
        }
        $room_attens = array();
        foreach ($uids['attens'] as $value) {
            array_push($user_key, 'hroom_duration:' . $value . ':4');
            $roomlist = $this->make('redis')->hGetAll('hroom_duration:' . $value . ':4');
            foreach ($roomlist as $item) {
                $room = json_decode($item, true);
                if ($room['status'] == 0 && $room['reuid'] == 0 && $room['uid'] != $uid && strtotime($room['starttime']) > time()) {
                    array_push($room_attens, $room);
                }
            }
            foreach ($room_attens as $key => $row) {
                $edition_attens[$key] = $row['starttime'];
            }
            if (count($room_attens) > 0) array_multisort($edition_attens, SORT_ASC, $room_attens);
        }
        $keys = array_diff($keys, $user_key);
        $room_list = array();
        foreach ($keys as $value) {
            array_push($user_key, $value);
            $roomlist = $this->make('redis')->hGetAll($value);
            foreach ($roomlist as $item) {
                $room = json_decode($item, true);
                if ($room['status'] == 0 && $room['reuid'] == 0 && $room['uid'] != $uid && strtotime($room['starttime']) > time()) {
                    array_push($room_list, $room);
                }
            }
            foreach ($room_list as $key => $row) {
                $edition_list[$key] = $row['starttime'];
            }
            if (count($room_list) > 0) array_multisort($edition_list, SORT_ASC, $room_list);
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
     * @return Response
     */
    public function doReservation()
    {
        $roomid = $this->request()->get('duroomid');
        $flag = $this->request()->get('flag');
        if (empty($roomid) || empty($flag)) {
            return new JsonResponse(array('code' => 408, 'msg' => '请求错误'));
        }
        /** @var  $duroom \Video\ProjectBundle\Entity\VideoRoomDuration */
//        $duroom = $this->getDoctrine()->getManager()->getRepository('Video\ProjectBundle\Entity\VideoRoomDuration')->find($roomid);
        $duroom = RoomDuration::find($roomid);
        if (empty($duroom)) return new JsonResponse(array('code' => 410, 'msg' => '请求错误'));
        if (empty($duroom)) return new JsonResponse(array('code' => 401, 'msg' => '您预约的房间不存在'));
        if ($duroom['status'] == 1) return new JsonResponse(array('code' => 402, 'msg' => '当前的房间已经下线了，请选择其他房间。'));
        if ($duroom['reuid'] != '0') return new JsonResponse(array('code' => 403, 'msg' => '当前的房间已经被预定了，请选择其他房间。'));
        if ($duroom['uid'] == Auth::id()) return new JsonResponse(array('code' => 404, 'msg' => '自己不能预约自己的房间'));
        if ($this->userInfo['points'] < $duroom['points']) return new JsonResponse(array('code' => 405, 'msg' => '余额不足哦，请充值！'));
        //关键点，这个时段内有没有其他的房间重复，标志位为flag 默认值为false 当用户确认后传入的值为true
        if (!$this->checkRoomUnique($duroom, Auth::id()) && $flag == 'false') {
            return new JsonResponse(array('code' => 407, 'msg' => '您这个时间段有房间预约了，您确定要预约么'));
        }
        $duroom['reuid'] = Auth::id();
        $duroom->save();
        $this->set_durationredis($duroom);
        //记录一个标志位，在我的预约列表查询中需要优先显示查询已经预约过的主播，已经预约过的主播的ID会写到这个redis中类似关注一样的
        if (!($this->checkUserAttensExists(Auth::id(), $duroom['uid'], true, true))) {
            $this->make('redis')->zadd('zuser_reservation:' . Auth::id(), time(), $duroom['uid']);
        }
        Users::where('uid', Auth::id())->update(array('points' => ($this->userInfo['points'] - $duroom['points']), 'rich' => ($this->userInfo['rich'] + $duroom['points'])));
        $this->make('userServer')->getUserReset(Auth::id());// 更新redis TODO 好屌
        RoomDuration::where('id', $duroom['id'])
            ->update(array('reuid' => Auth::id(), 'invitetime' => time()));
        //增加消费记录查询
        MallList::create(array(
            'send_uid' => Auth::id(),
            'rec_uid' => $duroom['uid'],
            'gid' => 410001,
            //$duroom['roomtid'],irwin
            'gnum' => 1,
            'created' => date('Y-m-d H:i:s'),
            'rid' => $duroom['uid'],
            'points' => $duroom['points']
        ));
        // 用户增加预约排行榜的排名
        $this->make('redis')->zIncrBy('zrank_appoint_month' . date('Ym'), 1, $duroom['uid']);
        //修改用户日，周，月排行榜数据
        //zrank_rich_history: 用户历史消费    zrank_rich_week ：用户周消费   zrank_rich_day ：用户日消费  zrank_rich_month ：用户月消费
        $expire_day = strtotime(date('Y-m-d 00:00:00', strtotime('next day'))) - time();
        $expire_week = strtotime(date('Y-m-d 00:00:00', strtotime('next week'))) - time();
        $zrank_user = array('zrank_rich_history', 'zrank_rich_week', 'zrank_rich_day', 'zrank_rich_month:' . date('Ym'));
        foreach ($zrank_user as $value) {
            $this->make('redis')->zIncrBy($value, $duroom['points'], Auth::id());
            if ('zrank_rich_day' == $value) {
                $this->make('redis')->expire('zrank_rich_day', $expire_day);
            }
            if ('zrank_rich_week' == $value) {
                $this->make('redis')->expire('zrank_rich_week', $expire_week);
            }
        }
        //修改主播日，周，月排行榜数据
        //zrank_pop_history ：主播历史消费   zrank_pop_month  ：主播周消费 zrank_pop_week ：主播日消费 zrank_pop_day ：主播月消费
        $zrank_pop = array('zrank_pop_history', 'zrank_pop_month:' . date('Ym'), 'zrank_pop_week', 'zrank_pop_day');
        foreach ($zrank_pop as $value) {
            $this->make('redis')->zIncrBy($value, $duroom['points'], $duroom['uid']);
            if ('zrank_pop_day' == $value) {
                $this->make('redis')->expire('zrank_pop_day', $expire_day);
            }
            if ('zrank_pop_week' == $value) {
                $this->make('redis')->expire('zrank_pop_week', $expire_week);
            }
        }
        $this->make('redis')->lPush('lanchor_is_sub:' . $duroom['uid'], date('YmdHis', strtotime($duroom['starttime'])));
        return new JsonResponse(array('code' => 1, 'msg' => '预定成功'));
    }

    /**
     * 发送私信
     * @return string
     */
    public function domsg()
    {
        // $fid = $this->get('request')->get('fid');
        $tid = $this->make('request')->get('tid');
        if (!Users::find($tid)) {
            return new Response(json_encode(array(
                'ret' => false,
                'info' => '接受者用户不存在！'
            )));
        }
        if (Auth::id() == $tid) {
            return new Response(json_encode(array(
                'ret' => false,
                'info' => '不能给自己发私信！'
            )));
        }
        $content = $this->make('request')->get('content');
        $len = $this->count_chinese_utf8($content);
        if ($len < 0 || $len > 200) {
            return new Response(json_encode(array(
                'ret' => false,
                'info' => '输入为空或者输入内容过长，字符长度请限制200以内！'
            )));
        }
        $userInfo = $this->userInfo;
        if ($userInfo['roled'] == 0 && $userInfo['lv_rich'] < 3) {
            return new Response(json_encode(array(
                'ret' => false,
                'info' => '财富等级达到二富才能发送私信哦，请先去给心爱的主播送礼物提升财富等级吧。'
            )));
        }

        $num = $this->checkAstrictUidDay(Auth::id(), 1000, 'video_mail');//验证每天发帖次数
        if ($num == 0) {
            return new Response(json_encode(array(
                'ret' => false,
                'info' => '本日发送私信数量已达上限，请明天再试！'
            )));
        }

        $message = new Messages();
        $res = $message->create(array(
            'send_uid' => Auth::id(),
            'rec_uid' => $tid,
            'content' => htmlentities($content),
            'category' => 2,
            'status' => 0,
            'created' => date('Y-m-d H:i:s')
        ));
        if ($res) {
            $this->setAstrictUidDay(Auth::id(), $num, 'video_mail');//更新每天发帖次数
            return new Response(json_encode(array(
                'ret' => true,
                'info' => '私信发送成功！'
            )));
        } else {
            return new Response(json_encode(array(
                'ret' => false,
                'info' => '私信发送失败！'
            )));
        }
    }

    /**
     * 用户中心 提现页面
     *
     * @param
     * @return
     * @author Young
     * @update 2015-03-30
     * @version 1.0
     * @todo
     */
    public function withdraw()
    {
        if (!$this->userInfo['uid'] || $this->userInfo['roled'] != 3) {
            throw new NotFoundHttpException('error');
        }
        $withdrawnum = $this->make('request')->get('withdrawnum') ?: 0;
        if ($withdrawnum != 0) {
            $this->addwithdraw();
        }
        $mintime = $this->make('request')->get('mintime') ?: date('Y-m-d', strtotime('-1 month'));
        $maxtime = $this->make('request')->get('maxtime') ?: date('Y-m-d', strtotime('now'));
        $status = $this->make('request')->get('status') ?: 0;
        $availableBalance = $this->getAvailableBalance(Auth::id());
        $result['sum_points'] = $availableBalance['availpoints'];
        //$result['sum_points'] = 6000000;
        $result['Available_points'] = $availableBalance['availmoney'];
        //$result['Available_points'] = 6000000;
        $result['mintime'] = $mintime;
        $result['maxtime'] = $maxtime;
        $maxtime = date('Y-m-d' . ' 23:59:59', strtotime($maxtime));
//        $repository = $this->getDoctrine()->getManager()
//            ->getRepository('Video\\ProjectBundle\\Entity\\VideoWithdrawalList');
//
//        $queryBuilder = $repository->createQueryBuilder('l')
//            ->where('l.uid = :uid', 'l.created < :end', 'l.created > :start and l.status = :status')
//            ->orderby('l.created', 'DESC')
//            ->setParameter('uid', $this->_uid)
//            ->setParameter('status', $status)
//            ->setParameter('end', new \DateTime($maxtime))
//            ->setParameter('start', new \DateTime($mintime))
//            ->getQuery();

//        $thispage = $this->make('request')->get('page') ?: 1;
        $data = WithDrawalList::where('uid', Auth::id())->where('created', '<', $maxtime)->where('created', '>', $mintime)
            ->where('status', $status)
            ->orderBy('created', 'DESC')
            ->paginate();
        $result['user'] = $this->userInfo;
//        $result['pagination'] = \Video\ProjectBundle\Service\Pagination::page($queryBuilder, $thispage, 10);
//        $data = $queryBuilder->getResult();
        $status_array = array(
            '0' => '审批中',
            '1' => '已审批',
            '2' => '拒绝'
        );
        foreach ($data as $key => $item) {
            $result['data'][$key]['id'] = $item['id'];
            $result['data'][$key]['withdrawalnu'] = $item->withdrawalnu;
            $result['data'][$key]['created'] = $item->created;
            $result['data'][$key]['money'] = $item->money;
            $result['data'][$key]['status'] = $status_array[$item->status];
            $result['data'][$key]['withdrawaltime'] = $item->withdrawaltime;

        }
        $uriParammeters = $this->make('request')->query->all();
        $result['uri'] = array();
        foreach ($uriParammeters as $p => $v) {
            if (strstr($p, '?')) continue;
            if (!empty($v)) {
                $result['uri'][$p] = $v;
            }
        }
        if (empty($result['data'])) {
            $result['data'] = array();
        }
        return $this->render('Member/withdraw', $result);
    }

    /**
     * @description 提现申请
     * @author TX
     * @data2015.3.26
     */
    public function addwithdraw()
    {
        if (Auth::guest()) {
            return new Response('Login:TimeOut');
        }
        $money = $this->make('request')->get('withdrawnum');
        if (empty($money) || $money < 200) {
            return new JsonResponse(array('code' => 309, 'msg' => '每次提现不能少于200！'));
        }
        $uid = Auth::id();
        $avila_points = $this->getAvailableBalance($uid);
        if ($money > $avila_points['availmoney']) {
            return new JsonResponse(array('code' => 310, 'msg' => '提现金额不能大于可用余额！'));
        }
        $wd = date('ymdhis') . substr(microtime(), 2, 4);
        $withrawal = new WithDrawalList();
        $withrawal->uid = $uid;
        $withrawal->created = date('Y-m-d H:i:s');
        $withrawal->money = $money;
        $withrawal->moneypoints = $this->BalanceToOponts($money, $uid);
        $withrawal->withdrawalnu = $wd;
        $withrawal->status = 0;//0表示审批中
//        $em = $this->getDoctrine()->getManager();
//        $em ->persist($withrawal);
//        $em->flush();
        $withrawal->save();
        return new JsonResponse(array('code' => 0, 'msg' => '申请成功！请等待审核'));
    }

    /**
     * 用户中心 主播中心
     * @author D.C
     * @update 2014.11.11
     * @return Response
     */
    public function anchor()
    {
        $user = $this->userInfo;
        if (!$user['uid'] || $user['roled'] != 3) {
            throw $this->createAccessDeniedException();
        }

        //更新相册
        if (in_array($this->make('request')->get('handle'), array('del', 'get', 'set'))) {
            $id = sprintf("%u", $this->make('request')->get('id'));
            if (!$id) {
                return new Response(json_encode(array('code' => 101, 'info' => '操作失败')));
            }
            $result = $this->_anchorHandle($this->make('request')->get('handle'), $id);
            if ($result) {
                $result = is_array($result) ? json_encode($result) : $result;
                return new Response(json_encode(array('code' => 0, 'info' => '操作成功', 'data' => $result)));
            } else {
                return new Response(json_encode(array('code' => 103, 'info' => '操作失败', 'data' => $result)));
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
     * @author D.C
     * @param null $type
     * @param int $id
     * @return array|bool
     * @update 2014.11.7
     */
    private function _anchorHandle($type = null, $id = 0)
    {
        if (!$type || !$id) return false;
        /*irwin doctrine 到 lavarel 改造
        $gm = $this->get('doctrine')->getManager();
        $dm = new DataModel($this);
        $user = $this->getUserInfo();
        $anchor = $gm->getRepository('Video\ProjectBundle\Entity\VideoAnchor')->find($id);*/
        $anchor = Anchor::find($id);
        if (!$anchor) return false;
        $pic_used_size = $this->userInfo['pic_used_size'] - $anchor['size'];

        switch ($type) {
            case 'del':
                /*irwin doctrine 到 lavarel 改造
                $dm->setUserField(array('pic_used_size' => $pic_used_size), $user['uid']);
                $gm->remove($anchor);*/
                //将用户剩余图片空间同步更新数据库及redis
                Users::find(Auth::id())->update(array('pic_used_size' => $pic_used_size));
                $this->make('redis')->hMset('huser_info:' . Auth::id(), Users::find(Auth::id())->toArray());
                Anchor::find($id)->delete();
                break;

            case 'get':
                return Anchor::find($id)->toArray();
                break;

            case 'set':
                /*irwin doctrine 到 lavarel 改造
                $anchor->setName($this->request()->get('name'));
                $anchor->setSummary($this->request()->get('summary'));
                $gm->persist($anchor);*/
                //更新图片名称与备注
                Anchor::find($id)->update(array('name' => $this->make('request')->get('name'), 'summary' => $this->make('request')->get('summary')));
                break;

            default:
                return false;
        }
        //$gm->flush();
        return true;
    }

    /**
     *用户中心 房间游戏
     * @author TX
     * @updata 2015.6.110
     * @return Response
     */
    public function gamelist($type = 1)
    {
        if (!in_array($type, [1, 2])) {
            throw new NotFoundHttpException();
        }
        $data = array();
        // 我参与的
        if ($type == 1) {

            $data = CarGameBetBak::with(['game' => function ($query) {
                $query->with('gameMasterUser');
            }])->with('gameRoomUser')
                ->where('uid', Auth::id())
                ->where('dml_flag', '!=', 3)
                ->orderBy('created', 'desc')
                ->paginate();
        }
        // 我做庄的
        if ($type == 2) {
            $data = CarGame::with('gameRoomUser')
                ->where('uid', Auth::id())
                ->where('dml_flag', '!=', 3)
                ->orderBy('stime', 'DESC')
                ->paginate();
        }

        return $this->render('Member/gamelist' . $type, array('data' => $data));
    }

    /**
     * 用户中心 礼物统计
     * @description 礼物统计 TODO 优化可能性
     * @author D.C
     * @date 2015.2.6
     */
    public function count()
    {
        $uid = Auth::id();
        if (!$uid) {
            throw new NotFoundHttpException();
        }
        $type = $this->make('request')->get('type') ?: 'send';
        $mint = $this->make('request')->get('mintime') ?: date('Y-m-d', strtotime('-1 day'));
        $maxt = $this->make('request')->get('maxtime') ?: date('Y-m-d');

        $mintime = date('Y-m-d H:i:s', strtotime($mint));
        $maxtime = date('Y-m-d H:i:s', strtotime($maxt . '23:59:59'));

        $selectTypeName = $type == 'send' ? 'send_uid' : 'rec_uid';
        $uriParammeters = $this->make('request')->query->all();
        $var['uri'] = array();
        foreach ($uriParammeters as $p => $v) {
            if (strstr($p, '?')) continue;
            if (!empty($v)) {
                $var['uri'][$p] = $v;
            }
        }

        $all_data = MallList::where($selectTypeName, $uid)
            ->where('created', '>', $mintime)
            ->where('created', '<', $maxtime)
            ->where('gid', '>', 10)
            ->where('gid', '!=', 410001)
            ->paginate();

        $sum_Gift_mun = MallList::where($selectTypeName, $uid)
            ->where('created', '>', $mintime)
            ->where('created', '<', $maxtime)
            ->where('gid', '>', 10)
            ->where('gid', '!=', 410001)
            ->sum('gnum');
        $sum_Points_mun = MallList::where($selectTypeName, $uid)
            ->where('created', '>', $mintime)
            ->where('created', '<', $maxtime)
            ->where('gid', '!=', 410001)
            ->where('gid', '>', 10)
            ->sum('points');
        $sum_Gift_mun = $sum_Gift_mun ? $sum_Gift_mun : 0;
        $sum_Points_mun = $sum_Points_mun ? $sum_Points_mun : 0;
        $twig = clone $this->make('view');
        $twig->setLoader(new \Twig_Loader_String());

        $function = new \Twig_SimpleFunction('getUserName', function ($uid) {
            if (!$uid) return;
            $user = Users::find($uid);
            if ($user) {
                return $user['nickname'] ?: $user['username'];
            }
        });

        $twig->addFunction($function);

        $function = new \Twig_SimpleFunction('getGoods', function ($gid) {
            if (!$gid) return false;
            return $this->getGoods($gid);
        });
        $twig->addFunction($function);


        //todo author raby
        if ($type == "counttime") {
            $where_uid = ['send_uid' => Auth::id()];
            if ($this->userInfo['roled'] == 3) {
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

            $sum_Gift_mun = $uid_sum;
            $sum_Points_mun = $points_sum;
            //$data['timecost_list'] = $live_list;
            //print_r($all_data); exit;
        }

        $all_data->appends(['type' => $type, 'mintime' => $mint, 'maxtime' => $maxt]);

        $var['type'] = $type;
        $var['data'] = $all_data;
        $var['mintime'] = $mint;
        $var['maxtime'] = $maxt;
        $var['sum_Gift_mun'] = $sum_Gift_mun;
        $var['sum_Points_mun'] = $sum_Points_mun;
        return $this->render('Member/count', $var);
    }

    /**
     * 用户中心 直播时间 TODO 尼玛
     *
     * @author TX
     * @date 2015.2.6
     */
    public function live()
    {

        $uid = Auth::id();

        /**
         * 查询的开始时间 用于获取数据
         * 默认是前一天到现在的
         */
        $start = $this->make('request')->get('start') ?: $this->_reqSession->get('live_start');
        if (!$start) {
            $start = date('Y-m-d', strtotime("-1 day"));
        } else {
            $this->_reqSession->set('live_start', $start);
        }

        $end = $this->make('request')->get('end') ?: $this->_reqSession->get('live_end');
        if (!$end) {
            $end = date('Y-m-d');
        } else {
            $this->_reqSession->set('live_end', $end);
        }
        $result = array();
        $result['end'] = $end;
        $result['start'] = $start;
        $end = date('Y-m-d' . ' 23:59:59', strtotime($end));
        $start = date('Y-m-d' . ' 00:00:00', strtotime($start));
        $result['user'] = $this->userInfo;
        $result['data'] = array();
        /**
         * 获取自播记录 运算时长
         */
        $iEnd = strtotime($end);
        $iStart = strtotime($start);
////        var_dump(['uid' => $uid, 'end' => $end, 'start' => $start, 'start2' => $start, 'iEnd' => $iEnd, 'iStart' => $iStart]);
////        $page = $this->make('request')->get('page')?$this->make('request')->get('page'):1;
//        $count = DB::select('select count(0) as c from video_live_list where uid=:uid and ((start_time < :end and start_time > :start) or (
//          start_time < :start2 and UNIX_TIMESTAMP(start_time)+duration < :iEnd and UNIX_TIMESTAMP(start_time)+duration > :iStart))
//          order by start_time desc', ['uid' => $uid, 'end' => $end, 'start' => $start, 'start2' => $start, 'iEnd' => $iEnd, 'iStart' => $iStart]);


        $data = (array)DB::select('select * from video_live_list where uid=:uid and ((start_time < :end and start_time > :start) or (
          start_time < :start2 and UNIX_TIMESTAMP(start_time)+duration < :iEnd and UNIX_TIMESTAMP(start_time)+duration > :iStart))
          order by start_time desc', ['uid' => $uid, 'end' => $end, 'start' => $start, 'start2' => $start, 'iEnd' => $iEnd, 'iStart' => $iStart]);
        $thispage = $this->make("request")->get('page') ?: 1;
        //我的预约推荐是从redis中取数据的，先全部取出数据，做排序
        $total_duration = $data;
        $Count = count($data);
        $data = array_slice($data, ($thispage - 1) * 15, 15);
        $result['pagination'] = array(
            'page' => $thispage,
            'count' => $Count,
            'pages' => ceil($Count / 15),
        );
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
            $result['data'][$key] = $item;

            $result['data'][$key]['created'] = $item['start_time'];
            /**
             * 如果开始时间是在前一天的
             */
            if ($item['start_time'] <= $start) {
                $result['data'][$key]['startTime'] = $start;
            } else {
                $result['data'][$key]['startTime'] = $item['start_time'];
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
            $result['data'][$key]['endTime'] = date('Y-m-d H:i:s', $endTime);//date('Y-m-d H:i:s',strtotime($time1)+30*60);//注意引号内的大小写,分钟是i不是m
            $result['data'][$key]['duration'] = $this->_sec2time($duration);
        }
        $result['data'] = new LengthAwarePaginator($result['data'], $Count, 15, '', ['path' => '/member/live', 'query' => ['start' => $result['start'], 'end' => $result['end']]]);
        //$result['totalTime'] = $this->getTotalTime($uid, $end, $start);
        if (empty($result['data'])) {
            $result['data'] = array();
        }
        $result['totalTime'] = $this->_sec2time($total);
        return $this->render('Member/live', $result);
    }

    /**
     *含时长房间修改
     * @author TX
     * @update 2015.4.27
     * @return Response
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
            return new JsonResponse(array('code' => 10, 'msg' => '请求错误'));
        }
        if (!in_array($duration, array(25, 55))) {
            return new JsonResponse(array('code' => 11, 'msg' => '请求错误'));
        }
        /** @var  $durationRoom \Video\ProjectBundle\Entity\VideoRoomDuration */
        $durationRoom = RoomDuration::find($durationid);
        if ($durationRoom->reuid != 0) {
            return new JsonResponse(array('code' => 8, 'msg' => '房间已经被预定，不能被修改'));
        }
        if (empty($durationRoom)) {
            return new JsonResponse(array('code' => 12, 'msg' => '请求错误'));
        }
        $theday = date("Y-m-d H:i:s", mktime(23, 59, 59, date("m"), date("d") + 7, date("Y")));
        $start_time = date("Y-m-d H:i:s", strtotime($start_time . ' ' . $hour . ':' . $minute . ':00'));
        if ($theday < date("Y-m-d H:i:s", strtotime($start_time))) {
            return new JsonResponse(array('code' => 5, 'msg' => '只能设置未来七天以内'));
        }
        if (date("Y-m-d H:i:s") > date("Y-m-d H:i:s", strtotime($start_time))) {
            return new JsonResponse(array('code' => 6, 'msg' => '不能设置过去的时间'));
        }
        $durationRoom->starttime = $start_time;
        $durationRoom->duration = $duration * 60;
        $durationRoom->points = $points;
        if ($this->checkRoomUnique($durationRoom)) {
            //$this->_data_model->updateByEntity('Video\ProjectBundle\Entity\VideoRoomDuration', array('id' => $durationid), array('starttime' => new \DateTime($start_time), 'duration' => $duration * 60, 'points' => $points));
            $durationRoom->save();
            $this->set_durationredis($durationRoom);
            return new JsonResponse(array('code' => 1, 'msg' => '修改成功'));
        } else {
            return new JsonResponse(array('code' => 9, 'msg' => '这一时段内有重复哦'));
        }

    }

    /**
     * [avatarupload 头像上传方法]
     *
     * @author dc <dc#wisdominfo.my>
     * @version 2015-11-20
     * @return  JsonResponse
     */
    public function avatarUpload()
    {
        $user = $this->userInfo;
        $result = json_decode($this->make('systemServer')->upload($this->userInfo), true);

        if (!$result['ret']) return new JsonResponse($result);
        //更新用户头像
        Users::where('uid', $user['uid'])->update(array('headimg' => $result['info']['md5']));

        //更新用户redis
        $this->make('userServer')->getUserReset($user['uid']);

        return new JsonResponse($result);
    }

    /**
     * [flashUpload 相册上传方法]
     *
     * @author dc <dc#wisdominfo.my>
     * @version 2015-11-20
     * @return JsonResponse
     */
    public function flashUpload()
    {
        $user = $this->userInfo;
        $result = json_decode($this->make('systemServer')->upload($this->userInfo), true);

        if (!$result['ret']) return new JsonResponse($result);

        $anchor = Anchor::create(array('uid' => Auth::id(), 'file' => $result['info']['md5'], 'size' => $result['info']['size'], 'jointime' => time()));

        //更新用户空间
        Users::where('uid', $user['uid'])->update(array('pic_used_size' => $user['pic_used_size'] + $result['info']['size']));
        //更新用户redis
        $this->make('userServer')->getUserReset($user['uid']);


        $result['info']['id'] = $anchor->id;
        return new JsonResponse($result);

    }

    /**
     * 取消装备道具
     * @return JsonResponse
     * @Author Orino
     */
    public function cancelScene()
    {
        $this->make('redis')->del('user_car:' . Auth::id());//检查过期直接删除对应的缓存key
        return new JsonResponse(array('status' => 0, 'msg' => '操作成功'));
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
        $msg = array(
            'code' => 0,
            'msg' => ''
        );
        if (!$this->userInfo) {
            $msg = array(
                'code' => 101,
                'msg' => '亲，请先登录哦！'
            );
            return (new JsonResponse($msg))->setCallback('cb');
        }
        // 取到开通的贵族的数据 判断价格
        $gid = $this->request()->get('gid');
        // 默认的天数
//        $day = $this->request()->get('day') ? $this->request()->get('day') : 30;
        $day = 30;
        // 如果在房间内 就会有roomid即为主播uid，默认为0不在房间开通， 用于佣金方面的问题
        $roomId = $this->request()->get('roomId') ? $this->request()->get('roomId') : 0;

        $user = DB::table('video_user')->where('uid', $this->userInfo['uid'])->first();
        // 用户组服务
        $userGroup = $this->make('userGroupServer')->getGroupById($gid);
        if (!$userGroup || $userGroup['dml_flag'] == 3) {
            $msg['code'] = 1002;
            $msg['msg'] = '该贵族状态异常,请联系客服！';
            return (new JsonResponse($msg))->setCallback('cb');
        }

        // 钱不够
        if ($userGroup['system']['open_money'] > $user->points) {
            $msg['code'] = 102;
            $msg['msg'] = '亲,你的钻石不够啦！赶快充值吧！';
            return (new JsonResponse($msg))->setCallback('cb');
        }

        // 已经开通了高等级的 不能再开通低等级的
        if ($userGroup['level_id'] == $user->vip) {
            $msg['code'] = 1004;
            $msg['msg'] = '你已开通过此贵族，你可以保级或者开通高级贵族！';
            return (new JsonResponse($msg))->setCallback('cb');
        }
        if ($userGroup['level_id'] < $user->vip) {
            $msg['code'] = 1005;
            $msg['msg'] = '请现有等级过期后再开通，或开通高等级的贵族！';
            return (new JsonResponse($msg))->setCallback('cb');
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
            $isBuyThisGroup = DB::select('select * from video_user_buy_group where gid=? and uid=? limit 1', array($gid, Auth::id()));

            $userServer = $this->make('userServer')->setUser(Users::find(Auth::id())); // 初始化用户服务
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
                $arr = array(
                    'uid' => Auth::id(),
                    'created' => date('Y-m-d H:i:s'),
                    'points' => $userGroup['system']['gift_money'],
                    'paymoney' => round($userGroup['system']['gift_money'],1),
                    'order_id' => time(),
                    'pay_type' => 5,//服务器送的钱pay_type=5
                    'pay_id' => null,
                    'nickname' => $user->nickname
                );
                DB::table('video_recharge')->insert($arr);
                // 赠送后 发送给用户通知消息
                $message = array(
                    'mail_type' => 3,
                    'rec_uid' => $this->userInfo['uid'],
                    'content' => '您首次开通 ' . $userGroup['level_name'] . ' 贵族，获得了赠送礼包的' . $userGroup['system']['gift_money'] . '钻石'
                );
                $this->make('messageServer')->sendSystemToUsersMessage($message);
            }

            // 写入购买用户组的记录 user_buy_group
            $buyGroup = array(
                'uid' => Auth::id(),
                'gid' => $gid,
                'create_at' => date('Y-m-d H:i:s'),
                'rid' => $roomId,
                'end_time' => $exp,
                'status' => 1,
                'open_money' => $userGroup['system']['open_money'],
                'keep_level' => $userGroup['system']['keep_level'],
            );
            DB::table('video_user_buy_group')->insertGetId($buyGroup);

            /**
             * 购买贵族后自动送坐骑
             * [添加用户背包判断再进行赠送]
             * @author dc
             * @version 20151026
             */
            $userPack = Pack::whereUid(Auth::id())->whereGid($userGroup['mount'])->first();
            if (!$userPack) {
                DB::insert('insert into `video_pack` (uid, gid, expires, num) values (?, ?, ?, ?)', [Auth::id(), $userGroup['mount'], strtotime($exp), 1]);
                /*@todo 待检查为何这个方法插入失败*/
                //Pack::create(array('uid'=>Auth::id(),'gid'=>$userGroup['mount'],'expires'=>strtotime($exp),'num'=>1));
            }

            // 赠送爵位
            if ($userGroup['system']['gift_level']) {
                $userServer->modLvRich($userGroup['system']['gift_level']);
            }

            // 开通成功后 发送给用户通知消息
            $message = array(
                'mail_type' => 3,
                'rec_uid' => $this->userInfo['uid'],
                'content' => '贵族开通成功提醒：您已成功开通 ' . $userGroup['level_name'] . ' 贵族，到期日：' . $exp
            );
            $this->make('messageServer')->sendSystemToUsersMessage($message);

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
                        'gid' => $gid, 'vip_end' => $exp
                    )),
                    'content' => $this->userInfo['nickname'] . '在您的房间' . date('Y-m-d H:i:s') . '开通了什么' . $userGroup['level_name'] . '，您得到' . $userGroup['system']['host_money'] . '佣金！',
                    'status' => 0,
                    'dml_flag' => 1
                );
                DB::table('video_user_commission')->insertGetId($commission);
                $casheback = $userGroup['system']['host_money'];
            }
            $userinfo = DB::select('select * from video_user where uid=? limit 1', array(Auth::id()));
            //赠送完坐骑立即装备
            $redis = $this->make('redis');
            // 更新用户redis中的信息
            $redis->hMset('huser_info:' . Auth::id(), (array)$userinfo[0]);
            $redis->del('user_car:' . Auth::id());
            $redis->hset('user_car:' . Auth::id(), $userGroup['mount'], strtotime($exp));

            DB::commit();

        } catch (\Exception $e) {
            DB::rollback();
            //记录下日志
            $logPath = BASEDIR . '/app/logs/test_' . date('Y-m-d') . '.log';
            $loginfo = date('Y-m-d H:i:s') . ' uid' . Auth::id() . "\n 购买贵族 事务结果: \n" . $e->getMessage() . "\n";
            $this->logResult($loginfo, $logPath);

            $msg['code'] = 1003;
            $msg['msg'] = '可能由于网络原因，开通失败！';
            return (new JsonResponse($msg))->setCallback('cb');
        }
        $msg['msg'] = '开通成功';
        $msg['data'] = array(
            'uid' => Auth::id(),
            'roomid' => $roomId,
            'vip' => $userGroup['level_id'],
            'cashback' => $casheback,
            'name' => $this->userInfo['nickname']
        );

        return (new JsonResponse($msg))->setCallback('cb');
    }

    /**
     * 获取贵族的坐骑
     */
    public function getVipMount()
    {
        // 获取vip坐骑的id
        $mid = $this->make('request')->get('mid');
        $msg = array(
            'code' => 0,
            'msg' => ''
        );

        // 判断是否已经领过了
        $pack = Pack::where('uid', Auth::id())->where('gid', $mid)->first();
        if ($pack) {
            $msg['code'] = 1002;
            $msg['msg'] = '你已经获取过了该坐骑！';
            return new JsonResponse($msg);
        }

        // 判断是否是对应的贵族
        $userGroup = UserGroup::where('type', 'special')->where('mount', $mid)->first();

        if (!$userGroup) {
            $msg['code'] = 1005;
            $msg['msg'] = '此坐骑专属贵族所有！';
            return new JsonResponse($msg);
        }

        if ($this->userInfo['vip'] < $userGroup['level_id']) {
            $msg['code'] = 1003;
            $msg['msg'] = '你还不够领取此级别的坐骑！';
            return new JsonResponse($msg);
        }

        // 领取成功
        $pack = new Pack();
        $pack->uid = Auth::id();
        $pack->gid = $mid;
        $pack->num = 1;
        $pack->expires = strtotime($this->userInfo['vip_end']);
        $res = $pack->save();

        if ($res !== false) {
            $msg['msg'] = '开通成功！';
            return new JsonResponse($msg);
        }

    }

    /**
     * 获取用户的钱 主要是用于在商城页面中购买商品的接口
     */
    public function getmoney()
    {
        return new JsonResponse(
            array(
                'code' => 0,
                'info' => array(
                    'nickname' => $this->userInfo['nickname'],
                    'money' => $this->userInfo['points']
                )
            )
        );
    }

    /**
     * [hidden 用户在线、隐身接口]
     *
     * @author dc <dc#wisdominfo.my>
     * @version 2015-11-11
     * @param   int $status 要设置的状态1=隐身，0=在线
     * @return  json
     */
    public function hidden($status)
    {
        if (!in_array($status, array('0', '1'))) return new JsonResponse(array('status' => 0, 'message' => '参数错误'));

        $uid = Auth::id();
        if (!$uid) return new JsonResponse(array('status' => 0, 'message' => '用户错误'));
        $user = Users::where('uid', $uid)->with('vipGroup')->first();

        //判断用户是否有隐身权限
        if (!$this->make('userServer')->getUserHiddenPermission($user)) return new JsonResponse(array('status' => 0, 'message' => '没有权限!'));

        //更新数据库隐身状态
        $hidden = Users::where('uid', $uid)->update(array('hidden' => $status));

        //更新用户redis
        $this->make('userServer')->getUserReset($uid);

        return new JsonResponse(array('status' => 1, 'message' => '操作成功'));
    }


    /**
     * ajax 请求获取信息 TODO 策略
     *
     * @return Response
     */
    public function ajax($act)
    {
//        $act = $this->request()->get('action');
        if (($act != 'getfidinfo') && Auth::guest()) {
            return new RedirectResponse('/', 301);
        }
//        $this->_initUser();
        $actions = array(
            'userinfo' => 'editUserinfo',
            'attenionCancel' => 'attenionCancel',
            'getfidinfo' => 'getfidinfo',
            'delmsg' => 'delmsg',
            'equipHandle' => '_getEquipHandle'
        );
        if ($act == 'userinfo') {
            $info = $this->$actions[$act]($this->request()->reques->all(), Auth::id());
            return new Response(json_encode($info));
        } elseif ($act == 'attenionCancel') {
            $this->$actions[$act](Auth::id(), $this->request()->get('tid'));
        } else if ($act == 'getfidinfo') {
            $onlineId = $this->request()->getSession()->get(self::SEVER_SESS_ID);
            $uid = intval($this->request()->get('uid'));
            if ($uid == 0) {
                return new Response(json_encode(array('ret' => false)));
            }
            $data = $this->{$actions[$act]}($this->request()->get('uid'));
            if (!$data) {
                return new Response(json_encode(array('ret' => false)));
            } else {
                $data = $this->getOutputUser($data, 80);
                unset($data['safemail']);
                if ($onlineId) {
                    //$data['checkatten'] = $this->_data_model->checkIsattenByuid($uid,$onlineId);
                    $data['checkatten'] = $this->make('userServer')->checkFollow($onlineId, $uid) ? 1 : 0;
                } else {
                    $data['checkatten'] = 0;
                }
                if ($data['roled'] == 3) {
                    $data['live_status'] = $this->make('redis')->hget('hvediosKtv:' . $data['uid'], 'status');
                }
                return new Response(json_encode(array('ret' => true, 'info' => $data)));
            }
        } else if ($act == 'delmsg') {
            $data = $this->$actions[$act](Auth::id());
            //  return;
        } else if ($act == 'equipHandle') {
            $this->$actions[$act]($this->request()->get('gid'));
        }
        return new Response(json_encode(array('ret' => true, 'info' => '更新成功')));
    }

    /**
     * 付款
     */
    public function pay()
    {
        $retData = null;
        if (!$this->buyGoods()) {
            $retData = array(
                'info' => '购买失败！可能钱不够',
                'ret' => false
            );
        } else {
            //  $this->_getEquipHandle($this->get('request')->get('gid'));//分布
            $retData = array(
                'info' => '购买成功！',
                'ret' => true
            );
        }
        return new Response(json_encode($retData));
    }

    /**
     * 删除一对一房间
     */
    public function delRoomDuration()
    {
        $rid = $this->request()->input('rid');
        if (!$rid) return JsonResponse::create(['code' => 401, 'msg' => '请求错误']);
        $room = RoomDuration::find($rid);
        if (!$room) return new JsonResponse(array('code' => 402, 'msg' => '房间不存在'));
        if ($room->uid != Auth::id()) return JsonResponse::create(['code' => 404, 'msg' => '非法操作']);//只能删除自己房间
        if ($room->status == 1) return new JsonResponse(array('code' => 403, 'msg' => '房间已经删除'));
        if ($room->reuid != 0) {
            return new JsonResponse(array('code' => 400, 'msg' => '房间已经被预定，不能删除！'));
        }
        $this->make('redis')->hdel('hroom_duration:' . $room->uid . ':' . $room->roomtid, $room->id);//删除对应的redis
        $room->delete();
        return JsonResponse::create(['code' => 1, 'msg' => '删除成功']);
    }

    /**
     * 删除一对多房间
     * @return JsonResponse|Response|static
     */
    public function delRoomOne2Many()
    {
        $rid = $this->request()->input('rid');
        if (!$rid) return JsonResponse::create(['status' => 401, 'msg' => '请求错误']);
        $room = RoomOneToMore::find($rid);
        if (!$room) return new JsonResponse(array('status' => 402, 'msg' => '房间不存在'));
        if ($room->uid != Auth::id()) return JsonResponse::create(['status' => 404, 'msg' => '非法操作']);//只能删除自己房间
        if ($room->status == 1) return new JsonResponse(array('status' => 403, 'msg' => '房间已经删除'));
        if ($room->purchase()->exists()) {
            return new JsonResponse(array('status' => 400, 'msg' => '房间已经被预定，不能删除！'));
        }
        $redis = $this->make('redis');
        $redis->sRem('hroom_whitelist_key:' . $room->uid, $room->id);
        $redis->delete('hroom_whitelist:' . $room->uid . ':' . $room->id);
        $room->update(['status' => 1]);
        return JsonResponse::create(['status' => 1, 'msg' => '删除成功']);
    }

    /**
     * 一对多补票接口
     */
    public function makeUpOneToMore()
    {
        $uid = Auth::id();
        $request = $this->request();
        $rid = intval($request->input('rid'));
        $origin = intval($request->input('origin'))?:12;
        if ($rid==$uid) return JsonResponse::create(['status'=>0,'msg'=>'不能购买自己房间亲']);
        $onetomany = intval($request->input('onetomore'));
        if (empty($onetomany) || empty($uid)) return JsonResponse::create(['status' => 0, 'msg' => '参数错误']);
        /** @var \Redis $redis */
        $redis = $this->make('redis');
        $room = $redis->hgetall("hroom_whitelist:$rid:$onetomany");
        if (empty($room)) return JsonResponse::create(['status' => 0, 'msg' => '房间不存在']);

        $points = $room['points'];
        if (isset($room['uids']) && in_array($uid, explode(',', $room['uids']))) return JsonResponse::create(['status' => 0, 'msg' => '您已有资格进入该房间，请从“我的预约”进入。']);
        if (isset($room['tickets']) && in_array($uid, explode(',', $room['tickets']))) return JsonResponse::create(['status' => 0, 'msg' => '您已有资格进入该房间，请从“我的预约”进入。']);
        /** 检查余额 */
        $user = $this->make('userServer')->getUserByUid($uid);
        if ($user['points'] < $points) return JsonResponse::create(['status' => 0, 'msg' => '余额不足', 'cmd' => 'topupTip']);
        /** 通知java送礼*/
        $redis->publish('makeUpOneToMore',
            json_encode([
                'rid' => $rid,
                'uid' => $uid,
                'onetomore' => $onetomany,
                'origin' => $origin
            ]));
        /** 检查购买状态 */
        $timeout = microtime(true) + 4;
        while (true) {
            if (microtime(true) > $timeout) break;
            $tickets = explode(',', $redis->hGet("hroom_whitelist:$rid:$onetomany", 'tickets'));
            if (in_array($uid, $tickets)) return JsonResponse::create(['status' => 1, 'msg'=>'购买成功']);
            usleep(200000);
        }
        return JsonResponse::create(['status' => 0, 'msg'=> '购买失败']);
    }

    public function buyModifyNickname()
    {
        $uid = Auth::id();
        $price = $this->make('config')['config.nickname_price'] ?: 200;
        $userService = $this->make('userServer');
        $user = $userService->getUserByUid($uid);
        if ($user['points'] < $price) return JsonResponse::create(['status' => 0, 'msg' => '余额不足' . $price . '钻']);
        $userService->setUser((new Users)->forceFill($user));
        $num = $userService->getModNickNameStatus()['num'];
        if ($num >= 1) return JsonResponse::create(['status' => 0, 'msg' => '已有修改昵称资格']);
        $redis = $this->make('redis');
        /** 扣钱给资格 */
        if (Users::where('uid', $uid)->where('points', '>=', $price)->decrement('points', $price)) {
            $redis->hIncrBy('huser_info:' . $uid, 'points', $price * -1);
            $redis->hIncrBy('modify.nickname', $uid, 1);
            return JsonResponse::create(['status' => 1, 'msg' => '购买成功']);
        } else {
            return JsonResponse::create(['status' => 0, 'msg' => '扣款失败']);
        }
    }

    /**添加用户到一对多
     * @param $onetomany
     * @param $uid
     * @param int $origin
     * @param int $points 0后台添加，-1使用房间价格全额
     * @return array
     */
    protected function addOneToManyRoomUser($rid, $onetomany, $uid, $origin = 13, $points = 0)
    {
        /** @var \Redis $redis */
        $redis = $this->make('redis');
        if (empty($onetomany) || empty($uid)) return [0, '参数错误'];
        $room = $redis->hgetall("hroom_whitelist:$rid:$onetomany");
        if (empty($room)) return [0, '房间不存在'];
        if ($rid == $uid) return [0, '主播不能购买自己的一对多'];//不能添加主播自己
        if (strtotime($room['endtime']) < time()) return [0, '房间已经结束'];//房间已经结束

        if (in_array($uid, explode(',', $room['uids']))) return [0, '您已有资格进入该房间，请从“我的预约”进入。'];
        if (UserBuyOneToMore::query()->where('onetomore', $onetomany)->where('uid', $uid)->first()) return [0, '您已有资格进入该房间，请从“我的预约”进入。'];//redis数据可能出错
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
            1, '添加成功', 'data' =>
                ['points' => $points]
        ];
    }
}

