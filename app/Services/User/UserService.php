<?php

namespace App\Services\User;

use App\Facades\SiteSer;
use App\Facades\UserSer;
use App\Models\Agents;
use App\Models\AgentsRelationship;
use App\Models\InviteCodes;
use App\Models\UserBuyGroup;
use App\Models\UserDomain;
use App\Models\UserGroup;
use App\Models\UserGroupPermission;
use App\Models\UserModNickName;
use App\Models\Users;
use App\Services\Service;
use App\Services\UserGroup\UserGroupService;
use DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;
use Mockery\Exception;

class UserService extends Service
{

    const KEY_USERNAME_TO_ID = 'husername_to_id';
    const KEY_USER_INFO = 'huser_info:';
    const KEY_USER_SID = 'huser_sid';
    public $user;
    protected $redis;

    public function __construct(RedisManager $redis)
    {
        $this->redis = $redis;
    }

    public function setUser($user)
    {
        if (!$user instanceof Users) {
            abort(404, 'Please make sure $user is a App\Models\Users object');
        }
        $this->user = $user;
        return $this;
    }

    /**
     * @param array $user
     * @param array $gift
     * @param int   $agent
     * @param int   $invite_code
     * @return bool|int
     */
    public function register(array $user, $gift = [], $agent = 0, $invite_code = 0)
    {
        $newUser = Arr::only($user, ['did', 'username', 'password', 'nickname', 'roled', 'exp', 'pop', 'created', 'status', 'province', 'city', 'county', 'video_status', 'rich', 'lv_exp', 'lv_rich', 'pic_total_size', 'pic_used_size', 'lv_type', 'icon_id', 'uuid', 'xtoken', 'origin', 'sex']);
        $newUser['created'] = date('Y-m-d H:i:s');
        if (strlen($newUser['password']) != 32) {
            $newUser['password'] = md5($newUser['password']);
        }
        try {
            /**
             * 开启事务
             */
            DB::beginTransaction();
            //开始注册===================================

            $uid = DB::table($userTable = (new Users)->getTable())->insertGetId($newUser);
            if (!$uid) throw new \ErrorException('insert to user table error.');

            //注册主播房间
            if (Arr::get($user, 'roled') == 3) {
                DB::table($userTable)->where('uid', $uid)->update(['rid' => $uid]);
            }

            //更新域名用户统计
            if ($did = Arr::get($newUser, 'did')) {
                DB::table((new UserDomain)->getTable())->where('id', $did)->increment('users');
            }

            //邀请码处理
            if ($invite_code) {
                $code = substr($invite_code, -8);
                $gid = substr($invite_code, 0, -8);
                $invite = InviteCodes::where('gid', $gid)->where('status', 0)->where('code', $code)->with('group')->first();

                /**
                 * 邀请码赠送处理
                 */
                if ($invite) {
                    //贵族礼包
                    $gift['vip'] = $invite->group->vip ?: 0;
                    $gift['vip_end'] = $invite->group->vip_days ?: 0;

                    //钻石礼包
                    $gift['points'] = $invite->group->points ?: 0;

                    //所属代理
                    $agent = $invite->group->agents ?: 0;

                    //注消邀请码
                    DB::table((new InviteCodes)->getTable())->where('gid', $gid)->where('status', 0)->where('code', $code)->update(['uid' => $uid, 'used_at' => date('Y-m-d H:i:s'), 'status' => 1]);

                }
            }

            DB::commit();
            //更新redis关联
            $redis = $this->make('redis');
            $redis->hset('husername_to_id', $user['username'], $uid);
            $redis->hset('hnickname_to_id', $user['nickname'], $uid);

            //赠送钻石
            if ($points = Arr::get($gift, 'points')) {
                if (!$this->updateUserOfPoints($uid, '+', $points, 5)) {
                    throw new \ErrorException('update user of points exception at register');
                }
            }

            //赠送贵族
            if (Arr::get($gift, 'vip') && Arr::get($gift, 'vip_end')) {
                if (!$this->updateUserOfVip($uid, Arr::get($gift, 'vip'), 3, Arr::get($gift, 'vip_end'))) {
                    throw new \ErrorException('update user of vip exception at register');
                }
            }

            //更新所属代理
            if ($agent) {
                if (DB::table((new Agents)->getTable())->where('id', $agent)) {
                    if (!$this->setUserAgents($uid, $agent)) {
                        throw new \ErrorException('update user of agents exception  at register');
                    }
                }
            }

            //注册完成===================================


        } catch (\Exception $e) {
            Log::info('注册 事务结果:'.$e->getMessage());
            DB::rollback();
            return false;
        }

        return $uid;
    }

