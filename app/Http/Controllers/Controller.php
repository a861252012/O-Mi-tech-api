<?php

namespace App\Http\Controllers;

use App\Facades\SiteSer;
use App\Facades\UserSer;
use App\Models\AnchorGroup;
use App\Models\Area;
use App\Models\Attention;
use App\Models\BirthStar;
use App\Models\Goods;
use App\Models\Keywords;
use App\Models\LevelRich;
use App\Models\LiveList;
use App\Models\MallList;
use App\Models\Messages;
use App\Models\Pack;
use App\Models\Recharge;
use App\Models\RoomDuration;
use App\Models\RoomOneToMore;
use App\Models\UserBuyGroup;
use App\Models\UserGroup;
use App\Models\UserModNickName;
use App\Models\Users;
use App\Models\Usersall;
use App\Models\VideoMail;
use App\Models\WithDrawalList;
use App\Models\WithDrawalRules;
use App\Services\User\UserService;
use App\Traits\ApiOutput;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Mews\Captcha\Facades\Captcha;
use App\Services\Site\SiteService;


//use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, ApiOutput;

    const CLIENT_ENCRY_FIELD = 'v_remember_encrypt';
    //const DOMAIN_A = 'domain_a';
    const SEVER_SESS_ID = 'webonline';//在线用户id
    const  TOKEN_CONST = 'auth_key';
    const  WEB_SECRET_KEY = 'c5ff645187eb7245d43178f20607920e456';
    const TTL_USER_NICKNAME = 2678400;

    protected $_online; // 在线用户的uid
    protected $userInfo; // 在线用户的信息
    protected $_reqSession;
    protected $_sess_id;
    protected $flash_url = '';
    protected $publish_version = '';
    public $_isGetCache = false;
    public $container = null;


    public function __construct(Request $request)
    {

        if ($this->isMobileUrl($request)) {
            config()->set('auth.defaults.guard', 'mobile');
        } else {
            config()->set('auth.defaults.guard', 'pc');
        }
        $this->_online = Auth::id();
        $this->userInfo = Auth::user();
        $this->container = app();
    }

    public function isMobileUrl(Request $request)
    {
        return $request->is('api/m/*');
    }

    /**
     * @param string $name
     * @return \Illuminate\Redis\Connections\Connection|null
     */
    public function make($name = "")
    {
        return resolve($name);
    }

    /**
     */
    public function request()
    {
        return request();
    }

    /**
     * * 全站注册/登录密码解密函数
     * @param $s
     * @return string
     * @author D.C
     * @update 2015-02-04
     */
    public function decode($o = "")
    {
        $a = str_split($o, 2);
        $s = '%' . implode('%', $a);
        $s = urldecode($s);

        return !isset($_REQUEST['_m']) ? trim($s) : $o;
    }


    /**
     * 时段房间互拆（一对一，一对多）
     * 返回 true 不重叠 false重叠
     */
    public function notSetRepeat($start_time, $endtime)
    {
        $now = date('Y-m-d H:i:s');
        //时间，是否和一对一有重叠
        $data = RoomDuration::where('status', 0)->where('uid', Auth::id())
            ->orderBy('starttime', 'DESC')
            ->get()->toArray();

        $temp_data = $this->array_column_multi($data, ['starttime', 'endtime']);
        if (!$this->checkActiveTime($start_time, $endtime, $temp_data)) return false;

        //时间，是否和一对多有重叠
        $data = RoomOneToMore::where('status', 0)->where('uid', Auth::id())->get()->toArray();
        $temp_data = $this->array_column_multi($data, ['starttime', 'endtime']);
        if (!$this->checkActiveTime($start_time, $endtime, $temp_data)) return false;
        return true;
    }

    /**
     * @param string $stime
     * @param string $etime
     * @param array $data
     * @return bool false重叠 true不重叠
     */
    public function checkActiveTime($stime = '', $etime = '', $data = [])
    {
        $stime = strtotime($stime);
        $etime = strtotime($etime);

        $flag = true;
        foreach ($data as $k => $v) {
            //开始时间在区间之内
            if ($stime >= strtotime($v['starttime']) && $stime <= strtotime($v['endtime'])) {
                $flag = false;
                break;
            }
            //结束时间在区间之内
            if ($etime >= strtotime($v['starttime']) && $etime <= strtotime($v['endtime'])) {
                $flag = false;
                break;
            }
            //包含
            if ($stime <= strtotime($v['starttime']) && $etime >= strtotime($v['endtime'])) {
                $flag = false;
                break;
            }
        }
        return $flag;
    }

    /**
     * @param $uid
     * @param $auto_id
     */
    public function setRoomWhiteKey($uid, $auto_id)
    {
        $ids = Redis::hget('hroom_whitelist_key', $uid);
        if ($ids) {
            $ids .= ',' . $auto_id;
        } else {
            $ids = $auto_id;
        }
        Redis::hset('hroom_whitelist_key', $uid, $ids);
        return true;
    }

    /**
     * @param $year
     * @return bool|string
     */
    public function getAge($year)
    {
        return date('Y') - $year;
    }

    /**
     * 计算utf8中文字长度
     *
     * @param $str
     * @return int
     */
    public function count_chinese_utf8($str)
    {
        $arr = preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
        return count($arr);
    }

    /**
     * 获取自己被别人关注的数量/获取自己关注别人的数量
     * @param      $uid
     * @param bool $flag
     * @return mixed
     * @Author Orino
     */
    public function getUserAttensCount($uid, $flag = true)
    {
        if ($flag) {
            return Redis::zcard('zuser_attens:' . $uid);//获取自己关注别人的数量
        } else {
            return Redis::zcard('zuser_byattens:' . $uid);//获取自己被别人关注的数量
        }
    }

    /**
     * 获取月份对应的星座,$monthday = '118'等价于01月18号
     * @param int $monthday
     * @return mixed
     */
    public function getStarNames($monthday = 0)
    {
        $data = [];
        if (Redis::exists('hstar_names')) {
            $data = Redis::hgetall('hstar_names');
        } else {
            $data = BirthStar::orderBy('id', 'ASC')->get();
            $tmpArr = [];
            $len = count($data);
            for ($i = 0; $i < $len; $i++) {
                $tmpArr[$data[$i]['monthday']] = $data[$i]['starname'];
            }
            $data = $tmpArr;
            Redis::hmset('hstar_names', $data);
        }
        if ($monthday == 0)
            return current($data);
        foreach ($data as $key => $item) {
            if ($monthday <= $key) {
                return $item;
            }
        }
        return end($data);
    }

    /**
     * 经领导确认验证规则这边保持和以前一致
     * @param $request
     * @return string
     */
    public function doChangePwd($request)
    {
        $username = $request->get('username');
        $password = $this->decode(trim($request->get('password')));
        $password1 = $this->decode(trim($request->get('password1')));
        $password2 = $this->decode(trim($request->get('password2')));
        if (empty($username) || empty($password1) || empty($password2)) {
            return json_encode(array(
                "status" => 0,
                "msg" => '用户名或密码不能为空',
            ));
        }
        //用户名规则限制
        if (!preg_match('/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/', $username) || strlen($username) < 5 || strlen($username) > 30) {
            return json_encode(array(
                "status" => 0,
                "msg" => "用户名不合法",
            ));
        }

        //新密码规则限制
        if ($this->checkPasswordVaild($password1) || $this->checkPasswordVaild($password2)) {
            return json_encode(array(
                "status" => 0,
                "msg" => "密码不合法！"
            ));
        }

        if ($password == $password1) {
            return json_encode(array(
                "status" => 0,
                "msg" => "新旧密码不能相同"
            ));
        }
        if ($password1 != $password2) {
            return json_encode(array(
                "status" => 0,
                "msg" => "新密码两次输入不一致"
            ));
        }
        //用户名是否存在
        $uid = UserSer::getUidByUsername($username);
        if (empty($uid)) return json_encode(array(
            "status" => 0,
            "msg" => "用户名不存在"
        ));

        //是否已修改过
        $user = UserSer::getUserByUid($uid);

        //旧密码是否正确
        $old_password = md5($password);
        if ($user['password'] != $old_password) return json_encode(array(
            "status" => 0,
            "msg" => "旧密码验证失败"
        ));

        //修改新密码，更新状态及时间
        Users::query()->where('uid', $uid)->update([
            'password' => md5($password1),
            'pwd_change' => 1,
            'cpwd_time' => date('Y-m-d H:i:s'),
        ]);
        UserSer::getUserReset($uid);
        return json_encode(array(
            "status" => 1,
            "msg" => "修改成功"
        ));
    }

    public function checkPasswordVaild($password1 = "")
    {
        return strlen($password1) < 6 || strlen($password1) > 22;
    }

    /**
     * 渲染模板
     *
     * @param $tpl
     * @param $params
     */
    public function render($tpl, $params = [])
    {
        // 必须以html.twig结尾
        return JsonResponse::create($params);
    }

    /**
     * 给变量赋值
     *
     * @param string|array $var
     * @param string $value
     */
    public function assign($var, $value = NULL)
    {
        if (is_array($var)) {
            foreach ($var as $key => $val) {
                $this->data[$key] = $val;
            }
        } else {
            $this->data[$var] = $value;
        }
    }

    /**
     * 获取输出的用户信息 TODO
     *
     * @param $userInfo
     * @return mixed
     */
    public function getOutputUser($userInfo, $size = 150, $pct = true)
    {
        if (!$userInfo) return [];
        //$userInfo['nickname_sub'] = mb_substr($userInfo['nickname'],0,10,'utf-8');
        $userInfo['headimg'] = $this->getHeadimg($userInfo['headimg'], $size);
        if ($userInfo['birthday']) {
            $date = explode('-', $userInfo['birthday']);
            $userInfo['age'] = $this->getAge($date[0]);
            $date = intval($date[1] . $date[2]);
            $userInfo['starname'] = $this->getStarNames($date);
        }
        $userInfo['age'] = isset($userInfo['age']) ? $userInfo['age'] . '岁' : '永远18岁';
        $userInfo['starname'] = isset($userInfo['starname']) ? $userInfo['starname'] : '神秘星座';
        $userInfo['description'] = $userInfo['description'] ?: '此人好懒，大家帮TA想想写些什么！';
        if (!$userInfo['province'] || !$userInfo['city']) {
            $userInfo['procity'] = '中国的某个角落';
        } else {
            $userInfo['procity'] = $this->getArea($userInfo['province'], $userInfo['city'], $userInfo['county']);//取地区code对应的地区名称
        }
        // return $userInfo;
        $data = [
            'headimg' => $userInfo['headimg'],
            'uid' => strval($userInfo['uid']),
            'nickname' => $userInfo['nickname'],
            'vip' => $userInfo['vip'],
            'vip_end' => $userInfo['vip_end'],
            'lv_rich' => strval($userInfo['lv_rich']),
            'lv_exp' => strval($userInfo['lv_exp']),
            'roled' => $userInfo['roled'],
            'birthday' => $userInfo['birthday'],
            'age' => $userInfo['age'],
            'starname' => $userInfo['starname'],
            'description' => $userInfo['description'],
            'procity' => $userInfo['procity'],
            'sex' => $userInfo['sex'],
            'safemail' => $userInfo['safemail'],
            'icon_id' => intval($userInfo['icon_id']),
        ];
        if (!$userInfo['province'] || !$userInfo['city']) {
            $data['procity'] = '中国的某个角落';
        } else {
            $data['procity'] = $this->getArea($userInfo['province'], $userInfo['city']);//取地区code对应的地区名称
        }
        //  $data['attens'] = strval($this->_data_model->getAttenCount($userInfo['uid'],$field='t.tid '));
        $data['attens'] = strval($this->getUserAttensCount($userInfo['uid'], false));
        $data['space_url'] = request()->getSchemeAndHttpHost() . '/space?u=' . $userInfo['uid'];
        if ($pct) {
            $data['province'] = $userInfo['province'];
            $data['city'] = $userInfo['city'];
            $data['county'] = $userInfo['county'];
            $data['rid'] = $userInfo['rid'];
            $data['rich'] = $userInfo['rich'];
//            $data['room_url'] = $GLOBALS['REMOTE_JS_URL'];
        } else {
            $data['sex'] = $userInfo['sex'] == 1 ? '男' : '女';//接口调用所需要的名称
            $data['username'] = $userInfo['username'];
        }
        return $data;
    }

    /**
     * 查询提款余额 TODO
     *
     * @param $uid
     * @return int
     *
     */
    public function getAvailableBalance($uid, $status = 0)
    {
        $userServer = resolve(UserService::class);
        $userinfo = $userServer->getUserByUid($uid);
        if (empty($userinfo)) return false;
        if ($userinfo['lv_type'] == 1) {
            $date_start = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m") - 1, 1, date("Y")));
            $date_end = date("Y-m-d H:i:s", mktime(23, 59, 59, date("m"), 0, date("Y")));
            $date_with_start = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), 1, date("Y")));
            $date_with_end = date("Y-m-d H:i:s", mktime(23, 59, 59, date("m"), date("t"), date("Y")));
        } else {
            $date_start = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d") - date("w") + 1 - 7, date("Y")));
            $date_end = date("Y-m-d H:i:s", mktime(23, 59, 59, date("m"), date("d") - date("w") + 7 - 7, date("Y")));
            $date_with_start = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d") - date("w") + 1, date("Y")));
            $date_with_end = date("Y-m-d H:i:s", mktime(23, 59, 59, date("m"), date("d") - date("w") + 7, date("Y")));
        }
