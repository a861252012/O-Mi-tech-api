<?php

namespace App\Services\User;

use App\Entities\UserShare;
use App\Events\ShareUser;
use App\Facades\SiteSer;
use App\Facades\UserSer;
use App\Models\Agents;
use App\Models\AgentsRelationship;
use App\Models\Area;
use App\Models\UserBuyGroup;
use App\Models\UserGroup;
use App\Models\UserGroupPermission;
use App\Models\UserModNickName;
use App\Models\Users;
use App\Services\RedisCacheService;
use App\Services\Service;
use App\Services\ShareService;
use App\Services\UserGroup\UserGroupService;
use App\Services\User\RegService;
use DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;
use Mockery\Exception;

class UserService extends Service
{
    const KEY_USER_INFO = 'huser_info:';
    const TTL_USER_INFO = 216000;

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
     * @param int $agent
     * @param int $invite_code
     * @return bool|int
     */
    public function register(array $user, $gift = [], $agent = 0, $invite_code = 0)
    {
        $site_id = SiteSer::siteId();
        $newUser = Arr::only($user, [
            'did',
            'username',
            'password',
            'nickname',
            'cc_mobile',
            'roled',
            'exp',
            'pop',
            'created',
            'status',
            'province',
            'city',
            'county',
            'video_status',
            'rich',
            'lv_exp',
            'lv_rich',
            'pic_total_size',
            'pic_used_size',
            'lv_type',
            'icon_id',
            'uuid',
            'xtoken',
            'origin',
            'sex',
            'site_id',
        ]);
        $newUser['created'] = date('Y-m-d H:i:s');
        $newUser['logined'] = date('Y-m-d H:i:s');
        $newUser['site_id'] = $site_id;
        if (strlen($newUser['password']) != 32) {
            $newUser['password'] = md5($newUser['password']);
        }
        try {
            /**
             * ????????????
             */
            DB::beginTransaction();
            //????????????===================================

            $uid = DB::table($userTable = (new Users)->getTable())->insertGetId($newUser);
            if (!$uid) throw new \ErrorException('insert to user table error.');

            //??????????????????
            if (Arr::get($user, 'roled') == 3) {
                DB::table($userTable)->where('uid', $uid)->update(['rid' => $uid]);
            }

            DB::commit();

            // cnt + 1
            $regService = resolve(RegService::class);
            $cnt = $regService->incr();

            //????????????
            if ($points = Arr::get($gift, 'points')) {
                if (!$this->updateUserOfPoints($uid, '+', $points, 5)) {
                    throw new \ErrorException('update user of points exception at register');
                }
            }

            //????????????
            if (Arr::get($gift, 'vip') && Arr::get($gift, 'vip_end')) {
                if (!$this->updateUserOfVip($uid, Arr::get($gift, 'vip'), 3, Arr::get($gift, 'vip_end'))) {
                    throw new \ErrorException('update user of vip exception at register');
                }
            }

            //??????????????????
            if ($agent) {
                if (DB::table((new Agents)->getTable())->where('id', $agent)) {
                    if (!$this->setUserAgents($uid, $agent)) {
                        throw new \ErrorException('update user of agents exception  at register');
                    }
                }
            }

            //????????????===================================


        } catch (\Exception $e) {
            Log::info('?????? ????????????:' . $e->getMessage());
            DB::rollback();
            return false;
        }

        return $uid;
    }

    /**
     * [updateUserOfPoints ??????????????????]
     *
     * @see     src\Video\ProjectBundle\Controller\VideoBaseController.php addUserPoints function
     * @author  dc <dc@wisdominfo.my>
     * @version 2015-11-05
     * @param   integer $uid [??????id]
     * @param   string $operation [?????????????????????+ -??????]
     * @param   integer $points [???????????????]
     * @param   integer $pay_type [???????????????1 ???????????????2 ??????  3 ???????????????   4???????????? 5???????????? 6????????????????????? 7????????????]
     * @return  bool                  [??????true ??????false]
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

        //????????????
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
     * ????????????????????????????????????????????????????????????
     */
    public function getUserPublicInfoByUid($uid)
    {
        $user = $this->getUserByUid($uid);
        unset($user->cc_mobile);
        unset($user->safemail);
        unset($user->safemail_at);
        unset($user->first_charge_time);
        $user->password = '446d7f90ac03e025c741983cef31325c';
        $user->trade_password = '446d7f90ac03e025c741983cef31325c';
        $user->last_ip = '8.8.8.8';
        $user->username = $uid."@qq.com";
        return $user;
    }