    /**
     * [updateUserOfPoints 更新用户钻石]
     *
     * @see     src\Video\ProjectBundle\Controller\VideoBaseController.php addUserPoints function
     * @author  dc <dc@wisdominfo.my>
     * @version 2015-11-05
     * @param   integer $uid       [用户id]
     * @param   string  $operation [操作符，只能是+ -符号]
     * @param   integer $points    [更新钻石数]
     * @param   integer $pay_type  [充值方式：1 银行转账、2 抽奖  3 （未使用）   4后台充值 5充值赠送 6任务和签到奖励 7转帐记录]
     * @return  bool                  [成功true 失败false]
     * @throws \ErrorException
     */
    public function updateUserOfPoints($uid, $operation, $points, $pay_type)
    {
        //if(!is_int(uid) || !$points || !in_array($operation, array('+','-'))) return false;
        if (!$uid || !is_numeric($uid)) {
            throw new \Exception('Please make sure $uid is a numeric');
        }
        if (intval($points) < 1) return true;

        if (!$this->getUserByUid($uid)) throw new \ErrorException('Unknown the user ' . $uid);

        //更新钻石
        switch ($operation) {
            case '+':
                if (Users::where('uid', $uid)->increment('points', $points)) {
                } else {
                    throw new \ErrorException('increment user points error');
                }
                break;

            case '-':
                if (!Users::where('uid', $uid)->decrement('points', $points)) {
                    throw new \ErrorException('decrement user points error');
                }
                break;

            default:
                return false;
        }
        $this->getUserReset($uid);
        return true;
    }

    /**
     * @param int $uid
     * @param int $points
     * @return bool
     */
    public function addPoint($uid = 0, $points = 0): bool
    {
        if (!$this->getUserByUid($uid)) return false;

        if (!Users::where('uid', $uid)->increment('points', $points)) return false;

        $this->getUserReset($uid);
        return true;
    }


    /**
     * 从redis中读取，读取不到就读db,再写redis
     * @param $uid
     * @return Users|null
     */
    public function getUserByUid($uid)
    {
        if (!$uid || !is_numeric($uid)) {
            return null;
        }
        $hashtable = static::KEY_USER_INFO . $uid;

        if ($this->redis->Hexists($hashtable, 'uid')) {
            $user = (new Users())->setRawAttributes($this->redis->hgetall($hashtable),true);
            $user->exists=true;
        } else {
            $user = Users::find($uid);
            if ($user&&$user->exists) {
                $this->redis->hmset($hashtable, $user->toArray());
            }
        }
        return $this->user = $user;
    }

    public function userExists($uid)
    {
        return $this->redis->exists(static::KEY_USER_INFO . $uid);
    }

    /**
     * [userReset 重置用户redis并获取数据]
     *
     * @author  dc <dc@wisdominfo.my>
     * @version 2015-11-10
     * @param   int $uid 用户id
     * @return  array      用户数据
     */
    public function getUserReset($uid)
    {
        if (!$uid || !is_numeric($uid)) {
            throw new Exception('Please make sure $uid is a numeric');
        }
        $user = Users::find($uid);
        if (!$user) {
            return false;
        }
        $user = $user->toArray();

        //更新redis;
        $hashtable = 'huser_info:' . $uid;
        $this->redis->hmset($hashtable, $user);
        return $user;
    }

