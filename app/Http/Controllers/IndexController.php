<?php

namespace App\Http\Controllers;

use App\Facades\SiteSer;
use App\Facades\UserSer;
use App\Models\Complaints;
use App\Models\RoomDuration;
use App\Models\RoomStatus;
use App\Models\UserBuyOneToMore;
use App\Models\Users;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Faq;
use App\Services\User\UserService;

class IndexController extends Controller
{

    public function indexAction()
    {
        return '移除';
    }

    /**
     * 首页数据获取的地址
     * type 是获取的种类
     *
     * @return Response
     */
    public function videoList()
    {
        //$type 判断
        $type = request()->get('type', 'all');

        //为什么总在$flashVer??
        //updata by Young
        //获取flash版本
        $flashVer = SiteSer::config('publish_version');
        !$flashVer && $flashVer = 'v201504092044';

        //初始化$list数据
        $list = [
            'rooms' => [],
        ];

        /**
         * 首页中排行榜数据 TODO 修改调取接口
         */
        switch ($type) {
            case 'rank':
                $list = Redis::get('home_js_data_' . $flashVer . ':' . SiteSer::siteId());
                $list = str_replace(['cb(', ');'], ['', ''], $list);
                break;

            case 'fav':
                $uid = Auth::id();
                $myfavArr = $this->getUserAttensBycuruid($uid);
                $list = Redis::get('home_all_' . $flashVer);
                $list = str_replace(['cb(', ');'], ['', ''], $list);
                $data = json_decode($list, true);

                //echo $data; die;
                $myfav = [];
                if ($myfavArr) {
                    //过滤出主播
                    $hasharr = [];
                    foreach ($data['rooms'] as $value) {
                        $hasharr[$value['uid']] = $value;
                    }

                    foreach ($myfavArr as $item) {
                        if (isset($hasharr[$item])) {
                            $myfav[] = $hasharr[$item];
                        }
                    }
                }
                $data['rooms'] = $myfav;
                return JsonResponse::create($data);
                break;

            default:
                $list = Redis::get('home_' . $type . '_' . $flashVer . ':' . SiteSer::siteId());
                $list = str_replace(['cb(', ');'], '', $list);
        }


//        $list = json_decode($list, true);
        //兼容论坛结构，数据移动到data外！
        //   return JsonResponse::create(json_decode($list ?: '{}'));

        $result = json_decode($list ?: '{}');
        $result->data = json_decode($list ?: '{}');

        return JsonResponse::create($result);
    }

    /**
     * 设置用户进入时长房间状态
     * @author raby
     */
    public function setInRoomStat()
    {
        $roomid = $this->make('request')->get('roomid');
        if (!$roomid) return new JsonResponse(['status' => 2, 'msg' => __('messages.Index.setInRoomStat.invalid_param')]);

        $data = RoomStatus::where('uid', $roomid)
            ->where('tid', 6)->where('status', 1)->first();
        if (!$data) return new JsonResponse(['status' => 3, 'msg' => __('messages.Index.setInRoomStat.not_time_room')]);

        Redis::hset('htimecost_watch:' . $roomid, Auth::id(), $roomid);
        return new JsonResponse(['status' => 1, 'msg' => __('messages.Index.setInRoomStat.set_success')]);
    }

    /**
     * @param string $type
     */
    public function checkUniqueName()
    {
        $type = $this->request()->get('type');
        $limitData = ['username', 'nickname'];
        if (!in_array($type, $limitData)) {
            return new Response(json_encode([
                'msg' => __('messages.Index.checkUniqueName.invalid_param'),
                'data' => 0,
            ]));
        }
        $username = $this->request()->get('username');
        if ($type == 'username') {
            if (!preg_match('/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/', $username) || strlen($username) < 5 || strlen($username) > 30) {
                return new Response(json_encode([
                    "data" => 0,
                    "msg" => __('messages.Index.checkUniqueName.invalid_email'),
                ]));
            }
            $msg0 = __('messages.Index.checkUniqueName.email_had_been_use');
            $msg1 = __('messages.Index.checkUniqueName.email_can_be_use');

            $userArr = explode("@", $username);

            if (count($userArr) === 2) {
                $username = $userArr[0] . "@" . strtolower($userArr[1]);
            }

            if (Users::where('username', $username)->where('site_id', SiteSer::siteId())->exists()) {
                return new Response(json_encode([
                    'msg'  => $msg0,
                    'data' => 0,
                ]));
            } else {
                return new Response(json_encode([
                    'msg' => $msg1,
                    'data' => 1,
                ]));
            }
        } else {
            $len = $this->count_chinese_utf8($username);
            if ($len < 2 || $len > 11 || !preg_match("/^[^\s\/\:;]+$/", $username)) {
                return new Response(json_encode([
                    "data" => 0,
                    "msg" => __('messages.Index.checkUniqueName.nickname_format_error'),
                ]));
            }
            $msg0 = __('messages.Index.checkUniqueName.nickname_had_been_use');
            $msg1 = __('messages.Index.checkUniqueName.nickname_can_be_use');
        }
        $row = UserSer::getUserByUsername($username);
        if (!!$row) {
            return new Response(json_encode([
                'msg' => $msg0,
                'data' => 0,
            ]));
        } else {
            return new Response(json_encode([
                'msg' => $msg1,
                'data' => 1,
            ]));
        }
    }