//        $sumlive = $this->get('database_connection')->fetchAll('SELECT SUM(points) as sum_points FROM video_mall_list WHERE rec_uid = '.$uid .' AND created >'."'$date_start'".'  AND created < '."'$date_end'")[0]['sum_points'];
        $sumlive = MallList::where('rec_uid', $uid)
            ->where('created', '>', $date_start)->where('created', '<', $date_end)->sum('points');
//        $sumDuration = $this->get('database_connection')->fetchAll('SELECT SUM(duration) as sum_duration FROM video_live_list WHERE uid = '.$uid .' AND start_time >'."'$date_start'".'  AND start_time < '."'$date_end'")[0]['sum_duration'];
        $sumDuration = LiveList::where('uid', $uid)->where('start_time', '>', $date_start)
            ->where('start_time', '<', $date_end)->sum('duration');
//        $sumlist = $this->get('database_connection')->fetchAll('SELECT SUM(moneypoints) as sum_points FROM video_withdrawal_list WHERE uid = '.$uid .' AND video_withdrawal_list.status in(1,'.$status.')   AND created >'."'$date_with_start'".'  AND created < '."'$date_with_end'")[0]['sum_points'];
        $sumlist = WithDrawalList::where('uid', $uid)->where('status', 'in', [1, $status])
            ->where('created', '>', $date_with_start)->where('created', '<', $date_with_end)
            ->sum('moneypoints');
        $result['availmoney'] = 0;
        $result['availpoints'] = 0;
        if (empty($sumDuration)) {
            return $result;
        }
        $otype = $this->ordinary($sumlive, $userinfo['lv_type']);
        if (isset($otype['duration']) && $sumDuration <= $otype['duration']) {
            return $result;
        }
        if (empty($sumlist)) $sumlist = 0;
        $avail = $sumlive - $sumlist;
        if ($avail < 0) $avail = 0;