    /**
     * ???redis??????????????????????????????db,??????redis
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
            $arr = $this->redis->hgetall($hashtable);
            $user = (new Users())->setRawAttributes($arr, true);
            $user->exists = true;
        } else {
            $user = Users::allSites()->find($uid);
            if ($user && $user->exists) {
                $checkin = $this->redis->hmset($hashtable, $user->toArray());
                $this->redis->expire($hashtable, self::TTL_USER_INFO);
            }
        }
        return $this->user = $user;
    }

    public function getUserByUidAllSite($uid)
    {
        if (!$uid || !is_numeric($uid)) {
            return null;
        }
        $hashtable = static::KEY_USER_INFO . $uid;

        if ($this->redis->Hexists($hashtable, 'uid')) {
            $arr = $this->redis->hgetall($hashtable);
            $user = (new Users())->setRawAttributes($arr, true);
            $user->exists = true;
        } else {
            $user = Users::query()->where('uid', $uid)->allSites()->first();
            if ($user && $user->exists) {
                $this->redis->hmset($hashtable, $user->toArray());
                $this->redis->expire($hashtable, self::TTL_USER_INFO);
            }
        }
        return $this->user = $user;
    }

    public function userExists($uid)
    {
        return $this->redis->exists(static::KEY_USER_INFO . $uid);
    }

    /**
     * [userReset ????????????redis???????????????]
     *
     * @author  dc <dc@wisdominfo.my>
     * @version 2015-11-10
     * @param   int $uid ??????id
     * @return  array      ????????????
     */
    public function getUserReset($uid)
    {
        if (!$uid || !is_numeric($uid)) {
            throw new Exception('Please make sure $uid is a numeric');
        }
        $user = Users::query()->where('uid', $uid)->allSites()->first();
        if (!$user) {
            return false;
        }
        $user = $user->toArray();

        //??????redis;
        $hashtable = static::KEY_USER_INFO . $uid;
        $this->redis->hmset($hashtable, $user);
        $this->redis->expire($hashtable, self::TTL_USER_INFO);
        return $user;
    }

    /**
     * [updateUserOfVip ?????????????????????????????????]
     * @param      $uid [??????ID]
     * @param      $level_id [??????id]
     * @param      $type [????????????]
     * @param      $days [??????]
     * @param bool $fill_expires [???????????????,????????????????????????]
     * @return bool[??????true ??????false]
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
            //??????????????????

            //????????????????????????????????????????????????????????????????????????
            if ($vip > $level_id) return true;
            //??????????????????
            $expires = date('Y-m-d H:i:s', strtotime($userVip->vip_end) + ($days * 86400));

            Users::where('uid', $uid)->update(['vip' => $level_id, 'vip_end' => $expires]);
            UserBuyGroup::where('uid', $uid)->where('level_id', $level_id)->orderBy('id', 'desc')->limit(1);
        } elseif ($vip < $level_id) {
            $group = UserGroup::where('level_id', $level_id)->first();
            $group['system'] = unserialize($group['system']);
            if (!is_array($group['system'])) return false;

            //????????????
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

            //????????????
            if ($gift_level = $group['system']['gift_level']) {
                $level = UserGroup::where('level_id', $gift_level)->first(['level_value']);
                $level_value = $user['rich'] + $level->level_value;
                $vip_data = array_merge($vip_data, ['lv_rich' => $gift_level, 'rich' => $level_value]);
            }
            Users::where('uid', $uid)->update($vip_data);

        }
        if (!$this->getUserReset($uid)) {
            throw new Exception('updateUserOfVip getUserReset function for update user cache to redis was error');
        }
        return true;
    }


    /**
     * [checkVipStatus ??????vip??????]
     *
     * @author  dc <dc#wisdominfo.my>
     * @version 2015-11-11
     * @param   int $uid ??????id
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
     * [checkVipStatus ??????vip??????]
     *
     * @author  dc <dc#wisdominfo.my>
     * @version 2015-11-11
     * @param   int $uid ??????id
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

    public function cancelVip($uid = 0)
    {
        if (!$uid) {
            return;
        }
        $data = [
            'vip' => 0,
            'vip_end' => '',
            'hidden' => 0,
        ];
        $this->updateUserInfo($uid, $data);

        $pack = VideoPack::query()->where('uid', $uid)->whereBetween('gid', [120101, 120107])->delete();
        Redis::del('user_car:' . $uid);
        return true;
    }

    /**
     * ????????????????????????
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
     * [getUserByUsername ????????????????????????]
     *
     * @author  dc <dc#wisdominfo.my>
     * @version 2015-11-13
     * @param   string $username [????????????]
     * @return  array|bool               ??????????????????
     */
    public function getUserByUsername($username)
    {
        $uid = $this->getUidByUsername($username);
        return $this->getUserByUid($uid);
    }