    /**
     * 7月3号 由clark确认，关注部分代码和indexinfo不分离
     * todo 可删除关注部分的代码
     * @return Response
     * @throws \Exception
     */
    public function getIndexInfo()
    {
        $uid = Auth::id();
        //公告
        $notice = $this->make('redis')->hgetAll('system_notices:' . SiteSer::siteId());

        $qq_url = Redis::hget('hsite_config:' . SiteSer::siteId(), 'qq_url');
        $qq_side = Redis::hget('hsite_config:' . SiteSer::siteId(), 'qq_side');
        $email_url = Redis::hget('hsite_config:' . SiteSer::siteId(), 'email_url');


        $flashVersion = SiteSer::config('publish_version');
        // 经clark确认，获取我的关注的数据主播的数据。这个目前保留放在本接口（indexinfo）里
        $myfav = [];
        if ($uid) {
            //主播列表
            // $arr = include Storage::path('cache/anchor-search-data.php');
            //通过redis获取主播信息
            $userServer = resolve(UserService::class);
            $arr = $userServer->anchorlist();

//            $hasharr = [];
//            foreach ($arr as $value) {
//                $hasharr[$value['uid']] = $value;
//            }
//            unset($arr);
//            $myfavArr = $this->getUserAttensBycuruid($uid);
//            if (!!$myfavArr) {
//                //过滤出主播
//                foreach ($myfavArr as $item) {
//                    if (isset($hasharr[$item])) {
//                        $myfav[] = $hasharr[$item];
//                    }
//                }
//            }

            $myfavArr = $this->getUserAttensBycuruid($uid, $start = 0, $limit = 400);
            if (!!$myfavArr) {
                $myfav = collect($arr)->whereIn('uid', $myfavArr)->toArray();
                unset($myfavArr);
                unset($arr);
            }
        }


        // 获取我的预约的房间
        $myres = [];
        if ($uid) {
            $myReservation = RoomDuration::with('anchor')->where('reuid', $uid)
                ->where('endtime', '>', date('Y-m-d H:i:s', time()))
                ->orderBy('starttime', 'desc')
                ->get();
            if (!empty($myReservation)) {
                foreach ($myReservation as $item) {
                    $roomInfo = Redis::hgetall('hvediosKtv:' . $item->uid);
//                    $room['listType'] = 'myres';
                    $room['id'] = $item->id;
                    $room['rid'] = $item->uid;
                    $room['tid'] = 4;
                    $room['nickname'] = $item->anchor->nickname;
                    $room['cover'] = $item->anchor->cover;
                    $room['start_time'] = $item->starttime;
                    $room['end_time'] = $item->endtime;
                    $room['duration'] = $item->duration;
                    $room['one_to_one_status'] = 1;
                    $room['origin'] = $item->origin;
                    $room['new_user'] = intval(Redis::hget('huser_icon:' . $item->uid, 'new_user'));
                    $room['live_status'] = isset($roomInfo['status']) ? intval($roomInfo['status']) : 0;
                    $room['top'] = isset($roomInfo['top']) ? intval($roomInfo['top']) : 0;
                    $myres[] = $room;
                }
            }
            unset($myReservation);
        }

        // 从redis 获取一对多预约数据
        $myticket = [];
        if ($uid) {
            $oneToMore = UserBuyOneToMore::where('uid', $uid)->orderBy('starttime', 'desc')->get();//@TODO 时间过滤
            if (!empty($oneToMore)) {
                $oneManyRooms = Redis::get('home_one_many_' . $flashVersion . ':' . SiteSer::siteId());
                $oneManyRooms = str_replace(['cb(', ');'], ['', ''], $oneManyRooms);
                $oneManyRooms = json_decode($oneManyRooms, true);
                $rooms = $oneManyRooms['rooms'];//考虑做个redis的配置
                if ($rooms) {
                    foreach ($oneToMore as $item) {
                        foreach ($rooms as $roomp) {
                            if ($item->rid == $roomp['uid'] && $item->onetomore == $roomp['id']) {
//                                $room['listType'] = 'myticket';
                                $room = Arr::only($roomp,['id','rid','username','cover','start_time','end_time','duration','origin','new_user','live_status']);
                                $room['tid'] = 7;
                                $room['one_to_many_status'] = 1;
                                $room['top'] = isset($roomp['top']) ? $roomp['top'] : 0;
                                $myres[] = $room;
                            }
                        }
                    }
                }
            }
            unset($oneToMore);
        }

        $data = [
            'status' => 1,
            'uid' => $uid,
            'myfav' => $myfav,
            'myres' => $myres,
            'myticket' => $myticket,
            'notice' => $notice,
            'qqurl' => is_null($qq_url) ? '' : $qq_url,
            'qqside' => is_null($qq_side) ? '' : $qq_side,
            'emailurl' => is_null($email_url) ? '' : $email_url,

        ];
        array_walk($data,function (&$item) {
            if (\is_array($item)) {
                $item =  array_values($item);
            }
        });
        $file = base_path("bootstrap/cache/")."config.php";
        $publish = "publish time:".date('Y-m-d H:i:s',filectime($file));
        return JsonResponse::create(
            [
                'data' =>$data,
                'msg' => __('messages.Index.getIndexInfo.success') . $publish,
                'status' => 1,
            ]
        );
    }

