<?php

namespace App\Console\Commands;

use App\Models\Pack;
use App\Models\Recharge;
use App\Models\UserMexp;
use App\Models\Users;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class XingBaReg extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xingbareg {--n=1 : 生成用户数量} {--p=0 : 钻石数量} {--gid=} {--expire=} {--rich=} {--lv_rich=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成贵族账号';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $num = $this->option('n');
        $p = $this->option('p');
        $gid = $this->option('gid');
        $expire = $this->option('expire');
        $rich = $this->option('rich');
        $lv_rich = $this->option('lv_rich');
        //分组配置，nums是产生对应的数量用户，points钱数，道具id，expire道具赠送天数
        $groupArr = [
            ['nums' => $num, 'did' => 17, 'points' => $p, 'gid' => $gid, 'expire' => $expire, 'rich' => $rich, 'lv_rich' =>$lv_rich],
        ];
        $created = $logined = date('Y-m-d H:i:s');
        //1天秒数目
        $oneday = 86400;
        $password = md5('sex8vip888');//$mailpreFix,$mailSuffix,$groupArr可配置
        $time1 = microtime(true);
        $this->info('请稍等。。。');
        foreach ($groupArr as $item) {
            $this->genRegUsers($item, $oneday, $created, $logined, $password);
        }
        $this->info('处理完成，耗时' . intval(microtime(true) - $time1) . 's');
    }

    //获取随机的昵称，要与用户表对比

    private function genRegUsers(array $arr, &$oneday, &$created, &$logined, &$password)
    {
        for ($i = 0; $i < $arr['nums']; $i++) {
            $username = $this->checkUsername($this->getUserNameRand());
            $nickname = $this->checkNickName($this->getNickNameRand());
            $password = $this->getPassWordRand();
            //查看是否有赠送等级
            $is_rich = $is_points = false;
            $userdata = [
                'did' => isset($arr['did']) ? $arr['did'] : 0,
                'username' => $username,
                'nickname' => $nickname,
                'password' => $password,
                'created' => $created,
                'logined' => $logined,
            ];
            if (!empty($arr['rich']) && !empty($arr['lv_rich'])) {
                $userdata['rich'] = $arr['rich'];
                $userdata['lv_rich'] = $arr['lv_rich'];
                $is_rich = true;
            }

            //查看是否有赠送钻石
            if (!empty($arr['points'])) {
                $userdata['points'] = $arr['points'];
                $is_points = true;
            }

            //插入数据库
            $newUser = Users::create($userdata);
            $newUser = Users::onWriteConnection()->find($newUser->uid);
            //将送钱的记录放入到记录表
            if ($is_points) {
                Recharge::create([
                    'uid' => $newUser->uid,
                    'points' => $arr['points'],
                    'created' => date('Y-m-d H:i:s', time()),
                    'pay_type' => 5,
                    'pay_status' => 1,
                    'nickname' => $nickname,
                ]);
            }
            //如有赠送等级则插入送经验记录
            if ($is_rich) {
                UserMexp::create([
                    'uid' => $newUser->uid,
                    'exp' => $arr['rich'],
                    'status' => 2,
                    'type' => 1,
                    'roled' => 0,
                    'curr_exp' => 0,
                ]);
            }
            if( !empty($arr['expire'])  &&  !empty($arr['gid']) ){
                $expire = $arr['expire']*$oneday;
                //将赠送的道具，绑定给指定用户
                Pack::create(array(
                    'uid'=>$newUser->uid,
                    'gid'=> $arr['gid'],
                    'num'=> 1,
                    'expires'=> time()+$expire
                ));
            }
            Redis::hmset('huser_info:' . $newUser->uid, $newUser->toArray());

            Redis::hset('husername_to_id', $username, $newUser->uid);
            Redis::hset('hnickname_to_id', $nickname, $newUser->uid);

            logger()->info('性吧用户生成', $newUser->toArray());
        }
    }

//获取随机的注册帐号，要与用户表对比

    private function checkUsername($username)
    {
        if (Redis::hExists('husername_to_id', $username) || Users::where('username', $username)->exists()) {
            $username = $this->getUserNameRand(true);
            return $this->checkUsername($username);
        }
        return $username;
    }

    private function getUserNameRand($salt = false)
    {
        //这2个参数可配置可以使用注册邮箱重复率下降
        //性吧资源
        static $mailpreFix = 'dl_';//注册邮箱的前缀
        static $mailSuffix = '@agent.com';//注册邮箱的后缀
        static $randkeys = [
            "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z",
            0, 1, 2, 3, 4, 5, 6, 7, 8, 9,
        ];
        static $randkeys2 = ["A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z"];
        $len = mt_rand(4, 8);//长度随机
        $arrKeys = array_rand($randkeys, $len);
        $str = '';
        $i = 0;
        if ($salt == true) {
            $len = count($arrKeys);
            $j = mt_rand(0, $len - 1);
        }
        foreach ($arrKeys as $item) {
            if ($salt && $j == $i) {
                $str .= $randkeys2[array_rand($randkeys2, 1)];
            } else {
                $str .= $randkeys[$item];
            }
            $i++;
        }
        return $mailpreFix . $str . $mailSuffix;
    }

    private function checkNickName($nickname)
    {
        if (Users::where('nickname', $nickname)->exists()) {
            $nickname = $this->getNickNameRand(true);
            return $this->checkNickName($nickname);
        }
        return $nickname;
    }

    private function getNickNameRand($salt = false)
    {
        static $nicknameArr = [
            "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z",
            "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z",
            0, 1, 2, 3, 4, 5, 6, 7, 8, 9,
            '夏', '苏', '浅', '诗', '漠', '染', '安', '陌', '木', '伊', '帆', '凉', '落', '尘', '语', '轩', '歌', '熙', '夕', '影', '然', '枫', '风', '吕',
        ];
        static $repeatArr = [
            '颜', '唯', '洛', '雨', '悠', '子', '筱', '简', '晗', '宇', '景', '小',
        ];

        $randKeys = array_rand($nicknameArr, 8);
        $str = '';
        $i = 0;
        if ($salt == true) {
            $j = mt_rand(0, 7);
        }

        foreach ($randKeys as $item) {
            if ($salt && $j == $i) {
                $str .= $repeatArr[array_rand($repeatArr, 1)];
            } else {
                $str .= $nicknameArr[$item];
            }
            $i++;
        }
        return $str;
    }

    private function getPassWordRand($salt = false)
    {
        static $randkeys = [
            "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z",
            0, 1, 2, 3, 4, 5, 6, 7, 8, 9,
            "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z",
        ];
        static $randkeys2 = ["A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z"];
        $len = mt_rand(15, 18);//长度随机
        $arrKeys = array_rand($randkeys, $len);
        $str = '';
        $i = 0;
        if ($salt == true) {
            $len = count($arrKeys);
            $j = mt_rand(0, $len - 1);
        }
        foreach ($arrKeys as $item) {
            if ($salt && $j == $i) {
                $str .= $randkeys2[array_rand($randkeys2, 1)];
            } else {
                $str .= $randkeys[$item];
            }
            $i++;
        }
        return $str;
    }
}