    /**
     * [getUserByUsername ????????????????????????]
     *
     * @author  dc <dc#wisdominfo.my>
     * @version 2015-11-13
     * @param   string $nickname [????????????]
     * @return  array|bool               ??????????????????
     */
    public function getUserByNickname($nickname)
    {
        $uid = $this->getUidByNickname($nickname);
        return $this->getUserByUid($uid);
    }

    public function getUserByCCMobile($cc_mobile)
    {
        if (empty($cc_mobile)) {
            return null;
        }
        $uid = $this->getUidByCCMobile($cc_mobile);
        return $this->getUserByUid($uid);
    }

    public function getUidByUsername($username)
    {
        return Users::query()->where('username', $username)->where('site_id', SiteSer::siteId())->value('uid');
    }
    public function getUidByNickname($nickname)
    {
        return Users::query()->where('nickname', $nickname)->where('site_id', SiteSer::siteId())->value('uid');
    }
    public function getUidByCCMobile($cc_mobile)
    {
        return Users::query()->where('cc_mobile', $cc_mobile)->where('site_id', SiteSer::siteId())->value('uid');
    }

    /**
     *
     */
    public function addPoints($uid = 0, $points = 0)
    {

    }

    /**
     * ????????????????????????????????????????????????/???????????????????????????????????????
     * @Author  Nicholas ??????
     * @param   int $uid
     * @param   int $currentPage
     * @param   bool $fid
     * @param   int $perPage
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
            if ($user) {
                /*
                $flashVer = SiteSer::config('publish_version');
                !$flashVer && $flashVer = 'v201504092044';

                $list = Redis::get('home_all_' . $flashVer. ':'.SiteSer::siteId());
                $list = str_replace(['cb(', ');'], ['', ''], $list);
                $J_list = json_decode($list, true);

                foreach($J_list['rooms'] as $O_list){
                    if($O_list['rid']==$uid){
                        $live_status = $O_list['live_status'];
                        $attens = $O_list['attens'];
                    }
                }
                */
                $live_status = Redis::hGet('hvediosKtv:' . $user->uid,'status')-0;

                $attens = $this->getUserAttensCount($user->uid,false);