//        $ticheng_company_percent =$this->getDoctrine()->getManager()->getRepository('Video\ProjectBundle\Entity\VideoConf')
//            ->findOneBy(array('name'=>'ticheng_company_percent'));

        $siteConfig = app(SiteService::class)->config();
        $ticheng_company_percent = $siteConfig->get('ticheng_company_percent');
        if (empty($ticheng_company_percent)) {
            Redis::hSet('hsite_config:' . SiteSer::siteId(), 'ticheng_company_percent', 30);
            $ticheng_company_percent = $siteConfig->get('ticheng_company_percent');
        }

        $avi = ($avail / 10) * $otype['rpercentage'] * (100 - $ticheng_company_percent) / 100;
        $result['availmoney'] = (int)floor($avi);
        $result['availpoints'] = $avail;
        return $result;

    }

    /**
     * @param $money TODO
     * @return int
     *               通过钱数获取对应的钻石数
     */
    public function BalanceToOponts($money, $uid)
    {
        $userServer = resolve(UserService::class);
        $userinfo = $userServer->getUserByUid($uid);
        if ($userinfo['lv_type'] == 1) {
            $date_start = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m") - 1, 1, date("Y")));
            $date_end = date("Y-m-d H:i:s", mktime(23, 59, 59, date("m"), 0, date("Y")));
        } else {
            $date_start = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d") - date("w") + 1 - 7, date("Y")));
            $date_end = date("Y-m-d H:i:s", mktime(23, 59, 59, date("m"), date("d") - date("w") + 7 - 7, date("Y")));
        }
//        $sumlive = $this->get('database_connection')
//            ->fetchAll('SELECT SUM(points) as sum_points FROM video_mall_list WHERE rec_uid = '.$uid .' AND created >'."'$date_start'".'  AND created < '."'$date_end'")[0]['sum_points'];
        $sumlive = MallList::where('rec_uid', $uid)->where('created', '>', $date_start)->where('created', '<', $date_end)->sum('points');
        if (empty($sumlive)) {
            return 0;
        }
        $otype = $this->ordinary($sumlive, $userinfo['lv_type']);
        if (isset($otype['duration']) && $sumlive <= $otype['duration']) {
            return 0;
        }
//        $ticheng_company_percent =$this->getDoctrine()->getManager()->getRepository('Video\ProjectBundle\Entity\VideoConf')->findOneBy(array('name'=>'ticheng_company_percent'));
        $siteConfig = app(SiteService::class)->config();
        $ticheng_company_percent = $siteConfig->get('ticheng_company_percent');
        if (empty($ticheng_company_percent)) {
            Redis::hSet('hsite_config:' . SiteSer::siteId(), 'ticheng_company_percent', 30);
            $ticheng_company_percent = $siteConfig->get('ticheng_company_percent');
        }
        $avi = ($money * 10) / ($otype['rpercentage'] * ((100 - $ticheng_company_percent) / 100));
        return $avi;

    }


    /**
     * @param $points
     * @param $type
     * @return mixed
     * 获取对应的提款级别
     */
    public function ordinary($points, $type)
    {
        $typearr = [1, 2, 3];
        if (!in_array($type, $typearr)) return 0;
//        $otype = $this->get('database_connection')
//            ->fetchAll('SELECT * FROM video_withdrawal_rules WHERE anchortype = '."'$type'".' AND minincome<='."'$points'".' and maxincome>='."'$points'");
        $obj = new WithDrawalRules();
        $otype = $obj->where('anchortype', $type)->where('minincome', '<=', $points)
            ->where('maxincome', '>=', $points)->first();
        if (empty($otype)) $otype['rpercentage'] = 0.55;
        return $otype;
    }

    /**
     * 前3个参数是省市区的code值，第4个参数是分隔符
     * @return string
     */
    public function getArea()
    {
        $redis_key = 'h_areas';
        $limit = '';
        $argsList = func_get_args();
        if (count($argsList) == 4) {
            $limit = $argsList[3];
            array_pop($argsList);
        }

        if (Redis::Hexists($redis_key, $argsList[0])) {
            $areas = Redis::hmget($redis_key, $argsList);
            //ksort($areas,SORT_NUMERIC);//按键值从小到大排序
            return implode($limit, $areas);
        }
        //从数据库中查找并缓存到Redis
        $area = Area::all();
        $areas = [];
        foreach ($area as $a) {
            $areas[$a->code] = $a->area;
        }
        $area = [];
        $len = count($argsList);
        for ($i = 0; $i < $len; $i++) {
            if (isset($areas[$argsList[$i]])) {
                $area[] = $areas[$argsList[$i]];
            }
        }
        Redis::hmset($redis_key, $areas);
        ksort($area, SORT_NUMERIC);//按键值从小到大排序
        return implode($limit, $area);
    }


    /**
     * 获取下一个等级升级的值,计算升级率，
     * 就是本级别对应的值和下一级别对应的值相差的就是分母，
     * 用户对应的值减去本级别对应的值就是分子
     * Author Orino
     * update by Young. 将运算放置到前端
     */
    public function getLevelByRole($userinfo, $flag = false)
    {
        $cacheKey = 'keys_level_';//k->v

        //身份判断
        if ($userinfo['roled'] == 3) {

            //主播身份
            //等级从1级开始
            $modle = new AnchorGroup();
            $levelid = $userinfo['lv_exp'];
            $cacheKey .= 'exp';
        } else {

            //用户身份
            $modle = new UserGroup();
            $levelid = $userinfo['lv_rich'];
            $cacheKey .= 'rich';
        }

        //读取redis数据
        if (Redis::exists($cacheKey)) {
            $hashData = Redis::get($cacheKey);
            $hashData = json_decode($hashData, true);
        } else {
            $data = $modle->all();
            $hashData = [];
            foreach ($data as $item) {
                $hashData[$item->level_id] = $item->getAttributes();
            }
            Redis::set($cacheKey, json_encode($hashData, JSON_UNESCAPED_SLASHES));
        }

        //currentExp 当前等级经验值
        //nextExp 下一级等级经验值
        //$dec：当前等级与下一等级的等级差值85000 - 60000 = 25000 差值
        //$currentExp = $hashData[$levelid -1]['level_value'];
        /**
         * 修复当等级为空时报错
         * @author  dc
         * @version 20160325
         */
        if(isset($levelid)) {
            $currentExp = isset($hashData[$levelid]) ? $hashData[$levelid]['level_value'] : 0;
            $nextExp = isset($hashData[$levelid + 1]) ? $hashData[$levelid + 1]['level_value'] : 0;
        } else {
            $currentExp = 0;
            $nextExp =  0;
        }

        //var_dump($hashData[$levelid]); die;

        if ($nextExp != 0) {
            $dec = $nextExp - $currentExp;
        } else {
            //升级到最高级的情况
            return [
                'lv_sub' => 0,
                'lv_next_exp' => 0,
                'lv_current_exp' => $currentExp,
                'lv_percent' => 100,
            ];
        }

        //如果是主播
        //var_dump($hashData[$levelid]['level_value']);
        //var_dump($userinfo['exp']);

        //$num 是离下一等级还剩的等级差
        // if ($userinfo['roled'] == 3) {
        //     $nums = $hashData[$levelid + 1]['level_value'] - $userinfo['exp'];
        // } else {
        //     $nums = $hashData[$levelid + 1]['level_value'] - $userinfo['rich'];
        // }

        //var_dump($nums); die();

        //lv_nums 离下一级升级还差的钻石. 为什么这么写。。。
        // if ($nums < 0) {
        //     $arr = array('lv_nums' => abs($nums), 'lv_percent' => 0);
        // } else {
        //     $arr = array('lv_nums' => $dec - $nums);
        //     if ($flag) {
        //         $arr['lv_percent'] = $nums / $dec * 100;

        //     } else {
        //         $arr['lv_percent'] = $nums / $dec * 10;
        //     }
        // }
        //todo 临时处理,需要判断为何出现非数字变量
        if ($userinfo['roled'] == 3) {
            $subtract = intval($userinfo['exp']) - $currentExp;
        } else {
            $subtract = intval($userinfo['rich']) - $currentExp;
        }

        //var_dump($subtract); die;
        //当前经验值中，已经获得的经验值
        $arr['lv_sub'] = $subtract;

        //下一等级需要的经验值
        $arr['lv_next_exp'] = $nextExp;

        //当前等级需要的经验值
        $arr['lv_current_exp'] = $currentExp;

        //当前等级经验值百分比
        $arr['lv_percent'] = ($subtract / $dec) * 100;

        // var_dump($userinfo["lv_rich"]);
        // var_dump($subtract);
        // var_dump($nextExp);
        // var_dump($currentExp);
        // var_dump($arr['lv_percent']);

        // die;

        $func = function ($v) {
            return abs($v);//强制转化成整数
        };
        $arr = array_map($func, $arr);

        return $arr;
    }

    /**
     * 获取头像地址 默认头像
     *
     * @param        $headimg
     * @param string $size
     * @return string
     */
    public function getHeadimg($headimg, $size = 180)
    {
        return $headimg ? $headimg . ($size == 150 ? '' : '?w=' . $size . '&h=' . $size) : '';
    }


    /**获取推荐的房间ID
     * @param $uid
     * @return mixed
     * @Author TX
     */
    public function getRoomUid($uid)
    {
        $count = Redis::zcard('zuser_reservation:' . $uid);
        if ($count > 0) {
            $reservation = $uids = Redis::zrevrange('zuser_reservation:' . $uid, 0, $count - 1);
        } else {
            $reservation = [];
        }
        $result ['reservation'] = $reservation;
        $count = Redis::zcard('zuser_attens:' . $uid);
        if ($count > 0) {
            $result['attens'] = Redis::zrevrange('zuser_attens:' . $uid, 0, $count - 1);
        } else {
            $result['attens'] = [];
        }
        $result['attens'] = array_diff($result['attens'], $reservation);
        return $result;
    }

    /**
     * 检查房间的唯一性 TODO 尼玛为何要这么写
     *
     * @param $conn
     * @return bool
     * @Author TX
     */
    public function checkRoomUnique($roomDuration, $reuid = 0)
    {
        $room_duration = new RoomDuration();
        if ($reuid == 0) {
            // 这是判断主播自己在同一时间段是否已经有直播的设定了
            $uid = $roomDuration->uid;
            $room_duration = $room_duration->where('uid', $uid);

            $id = $roomDuration->id;
            if (!empty($id)) {
                $room_duration = $room_duration->where('id', '!=', $id);
            }
        } else {
            // 这是判断用户是否已经预约过了其他主播的播放
            $room_duration = $room_duration->where('reuid', $reuid)->where('id', '!=', $roomDuration->id);
        }
        $start = date('Y-m-d H:i:s', strtotime($roomDuration->starttime) - 3600);
        $end = date('Y-m-d H:i:s', strtotime($roomDuration->starttime) + 3600);
        //TODO 为啥不用limit？
        // 判断预约的主播的时间段是否空闲
        $data = $room_duration->where('status', 0)->where('starttime', '>', $start)->where('starttime', '<', $end)
            ->orderBy('starttime', 'DESC')
            ->get();
        $endroom = date('Y-m-d H:i:s', strtotime($roomDuration->starttime) + $roomDuration->duration);
        $startroom = date('Y-m-d H:i:s', strtotime($roomDuration->starttime));
        if (empty($data)) return true;
        foreach ($data as $value) {
            $endvalue = date('Y-m-d H:i:s', strtotime($value->starttime) + $value->duration);
            $startvalue = date('Y-m-d H:i:s', strtotime($value->starttime));
            if ($startvalue < date('Y-m-d H:i:s', time()) && $value->reuid == 0 && $endvalue > date('Y-m-d H:i:s', time())) return true;
            if ($endvalue >= $endroom && $startvalue <= $startroom) return false;
            if ($endvalue <= $endroom && $startvalue >= $startroom) return false;
            if ($startvalue <= $endroom && $startvalue >= $startroom) return false;
            if ($endvalue <= $endroom && $endvalue >= $startroom) return false;

        }
        return true;
    }

    /**设置时长房间的redis
     * @param VideoRoomDuration $durationRoom
     * @return bool
     */

    public function set_durationredis($durationRoom)
    {
        if (empty($durationRoom)) return false;
        $keys = 'hroom_duration:' . $durationRoom->uid . ':' . $durationRoom->roomtid;
        $arrObj = $durationRoom->find($durationRoom->id);
        if (!$arrObj) {
            return false;
        }
        $arr = $arrObj->toArray();
        unset($arr['endtime']);
        Redis::hSet($keys, $arr['id'], json_encode($arr));
        return true;
    }


    /**
     * 检查发起用户是否关注过被关注的用户
     * @param $fid
     * @param $tid
     * @return bool
     * @Author Orino
     */
    public function checkUserAttensExists($fid, $tid, $flag = true, $reservation = false)
    {
        if ($flag) {
            $zkey = 'zuser_attens:';
        } else {
            $zkey = 'zuser_byattens:';
        }
        if ($reservation) {
            $zkey = 'zuser_reservation';
        }
        if (Redis::zscore($zkey . $fid, $tid)) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * @param $points
     * @param $type
     * @return mixed
     * 统计直播总时长
     */
    public function getTotalTime($uid, $start_time, $end_time)
    {
        $otype = LiveList::where('uid', $uid)
            ->where('start_time', '<', $start_time)
            ->where('start_time', '>', $end_time)
            ->sum('duration');
        return $otype;
    }

    /**
     * 直播时间秒数转换为XX小时XX分
     */
    protected function _sec2time($sec)
    {
        $secs = $sec % 60;
        $sec = floor($sec / 60);
        if ($sec >= 60) {
            $hour = floor($sec / 60);
            $min = $sec % 60;
            $res = $hour . ' 小时 ';
            $min != 0 && $res .= $min . ' 分' . $secs . ' 秒';
        } else {
            $res = $sec ? $sec . ' 分' . $secs . ' 秒' : $secs . ' 秒';
        }
        return $res;
    }

    /**
     * 设置二级域名的cookie
     * @param      $key
     * @param null $vaule
     * @param int $time
     * @Author Orino
     */
    protected function setCookieByDomain($key, $vaule = null, $time = 0)
    {
        setcookie($key, $vaule, $time, '/');
    }


    /**
     * [toSql 创建一个监听事件，获取Eloquent的SQL语句]
     * 本功能主要用于调式数据库操作返回SQL
     *
     * @author  dc <dc@wisdominfo.my>
     * @version 2015-11-02
     * @return  [type]     [description]
     */
    protected function toSql()
    {
        $this->container->events->listen('illuminate.query', function ($query, $bindings, $time) {
            $query = str_replace(['%', '?'], ['%%', '%s'], $query);
            $query = vsprintf($query, $bindings);
            echo $query . PHP_EOL . $time;
            return;
        });
    }

    /**
     * 写充值的 日志 TODO 优化到充值服务中去
     *
     * @param string $word
     * @param string $recodeurl
     */
    protected function logResult($word = '', $recodeurl = '')
    {
        if ($recodeurl) {
            $recordLog = $recodeurl;
        } else {
            $recordLog = SiteSer::config('pay_log_file');
        }
        $fp = fopen($recordLog, "a");
        flock($fp, LOCK_EX);
        fwrite($fp, "执行日期：" . date("Ymd H:i:s", time()) . "\n" . $word . "\n");
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    /**
     * 生成用户对一个的token TODO 不该放这儿
     * @param $uid
     * @author Orino
     *
     * @return string
     */
    public function generateUidToken($uid)
    {
        $token = md5(uniqid(mt_rand(), true));
        Redis::set('user_token:' . $uid, $token);
        return $token;
    }

    /**
     * 对比用户的token TODO 真不该在这儿
     * @param $uid
     * @param $token
     * @return bool
     * @Author Orino
     */
    public function verifyUidToken($uid, $token)
    {
        if ($token == null)
            return false;
        $key = 'user_token:' . $uid;
        $search_token = Redis::get($key);
        if ($search_token != null) {
            Redis::del($key);//销毁token
        }
        if ($search_token == $token) {
            return true;
        }
        return false;
    }

    /**
     * @param     $uid
     * @param int $start
     * @param int $limit
     * @return mixed
     * @Author Orino
     */
    public function getUserAttensBycuruid($uid, $start = 0, $limit = 4)
    {
        return Redis::zrevrange('zuser_attens:' . $uid, $start, $limit);
    }

    /**
     * @param Request $req
     * @return mixed
     */
    public function getCurUid($req)
    {
        return intval($req->getSession()->get(self::SEVER_SESS_ID));
    }

    /**
     * 获取主播的房间类型
     * @param $uid
     * @Author Orino
     */
    public function getAnchorRoomType($rid, $timecheck = null)
    {
        $timecheck = $timecheck ? $timecheck : date('Y-m-d H:i:s', time());
        $type = 5;
        $curStatus = Redis::hgetall('hroom_status:' . $rid . ':' . $type);
        if (!isset($curStatus['status'])) return 0;
        if ($curStatus != null && $curStatus['status'] == 1) {
            $rooms = Redis::hGetAll('hroom_duration:' . $rid . ':' . $type);
            foreach ($rooms as $value) {
                $room = json_decode($value, true);
                $start = date('Y-m-d H:i:s', strtotime($room['starttime']));
                $end = date('Y-m-d H:i:s', strtotime($room['starttime']) + $room['duration']);
                if ($start <= $timecheck && $end >= $timecheck && $room['status'] == 0) {
                    return $type;
                }
            }
        }
        --$type;
        $curStatus = Redis::hgetall('hroom_status:' . $rid . ':' . $type);
        if (!isset($curStatus['status'])) return 0;
        if ($curStatus != null && $curStatus['status'] == 1) {
            $rooms = Redis::hGetAll('hroom_duration:' . $rid . ':' . $type);
            foreach ($rooms as $value) {
                $room = json_decode($value, true);
                $start = date('Y-m-d H:i:s', strtotime($room['starttime']));
                $end = date('Y-m-d H:i:s', strtotime($room['starttime']) + $room['duration']);
                if ($start <= $timecheck && $end >= $timecheck && $room['status'] == 0) {
                    return $type;
                }
            }
        }
        --$type;
        $curStatus = Redis::hgetall('hroom_status:' . $rid . ':' . $type);
        if (!isset($curStatus['status'])) return 0;
        if ($curStatus != null && $curStatus['status'] == 1) {
            $rooms = Redis::hGetAll('hroom_duration:' . $rid . ':' . $type);
            foreach ($rooms as $value) {
                $room = json_decode($value, true);
                $start = date('Y-m-d H:i:s', strtotime($room['starttime']));
                $end = date('Y-m-d H:i:s', strtotime($room['starttime']) + $room['duration']);
                if ($start <= $timecheck && $end >= $timecheck && $room['status'] == 0) {
                    return $type;
                }
            }
        }
        --$type;
        $curStatus = Redis::hgetall('hroom_status:' . $rid . ':' . $type);
        if (!isset($curStatus['status'])) return 0;
        if ($curStatus != null && $curStatus['status'] == 1 && $curStatus['pwd'] != null) { //特殊处理密码
            return $type;
        }
        return 1;//普通房间
    }

    /**
     * 用户中心 基本信息 修改用户信息
     *
     * @return JsonResponse
     */
    public function editUserInfo()
    {

        // 到提交的时候
        $postData = request()->only(['nickname', 'birthday', 'headimg', 'sex', 'province', 'city', 'county']);

        if (empty($postData)) {
            return new JsonResponse([
                'status' => 0,
                'msg' => '非法提交',
            ]);
        }

        // 初始化一个用户服务器 并初始化用户
        $userServer = resolve(UserService::class);
        $user = Auth::user();
        $msg = [
            'status' => 0,
            'msg' => '',
        ];
        //昵称重复
        $from_nickname = $user['nickname'];
        if (isset($postData['nickname']) && ($postData['nickname'] != $user['nickname'])) {

            // 判断长度 和 格式 是否正确
            $len = $this->count_chinese_utf8($postData['nickname']);
            //昵称不能使用/:;\空格,换行等符号。
            if ($len < 2 || $len > 11 || !preg_match("/^[^\s\/\:;]+$/", $postData['nickname'])) {
                $msg = [
                    'msg' => '注册昵称不能使用/:;\空格,换行等符号！(2-11位的昵称)',
                    'status' => 0,
                ];
                return new JsonResponse($msg);
            }

            /**
             * 关键字过滤
             *
             * @author dc
             * @var array
             */
            $query = Keywords::where('btype', 2)->where('status', 0)->get(['keyword'])->toArray();

            if (is_array($query)) {
                foreach ($query as $v) {
                    $v['keyword'] = addcslashes($v['keyword'], '.^$*+?()[]{}|\\');
                    if (preg_match("/{$v['keyword']}/i", $postData['nickname'])) {
                        return new JsonResponse(['msg' => '昵称中含有非法字符，请修改后再提交!', 'status' => 0]);
                    }
                }
            }

            // 判断昵称的唯一性
            if (!$userServer->checkNickNameUnique($postData['nickname'])) {
                $msg = [
                    'msg' => '昵称重复！',
                    'status' => 0,
                ];
                return new JsonResponse($msg);
            }

            //判断是否可以修改昵称 通过权限判断
            $status = $userServer->getModNickNameStatus();
            if ($status['num'] == 0) {
                $msg['msg'] = '你已经不能修改昵称了！';
                $msg['status'] = false;
                return new JsonResponse($msg);
            }

            /* 購買旗標 */
            $boughtModifyFlag = false;
            //查询购买记录
            $redis = resolve('redis');
            $boughtModifyNickname = (int) $redis->hget('modify.nickname', Auth::id());
            if ($boughtModifyNickname >= 1) {//重置
                $redis->del('modify.nickname', Auth::id());
                $boughtModifyFlag = true;
            }

            /* 守護修改旗標 */
            $guardianModFlag = false;

            /* 守護功能 - 加總修改次數 */
            $modCountRedisKey = 'smname_num:' . date('m') . ':' . auth()->id();
            if (!$boughtModifyFlag
                && (!empty($user->guard_id) && time() < strtotime($user->guard_end))
                && ($redis->get($modCountRedisKey) < $user->guardianInfo->rename_limit)
            ) {
                $modNum = $redis->incr($modCountRedisKey);
                $guardianModFlag = true;

                if (1 == $modNum) {
                    $redis->expire($modCountRedisKey, self::TTL_USER_NICKNAME);
                }
            }
        }

        //保证使用默认图片的headimg是空值
        if (isset($postData['headimg']) && strpos($postData['headimg'], 'head_') === 0) {
            unset($postData['headimg']);
        }

        // 修改用户表
        $user->update($postData);

        //维护redis中的hnickname_to_id 用于注册时验证是否重名
        if (isset($postData['nickname']) && ($postData['nickname'] != $from_nickname)) {
            Redis::hset('hnickname_to_id:' . SiteSer::siteId(), $postData['nickname'], $user['uid']);
            Redis::hdel('hnickname_to_id:' . SiteSer::siteId(), $from_nickname);//删除旧昵称登入权限

            // 修改昵称成功后 就记录日志
            /* 守護更名次數不記log */
            if (!$guardianModFlag) {
                $modNameLog = [
                    'uid' => $user['uid'],
                    'before' => $from_nickname,
                    'after' => $postData['nickname'],
                    'update_at' => time(),
                    'init_time' => date('Y-m-d H:i:s', time()),
                    'dml_time' => date('Y-m-d H:i:s', time()),
                    'dml_flag' => 1,
                ];
                UserModNickName::create($modNameLog);
            }

        }
        $userServer->getUserReset($user->uid);

        $msg = [
            'msg' => '更新成功！',
            'status' => 1,
        ];
        return new JsonResponse($msg);
    }

    /**
     * 删除关注信息
     * @param $fid
     * @param $tid
     * @return bool
     */
    public function attenionCancel($fid, $tid)
    {
        Attention::where('fid', $fid)->where('tid', $tid)->delete();
        return true;
    }

    /**
     * 取统计数据
     * @param $uid
     * @param $atten
     */
    public function getfidinfo($uid)
    {
        $userinfo = Users::find($uid);
        if (!$userinfo) {
            return null;
        }
        if (is_object($userinfo)) {
            $userinfo = $userinfo->toArray();

        }
        return $userinfo;
    }

    /**
     * 物理删除私信 TODO 尼玛
     * @param $id
     * @param $fid
     * @param $tid
     * @param $curUid
     * @return bool
     */
    public function delmsg($curUid)
    {
        $id = $this->request()->get('id');
        $tid = $this->request()->get('tid');
        $fid = $this->request()->get('fid');
        if ($curUid != $tid) {
            //用户存在问题
            return false;
        }
        $this->delBylogicflag(['id' => $id, 'send_uid' => $fid, 'rec_uid' => $tid]);
        //$this->delByEntity('Video\ProjectBundle\Entity\VideoMail',array('id'=>$id,'sendUid'=>$fid,'recUid'=>$tid));
    }

    /**
     * 逻辑删除
     * @param $conditions
     * @Author Orino
     */
    public function delBylogicflag($conditions)
    {
//        $this->updateByEntity('Video\ProjectBundle\Entity\VideoMail',$conditions,array('logicflag'=>0));
        Messages::where($conditions)->update(['logicflag' => 0]);
    }

    //通过uid,true检查通过，false不通过
    public function checkNameUnique($criteria)
    {
//        $sql = '';
        $uid = $criteria['uid'];
//        unset($criteria['uid']);
//        foreach( $criteria as $key=>$item){
//            $sql .= $sql==''? $key.'= "'.$item.'"' : ' AND '.$key.'= "'.$item.'"';
//        }
//        $stmt = $this->_doctrine_em->getConnection()->prepare('SELECT * from `video_user` WHERE '.$sql);
//        $stmt->execute();//    $stmt->execute($parmas);
//        $data =  $stmt->fetchAll();
        $data = Users::where($criteria)->get();
        $data = count($data) > 0 ? $data[0] : [];
        return (empty($data) || $data['uid'] == $uid) ? true : false;
    }

    public function buyGoods($type, $gid, $nums)
    {
        if ($nums < 1) {
            return false;
        }
        //查询商品的价格和用户的金钱
//        $goodsObj = $this->_doctrine_em->getRepository('Video\ProjectBundle\Entity\VideoGoods')
//            ->findOneBy(array('gid'=>$gid,'isShow'=>1,'unitType'=>2));

        $goodsObj = Goods::where(['gid' => $gid, 'is_show' => 1, 'unit_type' => 2])->first();//强制为商城出售的商品

        if (!$goodsObj) {
            return false;
        }

        $userinfo = Auth::user();
        $months = 0;
        if ($type == 0) {
            $total_price = $goodsObj->price * $nums;
            $total_num = $nums * 30;
            $months = $nums;
        } else {
            $total_price = $goodsObj->price * 12 * 0.9 * $nums;
            $total_num = $nums * 365;
            $months = $nums * 12;
        }
        if ($userinfo['points'] < $total_price) {
            return false;
        }

        //  $expireTime = strtotime('+'.$total_num.' days');
        //24*60*60=86400
        $expireTime = $total_num * 86400;

        try {
            $criteria = ['uid' => Auth::id(), 'gid' => $gid];
            $gidExpireTime = 0;
            $packEntity = Pack::where($criteria)->first();

            //开启事务
            DB::begintransaction();
            //处理用户与道具的关联信息
            if (!$packEntity) {
                $criteria1 = $criteria;
                $criteria['expires'] = $_SERVER['REQUEST_TIME'] + $expireTime;
                $criteria['num'] = 1;
                Pack::create($criteria);
                Pack::where($criteria1)->update(['site_id' => SiteSer::siteId()]);
                $gidExpireTime = $criteria['expires'];
            } else {
                $gidExpireTime = $packEntity->expires;
                if ($gidExpireTime < time()) {
                    $gidExpireTime = time() + $expireTime;
                } else {
                    $gidExpireTime += $expireTime;
                }
                Pack::where($criteria)->update(['expires' => $gidExpireTime,'site_id' => SiteSer::siteId()]);
            }

            $upUser = ['points' => $userinfo['points'] - $total_price, 'rich' => $userinfo['rich'] + $total_price];
            $flag = Users::where('uid', Auth::id())->update($upUser);

            if (!$flag) {
                DB::rollback();
                resolve(UserService::class)->cacheUserInfo(Auth::id(), null);
                return false;
            }
            MallList::create([
                'send_uid' => Auth::id(),
                'rec_uid' => 0,
                'gid' => $gid,
                'gnum' => $months,
                'created' => date('Y-m-d H:i:s'),
                'rid' => 0,
                'points' => $total_price,
            ]);
            DB::commit();
            $gidinfo = Redis::hgetall('user_car:' . Auth::id());

            if (!empty($gidinfo) && isset($gidinfo[$gid])) {
                Redis::hset('user_car:' . Auth::id(), $gid, $gidExpireTime);
            }
            // 更新redis
            resolve(UserService::class)->cacheUserInfo(Auth::id(), null);
            return true;
        } catch (\Exception $e) {
            DB::rollback();
            resolve(UserService::class)->cacheUserInfo(Auth::id(), null);
            return false;
        }
    }

    /**
     * true表示没达到今天的上限，反之则超过
     * @param $uid
     * @param $astrictNum
     * @param $redisKey
     * @return bool
     * @Author Orino
     */
    public function checkAstrictUidDay($uid, $astrictNum, $table)
    {
        $redisKey = 'h' . $table . date('Ymd');
        if (Redis::exists($redisKey)) {
            $num = intval(Redis::hget($redisKey, $uid));//有可能取出为空
            if ($num >= $astrictNum) {
                $flag = 0;
            } else {
                $flag = ++$num;
            }
        } else {
            //不存在就删除前1天的，维护好数据
            Redis::del('h' . $table . date('Ymd', strtotime('-1 day')));
            $flag = 1;
        }
        return $flag;
    }

    /**
     * @param           $uid
     * @param           $num
     * @param           $table
     * @param DataModel $dataModel
     * @param           $num
     * @Author Orino
     */
    public function setAstrictUidDay($uid, $num, $table)
    {
        if ($num == 0) return;
        $redisInst = $this->make('redis');
        $redisKey = 'h' . $table . date('Ymd');
        $redisInst->hset($redisKey, $uid, $num);
    }

    /**
     * 获取礼物道具相关信息
     * @param int $gid |默认返回所有记录
     * @return bool|string
     * @author  D.C
     * @update  2015-02-09
     * @version 1.0
     */
    public function getGoods($gid = 0)
    {

        if (Redis::hexists('goods', $gid)) {
            return Redis::hget('goods', $gid);
        }
//        $query = $this->getDoctrine()->getManager()->getRepository('Video\ProjectBundle\Entity\VideoGoods')
//            ->createQueryBuilder('e')->getQuery();
//        $result = $query->getArrayResult();
        $result = Goods::all()->toArray();
        foreach ($result as $v) {
            $goods[$v['gid']] = $v;
            Redis::hset('goods', $v['gid'], $v['name']); //目前只缓存name项
        }
        Redis::EXPIRE('goods', 86400);
        if (!$gid) {
            return $goods;
        } else {
            return isset($goods[$gid]) ? $goods[$gid]['name'] : false;
        }

    }

    /**
     *  检测用户的贵族保级状态 TODO 以后移到新框架中去
     *
     *  如果不是贵族 直接返回
     *  如果是贵族，检测状态：
     *      达到状态更新贵族的有效期
     *      未达到直接返回
     *
     * @param $user
     * @return boolean
     */
    public function checkUserVipStatus($user)
    {
        // 当不是贵族时
        if (!$user['vip']) {
            return true;
        }

        $group = LevelRich::where('level_id', $user['vip'])->first();
        if (!$group) {
            return true;// 用户组都不在了没保级了
        }

        $userGid = $group->gid;

        // 获取购买记录
        $log = UserBuyGroup::withoutGlobalScopes()->where('uid', $user['uid'])->where('gid', $userGid)->orderBy('end_time', 'desc')->first();
        // 获取充值详细 时间为有效期往前推一个月
        //$startTime = strtotime($log->end_time) - 30 * 24 * 60 * 60;

        //dc修改空数据下 判断
        if ($log) {
            $startTime = strtotime($log->end_time) - 30 * 24 * 60 * 60;
        } else {
            $startTime = time();
        }


        // 兼容后台充值的策略的
        $pays = Recharge::withoutGlobalScopes()->where('uid', $user['uid'])->where('pay_status', 2)->where(function ($query) {
            $query->orWhere('pay_type', 1)->orWhere('pay_type', 4)->orWhere('pay_type', 7);
        })->where('created', '>=', date('Y-m-d H:i:s', $startTime))
            ->sum('points');
        if (!$pays) {
            return true; // 未充值直接返回
        }

        $system = unserialize($group->system);
        if ($pays >= $system['keep_level']) {
            // 更改有效期
            //开启事务
            DB::begintransaction();
            try {
                $newTime = strtotime($log->end_time) + 30 * 24 * 60 * 60;
                $log->end_time = date('Y-m-d H:i:s', $newTime);
                $log->save();

                $userObj = Usersall::find($user['uid']);
                $userObj->vip_end = date('Y-m-d H:i:s', $newTime);
                $userObj->save();

                DB::commit();
                // 更新完刷新redis
                resolve(UserService::class)->cacheUserInfo($user['uid'], ['vip_end' => date('Y-m-d H:i:s', $newTime)]);
/*
                //发送私信给用户
                VideoMail::create([
                    'send_uid' => 0,
                    'rec_uid' => $user['uid'],
                    'content' => '贵族保级成功提醒：您的' . $group->level_name . '贵族保级成功啦！到期日：' . date('Y-m-d H:i:s', $newTime),
                    'category' => 1,
                    'status' => 0,
                    'created' => date('Y-m-d H:i:s'),
                ]);
*/
            } catch (\Exception $e) {
//                $testPath = BASEDIR . '/app/logs/test_' . date('Y-m-d') . '.log';
                $testInfo = "保级异常：getmypid " . getmypid() . "checkUserVipStatus 更新数据成功  \n";
                Log::error($testInfo);
//                $this->logResult($testInfo, $testPath);
                DB::rollBack();//事务回滚
            }

        }
        return true;
    }

    function array_column_multi(array $input, array $column_keys)
    {
        $result = [];
        $column_keys = array_flip($column_keys);
        foreach ($input as $key => $el) {
            $result[$key] = array_intersect_key($el, $column_keys);
        }
        return $result;
    }

    public function format_jsoncode($arr)
    {
        $res = array(
            'status' => 1,
            'data' => $arr ?: [],
            'msg' => '获取成功'
        );
        return $res;

    }

    protected function msg($msg, $status = 0)
    {
        return JsonResponse::create(
            [
                "status" => $status,
                "msg" => $msg,
            ]
        );
    }
}