    /**
     * [updateUserOfVip 开通贵族及延长贵族时间]
     * @param      $uid          [用户ID]
     * @param      $level_id     [贵族id]
     * @param      $type         [开通类型]
     * @param      $days         [天数]
     * @param bool $fill_expires [已经是贵族,是否延长贵族时间]
     * @return bool[成功true 失败false]
     */
    public function updateUserOfVip($uid, $level_id, $type, $days = 30, $fill_expires = false)
    {
        if (!$uid || !is_numeric($uid)) {
            throw new Exception('Please make sure $uid is a numeric');
        }
        $user = $this->getUserByUid($uid);
        if (!$user) return false;

        $vip = 0;
        $userVip = $this->checkUserVipStatus($uid);
        $expires = date('Y-m-d H:i:s', strtotime('+' . $days . ' days'));
        if ($userVip) {
            $vip = $userVip->vip;
        }

        if ($vip == $level_id) {
            if (!$fill_expires) return true;
            //延长贵族时间

            //如果新增的贵族等级小于原有的贵族等级则不进行操作
            if ($vip > $level_id) return true;
            //追加过期时间
            $expires = date('Y-m-d H:i:s', strtotime($userVip->vip_end) + ($days * 86400));

            Users::where('uid', $uid)->update(['vip' => $level_id, 'vip_end' => $expires]);
            UserBuyGroup::where('uid', $uid)->where('level_id', $level_id)->orderBy('id', 'desc')->limit(1);
        } elseif ($vip < $level_id) {
            $group = UserGroup::where('level_id', $level_id)->first();
            $group['system'] = unserialize($group['system']);
            if (!is_array($group['system'])) return false;

            //开通贵族
            UserBuyGroup::create([
                'uid' => $uid,
                'gid' => $group['gid'],
                'level_id' => $level_id,
                'type' => $type,
                'end_time' => $expires,
                'create_at' => date('Y-m-d H:i:s'),
                'open_money' => $group['system']['open_money'],
                'keep_level' => $group['system']['keep_level'],
                'status' => 1,

            ]);
            $vip_data = ['vip' => $level_id, 'vip_end' => $expires];

            //赠送等级
            if ($gift_level = $group['system']['gift_level']) {
                $level = UserGroup::where('level_id', $gift_level)->first(['level_value']);
                $level_value = $user['rich'] + $level->level_value;
                $vip_data = array_merge($vip_data, ['lv_rich' => $gift_level, 'rich' => $level_value]);
            }
            Users::where('uid', $uid)->update($vip_data);

        }
        //$this->make('redis')->del('huser_info:'.$uid);
        if (!$this->getUserReset($uid)) {
            throw new Exception('updateUserOfVip getUserReset function for update user cache to redis was error');
        }
        return true;
    }


    /**
     * [checkVipStatus 检查vip状态]
     *
     * @author  dc <dc#wisdominfo.my>
     * @version 2015-11-11
     * @param   int $uid 用户id
     * @return object|false
     */
    public function checkUserVipStatus($uid)
    {
        if (!$uid || !is_numeric($uid)) {
            throw new Exception('Please make sure $uid is a numeric');
        }
        $vip = Users::where('uid', $uid)->where('vip', '>', 0)->where('vip_end', '>', date('Y-m-d H:i:s'))->first();

        return $vip;
    }

    /**
     * [checkVipStatus 检查vip状态]
     *
     * @author  dc <dc#wisdominfo.my>
     * @version 2015-11-11
     * @param   int $uid 用户id
     * @return object|false
     */
    public function checkVip($uid)
    {
        if (!$uid || !is_numeric($uid)) {
            throw new Exception('Please make sure $uid is a numeric');
        }
        $vip = Users::where('uid', $uid)->where('vip', '>', 0)->where('vip_end', '>', date('Y-m-d H:i:s'))->first();

        return $vip;
    }

    public function cancelVip($uid=0){
        $user['uid'] = $uid;
        Users::query()->where('uid',$user['uid'])->update(array('vip'=>0,'vip_end'=>'','hidden'=>0));
        Redis::hmset('huser_info:'.$user['uid'],[
            'vip'=>'0',
            'hidden'=>'0',
            'vip_end'=>'',
        ]);
        $pack = VideoPack::query()->where('uid',$user['uid'])->whereBetween('gid',[120101,120107])->delete();
        Redis::del('user_car:'.$user['uid']);
        return true;
    }