    /**
     * 首页获取帮助信息
     * @Author bart
     */
    public function getHelp($sort, $num)
    {
        $redis = $this->make('redis');
        $ids = $redis->get('video:faq:sort:class:' . $sort);
        if (!empty($ids)) {
            $ids = json_decode($ids);
            $data = Faq::whereIn('id', $ids)->limit($num)->get();
        } else {
            $data = [];
        }
        return JsonResponse::create([
            'status' => 1,
            'data' => $data,
        ]);
    }

    /**
     * 获取时间差的时分秒
     * @Author Orino
     */
    public function Hms($microtime)
    {
        $cha = (time() * 1000 - $microtime) / 1000;
        $hour = floor($cha / 60 / 60);
        $minute = floor(($cha - $hour * 60 * 60) / 60);
        $second = floor($cha - $hour * 60 * 60 - $minute * 60);
        $str = '';
        if ($hour > 0) {
            $str .= $hour . '时';
        }
        if ($minute > 0) {
            $str .= $minute . '分';
        }
        if ($second > 0) {
            $str .= $second . '秒';
        }
        return $str;
    }

    /**
     * 邀请注册推广用户处理逻辑方法
     * @author D.C
     * @update 2014.12.12
     * @param int $register_uid
     * @return bool
     */
    private function _userRegisterByInvitation($register_uid = 0)
    {

        //用户推广链接活动开关
        if (!SiteSer::config('invitation_status')) return false;

        //注册失败
        if (!$register_uid) return false;

        //检查Cookie是否存在,并获取推广人ID
        $invitation_uid = $this->request()->cookies->get('invitation_uid');
        if (!$invitation_uid) return false;

        //获取客户端IP，用于反作弊预处理
        $ips = $this->request()->getClientIp();

        //初始化Redis对象
        $redis = $this->make('redis');

        //防作弊处理,暂时未启用
        /*
        if($redis->hexists('invitation', $invitation_uid)){
            $i_user = unserialize($redis->hget('invitation', $invitation_uid));
            //一小时内同IP注册视为作弊，过滤处理
            if( ($i_user['time']+3600)<time() &&  $i_user['ips'] == $ips ){
                //return false;
            }
        }
        */

        $redis->hset('invitation', $invitation_uid, serialize(['time' => time(), 'ips' => $ips]));

        //奖励点数
        $addPoints = 100;

        /**
         * 进行赠送奖励流程处理,调用【Gesila】接口。目的：实时更新数据库、Redis、房间状态。
         * @example /video_gs/web_api/add_point?uid=10000&points=1&act_name=invitation_user
         * @param uid = 用户ID，points=增加点数，act_name=活动名称
         * @return  1=成功，-102=增加失败，-103=来源IP没有权限，-104=用户不存在
         */
        $apiURL = rtrim($GLOBALS['REMOTE_JS_URL'], '/') . '/video_gs/web_api/add_point?uid=' . $invitation_uid . '&points=' . $addPoints . '&act_name=invitation_user';
        $getPointsStatus = null;
        try {
            $getPointsStatus = file_get_contents($apiURL);
        } catch (\Exception $e) {
            return false;
        }
        $getPointsStatus = json_decode($getPointsStatus);

        //奖励失败处理
        if (is_null($getPointsStatus) || !$getPointsStatus->ret) {
            return false;
        }

        //数据库记录操作处理
        //为了优化性能不查询已存在用户

        UserInvitation::create([
            'pid' => $invitation_uid,
            'uid' => $register_uid,
            'reward' => $addPoints,
            'fromip' => $ips,
            'fromtime' => date('Y-m-d H:i:s', time()),
            'fromlink' => $this->request()->cookies->get('invitation_refer'),
        ]);

        //邀请成功，发送消息通知
        $this->make('messageServer')->sendSystemToUsersMessage(['rec_uid' => $invitation_uid, 'content' => '恭喜您成功邀请了一位新伙伴加入了蜜桃儿，并获得100个钻石的邀请奖励，要想获得更多奖励，请继续邀请您的亲朋好友们加入我们吧。']);
        return true;
    }