                $items->push([
                    'headimg' => $user->headimg . '.jpg',
                    'rid' => $user->uid,
                    'username' => $user->nickname,//190417: ???????????????????????? by stanly
                    'nickname' => $user->nickname,
                    'roled' => $user->roled,
                    'lv_exp' => $user->lv_exp,
                    'lv_rich' => $user->lv_rich,
                    'cover' => $user->cover ? $user->cover : '',
                    'fid' => $uid,
                    'live_status' => $live_status,
                    'attens' => $attens,
                ]);
            }
        }

        $paginator = new LengthAwarePaginator($items, $total, $perPage, $currentPage);
        return $paginator;
    }

    /**
     * ????????????????????????????????????/?????????????????????????????????
     * @param      $uid
     * @param bool $flag
     * @return mixed
     * @Author Orino
     */
    public function getUserAttensCount($uid, $flag = true)
    {
        if ($flag) {
            return $this->redis->zcard('zuser_attens:' . $uid);//?????????????????????????????????
        } else {
            return $this->redis->zcard('zuser_byattens:' . $uid);//????????????????????????????????????
        }
    }

    /**
     * ?????????????????? ???????????? TODO ??????
     *
     * @param        $headimg
     * @param int $size
     * @return string
     */
    public function getHeadimg($headimg, $size = 180)
    {
        return $headimg ? SiteSer::config('img_host') . '/' . $headimg . '.jpg' . ($size == 150 ? '' : '?w=' . $size . '&h=' . $size)
            : SiteSer::config('cdn_host') . '/src/img/head_' . $size . '.png';
    }

    /**
     * ????????????????????????
     * @param $uid
     * @return bool
     */
    public function checkUserLoginAsset($uid)
    {
        $user = $this->getUserByUid($uid);
        if ($user['points'] >= SiteSer::config('user_points_min')) {
            return true;
        }
        if ($user['roled'] == 3 || $this->checkUserVipStatus($uid)) {
            return true;
        }
        return false;
    }

    /**
     * ?????????????????????
     * <p>
     *  ???????????????????????? ?????????redis????????? ??????????????????
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
     * ??????nickname????????????
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
     * ???????????????????????????
     * <p>
     *  ????????????????????????????????????????????????????????????????????????
     * </p>
     * @param int $lv_rich
     * @return bool
     */
    public function modLvRich($lv_rich)
    {
        if (!$lv_rich) {
            throw new Exception('Mod Lv_rich should input a int type');
        }

        // ???????????????????????????????????????????????????????????????????????????????????????????????????
        if ($lv_rich <= $this->user['lv_rich']) {
            return true;
        } else {
            // ?????????????????? ????????????
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
     * ?????????????????????????????????????????? ??? ??????
     */
    public function getModNickNameStatus()
    {
        /** ???????????????????????? */
        $flag = (int) Redis::hget('modify.nickname', $this->user['uid']);
        if ($flag >= 1) {
            return ['num' => $flag];
        }

        /* ?????????????????? */
        if (!empty($this->user['guard_id']) && time() < strtotime($this->user['guard_end'])) {
            $num = 0;
            if (Redis::hget('hguardian_info:' . $this->user['guard_id'], 'rename')) {
                $guardianRenameCount = Redis::hget('hguardian_info:' . $this->user['guard_id'], 'rename_limit');
                $modCountRedisKey = 'smname_num:' . date('m') . ':' . auth()->id();
                $modCount = Redis::get($modCountRedisKey) ?? 0;

                $num = $guardianRenameCount - $modCount;
            }

            if ($num > 0) {
                return ['num' => $num];
            }
        }

        // TODO ???????????????
        if ($this->user['vip']) {
            $vip = $this->user['vipGroup']['permission'];
            return $this->checkModNickNameStatus($vip);
        }

        // ?????????????????????
//        if ($this->user['lv_rich']) {
//            $lv_id = $this->user['lvGroup']['permission'];
//            return $this->checkModNickNameStatus($lv_id);
//        }

        // ??????????????????????????? ???????????????
        $uMod = UserModNickName::where('uid', $this->user['uid'])->first();
        if ($uMod && $uMod->exists) {
            return ['num' => 0];
        } else {
            return ['num' => 1];
        }
    }

    /**
     * ????????????????????????????????????????????????????????????
     *
     * @param $permission
     * @return array
     */
    protected function checkModNickNameStatus($permission)
    {
        // ????????????
        if ($permission['modnickname'] == 0) {
            return ['num' => 0];
        }
        // ????????????
        if ($permission['modnickname'] == -1) {
            return ['num' => -1];
        }
        // ??????????????? ??? ??? ??? ?????????????????????
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
     * @todo        ???????????????????????????????????????
     * @param $uid [??????id]
     * @param $pid [???????????????id]
     * @return bool
     * @author      dc
     * @description ???????????????setUserAttens
     */
    public function setFollow($uid, $pid)
    {
        if (!$uid || !is_numeric($uid) || !$pid || !is_numeric($pid)) {
            throw new Exception('Please make sure $uid and  $pid is a numeric');
        }
        //????????????????????????
        if ($this->checkFollow($uid, $pid)) return false;
        $timestamp = time();
        //?????????????????????=>???????????????
        $this->make('redis')->zadd('zuser_attens:' . $uid, $timestamp, $pid);
        //?????????????????????=>???????????????
        $this->make('redis')->zadd('zuser_byattens:' . $pid, $timestamp, $uid);
        return true;
    }

    /**
     * @param null $uid ????????????id
     * @param null $pid ??????????????????id
     * @param bool|true $flag
     * @param bool|false $reservation
     * @return bool
     * @author      dc
     * @version     20151022
     * @description ???????????????????????????????????????????????? ??????????????? ???????????? @checkUserAttensExists
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
     * @todo        ???????????????????????????????????????
     * @param $uid [??????id]
     * @param $pid [???????????????id]
     * @return bool
     * @author      dc
     * @version     20151022
     * @description ???????????????delUserAttens
     */
    public function delFollow($uid, $pid)
    {

        if (!$uid || !is_numeric($uid) || !$pid || !is_numeric($pid)) {
            throw new Exception('Please make sure $uid and  $pid is a numeric');
        }
        //????????????????????????
        //if(!$this->checkFollow($uid, $pid)) return false;
        //?????????????????????id
        $this->make('redis')->zrem('zuser_attens:' . $uid, $pid);
        $this->make('redis')->zrem('zuser_byattens:' . $pid, $uid);
        return true;
    }


    /**
     * @param     $uid [????????????id]
     * @param int $limit [?????????????????? ???????????????]
     * @param     $table [????????????]
     * @return bool [??????true?????????????????????]
     * @author      dc
     * @version     20151023
     * @description ???????????????????????? VideoBaseController?????? checkAstrictUidDay??????
     */
    public function checkUserSmsLimit($uid, $limit, $table)
    {
        if (!$uid || !$table) {
            throw new Exception('the [checkUserSmsLimit] function params was failure ');
        }
        $redis = $this->make('redis');
        $redisKey = 'h' . $table . date('Ymd');

        //????????????????????????????????????1?????????
        if (!$redis->exists($redisKey)) {
            $redis->del('h' . $table . date('Ymd', strtotime('-1 day')));
            return 1;
        }
        $total = intval($redis->hget($redisKey, $uid));
        if ($total < $limit) return $total;
        return 0;
    }


    /**
     * @param $uid [??????id]
     * @param $num [??????]
     * @param $table [????????????]
     * @return bool [????????????]
     * @author      dc
     * @version     20151023
     * @description ???????????? VideoBaseController?????? setAstrictUidDay???????????????????????????????????????????????????
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
     * [getUserHiddenPermission ??????id????????????????????????????????????]
     *
     * @todo    ????????????????????????redis
     * @param array $user [????????????,?????????uid,lv_rich, vip, vip_end?????????]
     *
     * @return mixed
     * @author  dc@wisdominfo.my
     * @version 20151127
     *
     */
    public function getUserHiddenPermission($user)
    {
        //vip????????????
        if (!($user['vip'] > 0 && $user['vip_end'] > date('Y-m-d H:i:s'))) return false;

        //?????????????????????
        $userBuyGroup = $userBuyGroup = UserBuyGroup::where('uid', $user['uid'])->orderby('auto_id', 'desc')->first(['gid']);
        $buyGid = $userBuyGroup ? $userBuyGroup->gid : 0; //?????????????????????
        $lv_rich = $user['lv_rich'];    //???????????????
        $UserGroupPermission = UserGroupPermission::where('allowstealth', 1)->where(function ($query) use ($lv_rich, $buyGid) {
            $query->where('gid', $buyGid)->Orwhere('gid', $lv_rich);
        })->first();
        if (!$UserGroupPermission) return false;
        return true;
    }

    /**
     * todo
     * @param $userinfo [????????????,??????????????????????????????]
     * @param $des_key [????????????]
     * @return string [??????????????????]
     * @author       dc
     * @description  ??????????????????
     */
    public function get3Des($userinfo, $des_key)
    {
        $iv = 'onevideo';
        $encrypt = json_encode($userinfo, JSON_UNESCAPED_SLASHES); //??????
        $tb = @mcrypt_module_open(MCRYPT_3DES, '', 'cbc', ''); //?????????????????? 256??? 128/8 = 16 ?????? ??????IV?????????
        @mcrypt_generic_init($tb, $des_key, $iv); //?????????????????????
        $encrypt = $this->_PaddingPKCS7($encrypt);//????????????????????????,???????????????????????????????????????
        $cipher = @mcrypt_generic($tb, $encrypt); //?????????????????????
        $cipher = base64_encode($cipher);//????????????base64??????
        @mcrypt_generic_deinit($tb); //????????????????????????
        @mcrypt_module_close($tb); //??????????????????
        return $cipher;
    }

    /**
     * todo
     * @param $data [???3des???????????????]
     * @return string
     * @author      dc
     * @description ??????//?????????????????? ???????????????PaddingPK27??????
     */
    private function _PaddingPKCS7($data)
    {
        /* ???????????????????????????????????????,MCRYPT_3DES??????????????????,cbc??????????????????,??????mcrypt_module_open(MCRYPT_3DES,'','cbc','')?????????*/
        $block_size = @mcrypt_get_block_size(MCRYPT_3DES, 'cbc');
        $padding_char = $block_size - (strlen($data) % $block_size); // ???????????????????????????
        $data .= str_repeat(chr($padding_char), $padding_char);        // ????????????
        return $data;
    }

    public function getRank($key, $offset, $limit)
    {
        /** @var \Redis $redis */
        $redis = $this->make('redis');
        $mouth = date('Ym', time());
        $member_rank = ['zrank_rich_day', 'zrank_rich_week', 'zrank_rich_month:' . $mouth, 'zrank_rich_history', 'rank_game_day', 'rank_game_week', 'rank_game_month:' . $mouth, 'rank_game_his'];

        if (in_array($key, $member_rank)) {

            $rank = $redis->zRevRange($key . ':' . SiteSer::siteId(), $offset, $limit, true);
        } else {
            $rank = $redis->zRevRange($key, $offset, $limit, true);
        }


        $ret = [];
        static $userCache = [];//??????????????????
        foreach ($rank as $uid => $score) {
            $userObj = $this->getUserByUid($uid);
            $userInfo = $userObj ? $userObj->toArray() : [];
            if (count($userInfo)) {
                $userInfo['headimg'] .= '.jpg';
            }

            $ret[] = array_merge([
                'uid' => $uid,
                'score' => $score,
            ],
                isset($userCache[$uid]) ? $userCache[$uid]
                    : ($userCache[$uid] = array_only($userInfo, ['lv_rich', 'lv_exp', 'username', 'nickname', 'headimg', 'icon_id', 'description', 'vip', 'sex', 'site_id', 'cover']))
            );
        }
        $result = [];
        $site_id = SiteSer::siteId();
        if (!empty($ret)) {
            foreach ($ret as $key => $value) {
                $userObj = $this->getUserByUid($value['uid']);
                if ($userObj) {
                    $userInfo = $userObj->toArray();
                    $ret[$key]['age'] = isset($userInfo['age']) ? $userInfo['age'] . '???' : '??????18???';
                    $ret[$key]['starname'] = isset($userInfo['starname']) ? $userInfo['starname'] : '????????????';
                    $ret[$key]['description'] = $userInfo['description'] ?: '????????????????????????TA?????????????????????';
                    if (!$userInfo['province'] || !$userInfo['city']) {
                        $ret[$key]['procity'] = '?????????????????????';
                    } else {
                        $ret[$key]['procity'] = $this->getArea($userInfo['province'], $userInfo['city'], $userInfo['county']);//?????????code?????????????????????
                    }
                }
            }
            if (!empty($ret)) {
                foreach ($ret as $key => $value) {
                    $result[] = $value;
                }
            }

        }

        return $result;
    }

    public function getAllRank()
    {
        $key = sprintf('vrank_data%s%s:%d', '-', App::getLocale(), SiteSer::siteId());
        return Redis::get($key) ?? Redis::get('vrank_data:' . SiteSer::siteId());
    }

    /**
     * ???3????????????????????????code?????????4?????????????????????
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
            //ksort($areas,SORT_NUMERIC);//???????????????????????????
            return implode($limit, $areas);
        }
        //?????????????????????????????????Redis
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
        ksort($area, SORT_NUMERIC);//???????????????????????????
        return implode($limit, $area);
    }

    /**
     * ?????????????????????????????????
     */
    protected function checkUser()
    {
        if (!$this->user instanceof Users) {
            throw new Exception('Please make sure $user is a App\Models\Users object');
        }
    }

    public function deleteUserSession(Users $user)
    {
        $sid = resolve(RedisCacheService::class)->sid($user->getAuthIdentifier());
        Session::getHandler()->destroy($sid);

    }

    /**
     * ????????????????????????
     * todo ????????????????????????????????????
     * @param $uid
     * @param $password
     * @return bool
     */
    public function checkUserTradePassword($uid, $password)
    {
        $user = $this->getUserByUidAllSite($uid);
        return $user['trade_password'] == md5(md5(trim($password), $uid));
    }

    /**
     * ??????????????????
     * @param $uid
     * @param $password
     * @return bool
     */
    public function checkUserPassword($uid, $password)
    {
        $user = $this->getUserByUid($uid);
        return $user['password'] == md5(trim($password));
    }

    /**
     * [updateUserTradePassword ????????????????????????]
     * @param $uid
     * @param $password
     * @return bool
     */
    public function updateUserTradePassword($uid, $password)
    {
        if (!$uid || !$password) return false;
        $password = md5(md5(trim($password), $uid));
        if (Users::whereUid($uid)->update(array('trade_password' => $password))) {
            resolve(UserService::class)->getUserReset($uid);
            return $password;
        }
        return false;
    }

    public function anchorlist()
    {
        $flashVersion = SiteSer::config('publish_version');
        $pulish_version = Redis::get('home_all_' . $flashVersion . ':' . SiteSer::siteId());
        $arrdata = str_replace(['cb(', ');'], ['', ''], $pulish_version);
        $arrdata = json_decode($arrdata, true);
        $arr = $arrdata['rooms'];//????????????redis?????????
        return $arr;

    }

    public function modifyMobile($user, $cc_mobile)
    {
        $uid = $user->uid;
        $old_cc_mobile = $user->cc_mobile;
        $site_id = SiteSer::siteId();

        // update/add
        $data = [
            'cc_mobile' => $cc_mobile,
        ];
        $this->updateUserInfo($uid, $data);

        /* ????????????????????????????????????????????????????????? */
        if (empty($user->cc_mobile) && !empty(UserShare::where('uid', $uid)->first())) {
            $shareService = resolve(ShareService::class);
            info('??????????????????????????????: ' . $shareService->modifyUserShare($user->id, ['is_mobile_match' => 1, 'match_date' => date('Y-m-d')]));

            /* ???????????????????????? */
            event(new ShareUser($user));
        }
    }

    public function getUserInfo($uid, $field = null)
    {
        $userModel = $this->getUserByUid($uid);
        $user = $userModel->toArray();
        if ($field) {
            return $user[$field];
        }
        return $user;
    }

    public function updateUserInfo($uid, array $user)
    {
        if (Users::allSites()->where('uid', $uid)->update($user) === false) {
            return false;
        };
        return $this->cacheUserInfo($uid, $user);
    }

    public function cacheUserInfo($uid, array $user = null)
    {
        $userCacheKey = self::KEY_USER_INFO . $uid;

        if ($user === null) {
            return $this->redis->del($userCacheKey);
        }

        if (!$this->redis->exists($userCacheKey)) {
            $userModel = Users::allSites()->find($uid);
            if (!$userModel) {
                return false;
            }
            $user = $userModel->toArray();
        }
        $result = $this->redis->hMset($userCacheKey, $user);
        if ($result) {
            $this->redis->expire($userCacheKey, self::TTL_USER_INFO);
        }
        return $result;
    }

    public function updateLoginedDaily($uid, $login_ip)
    {
        if (!$uid) {
            return;
        }
        if ($this->isTodayLogin($uid)) {
            return;
        }
        $data = [
            'logined' => date('Y-m-d H:i:s'),
            'last_ip' => $login_ip,
        ];
        $this->updateUserInfo($uid, $data);
        $this->markTodayLogin($uid);
    }

    public function markTodayLogin($uid)
    {
        $checkKey = 'logined:'. date('Ymd') .':'. $uid;
        $this->redis->setex($checkKey, (24 - date('G')) * 3600, 1);
    }

    public function isTodayLogin($uid)
    {
        $checkKey = 'logined:'. date('Ymd') .':'. $uid;
        return $this->redis->exists($checkKey);
    }
}