    /**
     * 添加用户代理方法
     * @param $uid
     * @param $aid
     * @return mixed
     */
    public function setUserAgents($uid, $aid)
    {
        $agent = AgentsRelationship::where('uid', $uid)->exists();
        if ($agent) {
            return AgentsRelationship::where('uid', $uid)->update(['aid' => $aid]);
        } else {
            return AgentsRelationship::create(['uid' => $uid, 'aid' => $aid])->id;
        }
    }

    /**
     * [getUserByUsername 通过帐号获取用户]
     *
     * @author  dc <dc#wisdominfo.my>
     * @version 2015-11-13
     * @param   string $username [用户帐号]
     * @return  array|bool               返回用户数据
     */
    public function getUserByUsername($username)
    {
        $uid = $this->getUidByUsername($username);
        return $this->getUserByUid($uid);
    }

    public function getUidByUsername($username)
    {
        $uid = $this->redis->hget(static::KEY_USERNAME_TO_ID, $username);
        if (!$uid) {
            $uid = Users::query()->where('username', $username)->get(['uid'])->get('uid');
            if (!$uid) return null;
            $uid = $this->redis->hset(static::KEY_USERNAME_TO_ID, $username, $uid);
        }
        return $uid;
    }

    /**
     *
     */
    public function addPoints($uid = 0, $points = 0)
    {

    }