    /**
     * 投诉建议
     * @return Response
     */
    public function complaints(Request $request)
    {
        $date = date('Y-m-d H:i:s');
        $today_nu = date('Ymd');
        $uid = $request->getSession()->get(self::SEVER_SESS_ID);
        $hname = 'keys_complaints_flag:' . $uid . ':' . $today_nu;
        $times = Redis::get($hname);
        if (empty($times)) {
            Redis::set($hname, 1);
        } else {
            if ($times >= 10) return JsonResponse::create([
                'status' => 0,
                'data' => ['times' => $times],
                'msg' => __('messages.Index.complaints.success'),
            ]);
            Redis::set($hname, $times + 1);
        }
        $data = [
            'cid' => $request->get('sername'),
            'uid' => Auth::id(),
            'type' => intval($request->get('sertype')),
            'content' => $request->get('sercontent'),
            'created' => $date,
            'edit_time' => $date,
        ];
        if (!$data['content']) {
            return JsonResponse::create([
                'status' => 0,
                'data' => ['times' => $times],
                'msg' => __('messages.Index.complaints.content_required'),
            ]);
        }
        Complaints::create($data);
        return JsonResponse::create([
            'status' => 1,
            'data' => ['times' => $times],
            'msg' => __('messages.Index.complaints.success'),
        ]);
    }

    /**
     * php-cli通过curl调用获取,通过内部访问，维护代码方便
     */
    public function cliGetRes()
    {
        $action = $this->request()->get('act');
        if ($action == null || !isset($_REQUEST['vsign']) || SiteSer::config('vfphp_sign') != $_REQUEST['vsign'] ||
            !method_exists($this, $action)
        ) {
            return new JsonResponse(['status' => 1, 'data' => 'data is not available!']);
        }
        $data = [];
        /*批量出来获取主播房间类型*/
        if ($action == 'getAnchorRoomType' && isset($_REQUEST['ridlists'])) {
            $ridlists = explode(',', $_REQUEST['ridlists']);
            if (!$ridlists) {
                return new JsonResponse(['status' => 2, 'data' => 'data is not available!']);
            }

            foreach ($ridlists as $item) {
                $data[$item] = $this->getAnchorRoomType($item);
            }
        }
        return new JsonResponse(['status' => 0, 'data' => $data]);
    }


    /*
     * 主播招募配置信息接口 by desmond 2018-06-21
     */
    public function anchor_join()
    {
        $site_id = SiteSer::siteId();
        $anchor_join = Redis::hgetall('anchor_join:' . $site_id);
        $result = [];
        if (isset($anchor_join)) {
            foreach ($anchor_join as $key => $value) {
                $temp = json_decode($value);
                array_push($result, $temp);
            }
            return new JsonResponse(['status' => 1, 'data' => $result, 'msg' => __('messages.success')]);
        }
        return new JsonResponse(['status' => 0, 'data' => $result, 'msg' => __('messages.Index.anchor_join.failed')]);
    }
}