    /**
     * 获取当前用户被别人关注的用户信息/当前用户关注别人的用户信息
     * @Author  Nicholas 优化
     * @param   int  $uid
     * @param   int  $currentPage
     * @param   bool $fid
     * @param   int  $perPage
     * @Author  nicholas
     * @return LengthAwarePaginator
     */
    public function getUserAttens($uid, $currentPage = 1, $fid = true, $perPage = 12)
    {
        $items = collect();

        $total = $this->getUserAttensCount($uid);

        if ($fid) {
            //ZREVRANGE
            $uids = $this->redis->zrevrange('zuser_attens:' . $uid, ($currentPage - 1) * $perPage, $currentPage * $perPage - 1);
        } else {
            $uids = $this->redis->zrevrange('zuser_byattens:' . $uid, ($currentPage - 1) * $perPage, $currentPage * $perPage - 1);
        }
        foreach ($uids as $uid) {
            $user = UserSer::getUserByUid($uid);
            $items->push([
                'headimg' => $this->getHeadimg($user['headimg'], 80),
                'uid' => $user->uid,
                'nickname' => $user->nickname,
                'roled' => $user->roled,
                'lv_exp' => $user->lv_exp,
                'lv_rich' => $user->lv_rich,
                'fid' => $uid,
            ]);
        }

        $paginator = new LengthAwarePaginator($items, $total, $perPage, $currentPage);
        return $paginator;
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
            return $this->redis->zcard('zuser_attens:' . $uid);//获取自己关注别人的数量
        } else {
            return $this->redis->zcard('zuser_byattens:' . $uid);//获取自己被别人关注的数量
        }
    }

    /**
     * 获取头像地址 默认头像 TODO 优化
     *
     * @param        $headimg
     * @param int    $size
     * @return string
     */
    public function getHeadimg($headimg, $size = 180)
    {
        return $headimg ? SiteSer::config('img_host') . '/' . $headimg . ($size == 150 ? '' : '?w=' . $size . '&h=' . $size)
            : SiteSer::config('cdn_host') . '/src/img/head_' . $size . '.png';
    }

    /**
     * 检查用户登录权限
     * @param $uid
     * @return bool
     */
    public function checkUserLoginAsset($uid)
    {
        $user = $this->getUserByUid($uid);
        if ($user['points'] >= $this->container->config['config.user_points_min']) {
            return true;
        }
        if ($user['roled'] == 3 || $this->checkUserVipStatus($uid)) {
            return true;
        }
        return false;
    }

    /**
     * 修改用户的信息
     * <p>
     *  修改用户表的信息 牵涉到redis的更新 都集成到这里
     * </p>
     * @param $data array
     * @return object
     */
    public function updateUser($data = [])
    {
        if (!is_array($data)) {
            throw new Exception('Please make sure $data is a array');
        }
        $res = $this->user->update($data);
        if ($res === false) {
            throw new Exception('Mod $user is error');
        }
        return $res;
    }

    /**
     * 检查nickname是否唯一
     *
     * @param $nickname
     * @return bool
     */
    public function checkNickNameUnique($nickname)
    {
        $user = Users::where('nickname', $nickname)
            ->where('uid', '!=', $this->user['uid'])
            ->first();
        if ($user) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 赠送给用户一个等级
     * <p>
     *  会判断等级是否比用户现有等级小，如果小就保留不变
     * </p>
     * @param int $lv_rich
     * @return bool
     */
    public function modLvRich($lv_rich)
    {
        if (!$lv_rich) {
            throw new Exception('Mod Lv_rich should input a int type');
        }

        // 判断是否要提升到的等级是否小于本身用户的等级了，如果小于就不提升了
        if ($lv_rich <= $this->user['lv_rich']) {
            return true;
        } else {
            // 根据等级计算 对应的值
            $userGroup = resolve(UserGroupService::class);
            $lvs = $userGroup->getLevelGroup();
            $rich = $lvs[$lv_rich]['level_value'];
        }
        $res = DB::table('video_user')->where('uid', $this->user['uid'])->update(['lv_rich' => $lv_rich, 'rich' => $rich]);
        if ($res == false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 检查用户是否有修改昵称的权限 和 状态
     */
    public function getModNickNameStatus()
    {
        /** 先查询购买的权限 */
        $flag = intval(Redis::hget('modify.nickname', $this->user['uid']));
        if ($flag >= 1) {
            return ['num' => $flag];
        }

        // TODO 贵族的权限
        if ($this->user['vip']) {
            $vip = $this->user['vipGroup']['permission'];
            return $this->checkModNickNameStatus($vip);
        }

        // 普通等级的权限
//        if ($this->user['lv_rich']) {
//            $lv_id = $this->user['lvGroup']['permission'];
//            return $this->checkModNickNameStatus($lv_id);
//        }

        // 普通用户修改的权限 只允许一次
        $uMod = UserModNickName::where('uid', $this->user['uid'])->first();
        if ($uMod && $uMod->exists) {
            return ['num' => 0];
        } else {
            return ['num' => 1];
        }
    }

    /**
     * 根据昵称修改的权限设置来判断是否可以修改
     *
     * @param $permission
     * @return array
     */
    protected function checkModNickNameStatus($permission)
    {
        // 不可修改
        if ($permission['modnickname'] == 0) {
            return ['num' => 0];
        }
        // 无限制的
        if ($permission['modnickname'] == -1) {
            return ['num' => -1];
        }
        // 其他的按照 周 月 年 判断修改的次数
        list($num, $day) = explode('|', $permission['modnickname']);
        if ($day == 'week') {
            $uMod = UserModNickName::where('uid', $this->user['uid'])
                ->where('update_at', '>', strtotime('-1 week'))->count();
            return ['num' => $num - $uMod, 'mod' => $uMod, 'type' => $day];
        }
        if ($day == 'month') {
            $uMod = UserModNickName::where('uid', $this->user['uid'])
                ->where('update_at', '>', strtotime('-1 month'))->count();
            return ['num' => $num - $uMod, 'mod' => $uMod, 'type' => $day];
        }
        if ($day == 'year') {
            $uMod = UserModNickName::where('uid', $this->user['uid'])
                ->where('update_at', '>', strtotime('-1 year'))->count();
            return ['num' => $num - $uMod, 'mod' => $uMod, 'type' => $day];
        }
    }

    /**
     * @todo        原方法未写数据库，侍确认。
     * @param $uid [用户id]
     * @param $pid [被关注用户id]
     * @return bool
     * @author      dc
     * @description 迁移原方法setUserAttens
     */
    public function setFollow($uid, $pid)
    {
        if (!$uid || !is_numeric($uid) || !$pid || !is_numeric($pid)) {
            throw new Exception('Please make sure $uid and  $pid is a numeric');
        }
        //检查是否已被关注
        if ($this->checkFollow($uid, $pid)) return false;
        $timestamp = time();
        //设置关注发起者=>关注接受者
        $this->make('redis')->zadd('zuser_attens:' . $uid, $timestamp, $pid);
        //设置关注接受者=>关注发起者
        $this->make('redis')->zadd('zuser_byattens:' . $pid, $timestamp, $uid);
        return true;
    }

    /**
     * @param null       $uid 本身用户id
     * @param null       $pid 被关注的用户id
     * @param bool|true  $flag
     * @param bool|false $reservation
     * @return bool
     * @author      dc
     * @version     20151022
     * @description 因迁移过来，目前所涉及的参数解释 暂时未知。 原方法名 @checkUserAttensExists
     */
    public function checkFollow($uid = null, $pid = null, $flag = true, $reservation = false)
    {
        if ($reservation) {
            $zkey = 'zuser_reservation:';
        } else {
            $zkey = $flag ? 'zuser_attens:' : 'zuser_byattens:';
        }
        return $this->make('redis')->zscore($zkey . $uid, $pid) ? true : false;
    }

    /**
     * @todo        原方法未写数据库，侍确认。
     * @param $uid [用户id]
     * @param $pid [被关注用户id]
     * @return bool
     * @author      dc
     * @version     20151022
     * @description 迁移原方法delUserAttens
     */
    public function delFollow($uid, $pid)
    {

        if (!$uid || !is_numeric($uid) || !$pid || !is_numeric($pid)) {
            throw new Exception('Please make sure $uid and  $pid is a numeric');
        }
        //检查是否已被关注
        //if(!$this->checkFollow($uid, $pid)) return false;
        //删除互相关注的id
        $this->make('redis')->zrem('zuser_attens:' . $uid, $pid);
        $this->make('redis')->zrem('zuser_byattens:' . $pid, $uid);
        return true;
    }


    /**
     * @param     $uid   [发信用户id]
     * @param int $limit [发信数量限制 由调用方定]
     * @param     $table [信息类型]
     * @return bool [返回true表示未达到上限]
     * @author      dc
     * @version     20151023
     * @description 该含数迁移自原来 VideoBaseController中的 checkAstrictUidDay方法
     */
    public function checkUserSmsLimit($uid, $limit, $table)
    {
        if (!$uid || !$table) {
            throw new Exception('the [checkUserSmsLimit] function params was failure ');
        }
        $redis = $this->make('redis');
        $redisKey = 'h' . $table . date('Ymd');

        //先判断如果不存在就删除前1天数据
        if (!$redis->exists($redisKey)) {
            $redis->del('h' . $table . date('Ymd', strtotime('-1 day')));
            return 1;
        }
        $total = intval($redis->hget($redisKey, $uid));
        if ($total < $limit) return $total;
        return 0;
    }


    /**
     * @param $uid   [用户id]
     * @param $num   [数量]
     * @param $table [私信类型]
     * @return bool [更新结果]
     * @author      dc
     * @version     20151023
     * @description 迁移自原 VideoBaseController中的 setAstrictUidDay方法。主要功能更新用户发送私信数量
     */
    public function updateUserSmsTotal($uid, $num, $table)
    {
        if (!$uid || !$num) return false;
        $redis = $this->make('redis');
        $redisKey = 'h' . $table . date('Ymd');
        $num = intval($redis->hget($redisKey, $uid)) + $num;
        /**
         * @var $redis \Redis
         * */
        if ($redis->hset($redisKey, $uid, $num) === FALSE) return false;
        return true;
    }


    /**
     * [getUserHiddenPermission 根据id获取该用户是否有隐身权限]
     *
     * @todo    优化查询，迁移到redis
     * @param array $user [用户信息,要包含uid,lv_rich, vip, vip_end等字段]
     *
     * @return mixed
     * @author  dc@wisdominfo.my
     * @version 20151127
     *
     */
    public function getUserHiddenPermission($user)
    {
        //vip状态判断
        if (!($user['vip'] > 0 && $user['vip_end'] > date('Y-m-d H:i:s'))) return false;

        //用户组权限判断
        $userBuyGroup = $userBuyGroup = UserBuyGroup::where('uid', $user['uid'])->orderby('auto_id', 'desc')->first(['gid']);
        $buyGid = $userBuyGroup ? $userBuyGroup->gid : 0; //购买贵宾组条件
        $lv_rich = $user['lv_rich'];    //普通组条件
        $UserGroupPermission = UserGroupPermission::where('allowstealth', 1)->where(function ($query) use ($lv_rich, $buyGid) {
            $query->where('gid', $buyGid)->Orwhere('gid', $lv_rich);
        })->first();
        if (!$UserGroupPermission) return false;
        return true;
    }

    /**
     * todo
     * @param $userinfo [用户信息,常为获取到的信息数组]
     * @param $des_key  [加密密钥]
     * @return string [返回加密代码]
     * @author       dc
     * @description  加密用户信息
     */
    public function get3Des($userinfo, $des_key)
    {
        $iv = 'onevideo';
        $encrypt = json_encode($userinfo, JSON_UNESCAPED_SLASHES); //明文
        $tb = mcrypt_module_open(MCRYPT_3DES, '', 'cbc', ''); //创建加密环境 256位 128/8 = 16 字节 表示IV的长度
        mcrypt_generic_init($tb, $des_key, $iv); //初始化加密算法
        $encrypt = $this->_PaddingPKCS7($encrypt);//这个函数非常关键,其作用是对明文进行补位填充
        $cipher = mcrypt_generic($tb, $encrypt); //对数据执行加密
        $cipher = base64_encode($cipher);//同意进行base64编码
        mcrypt_generic_deinit($tb); //释放加密算法资源
        mcrypt_module_close($tb); //关闭加密环境
        return $cipher;
    }

    /**
     * todo
     * @param $data [经3des加密的密文]
     * @return string
     * @author      dc
     * @description 加密//补位填充函数 迁移自原原PaddingPK27函数
     */
    private function _PaddingPKCS7($data)
    {
        /* 获取加密算法的区块所需空间,MCRYPT_3DES表示加密算法,cbc表示加密模式,要和mcrypt_module_open(MCRYPT_3DES,'','cbc','')的一致*/
        $block_size = mcrypt_get_block_size(MCRYPT_3DES, 'cbc');
        $padding_char = $block_size - (strlen($data) % $block_size); // 计算需要补位的空间
        $data .= str_repeat(chr($padding_char), $padding_char);        // 补位操作
        return $data;
    }

    public function getRank($key, $offset, $limit)
    {
        /** @var \Redis $redis */
        $redis = $this->make('redis');

        $rank = $redis->zRevRange($key, $offset, $limit, true);
        $ret = [];
        static $userCache = [];//缓存用户信息
        foreach ($rank as $uid => $score) {
            $ret[] = array_merge([
                'uid' => $uid,
                'score' => $score,
            ],
                isset($userCache[$uid]) ? $userCache[$uid]
                    : ($userCache[$uid] = array_only($this->getUserByUid($uid)->toArray(), ['lv_rich', 'lv_exp', 'username', 'nickname', 'headimg', 'icon_id', 'description', 'vip']))
            );
        }
        return $ret;
    }

    /**
     * 检测用户是否是一个对象
     */
    protected function checkUser()
    {
        if (!$this->user instanceof Users) {
            throw new Exception('Please make sure $user is a App\Models\Users object');
        }
    }

    public function deleteUserSession(Users $user)
    {
        $sid = $this->redis->hget(static::KEY_USER_SID, $user->getAuthIdentifier());
        Session::getHandler()->destroy($sid);

    }
}
