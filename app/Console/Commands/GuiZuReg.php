<?php

namespace App\Console\Commands;
use App\Facades\SiteSer;
use App\Models\LevelRich;
use App\Models\UserBuyGroup;
use App\Models\Users;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class GuiZuReg extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'guizureg {--n=1 : 生成用户数量} {--p=1000 : 钻石数量} {--v=1101 : vip代号}';

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
        $num=$this->option('n');
        $p=$this->option('p');
        $v=(string)$this->option('v');
        //分组配置，nums是产生对应的数量用户，points钱数，道具id，expire道具赠送天数
        $created = $logined = date('Y-m-d H:i:s');
        $end_time = date('Y-m-d H:i:s', time() + 86400 * 40);
//线上配置 46
        $groupArr = [
            ['nums' => $num, 'did' => 1, 'points' => $p, 'vip_end' => $end_time, 'vip' => $v],
            //array('nums'=>500, 'did'=>46, 'points'=>1000,'vip_end'=>$end_time,'vip'=>'1101'),
        ];
        //1天秒数目
        $oneday = 86400;
        $password = md5('sex8vip888');//$mailpreFix,$mailSuffix,$groupArr可配置
        $time1 = microtime(true);
        $this->info('请稍等。。。');
        foreach ($groupArr as $item) {
            $this->genRegUsers($item, $oneday, $created, $logined, $password);
        }
       $this->info('处理完成，耗时'.intval(microtime(true) - $time1) . 's');
    }

    //获取随机的昵称，要与用户表对比

    private function genRegUsers(array $arr, &$oneday, &$created, &$logined, &$password)
    {
        for ($i = 0; $i < $arr['nums']; $i++) {
            $username = $this->checkUsername($this->getUserNameRand());
            $nickname = $this->checkNickName($this->getNickNameRand());
            //查看是否有赠送等级
            $vip = isset($arr['vip']) ? $arr['vip'] : 0;
            $vip_end = isset($arr['vip_end']) ? $arr['vip_end'] : 0;
            $did = isset($arr['did']) ? $arr['did'] : 0;
            $is_rich = $is_points = $is_vip = false;
            $userdata = [
                'did' => $did,
                //'roled'=> 3,        //todo
                'username' => $username,
                'nickname' => $nickname,
                'password' => md5($password),
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


            //开通贵族
            if (!empty($arr['vip']) && !empty($arr['vip_end'])) {
                $userdata['vip'] = $arr['vip'];
                $userdata['vip_end'] = $arr['vip_end'];
                $is_vip = true;
            }

            //插入数据库
            $newUser = Users::create($userdata);
            $newUser=Users::onWriteConnection()->find($newUser->uid);
            //增加开通记录--后台赠送
            if ($is_vip && $newUser) {
                $group = LevelRich::find($vip);
                $system = unserialize($group['system']);
                UserBuyGroup::create([
                    'uid' => $newUser->uid,
                    'gid' => $group['gid'],
                    'level_id' => $vip,
                    'type' => 3,
                    'rid' => 0,
                    'create_at' => date('Y-m-d H:i:s'),
                    'end_time' => $arr['vip_end'],
                    'status' => 1,
                    'open_money' => $system['open_money'],
                    'keep_level' => $system['keep_level'],
                ]);
            }

            Redis::hset('husername_to_id:'.SiteSer::siteId(), $username, $newUser->uid);
            Redis::hset('hnickname_to_id:'.SiteSer::siteId(), $nickname, $newUser->uid);

            logger()->info('贵族用户生成', $newUser->toArray());
        }
    }

//获取随机的注册帐号，要与用户表对比

    private function checkUsername($username)
    {
        if (Redis::hExists('husername_to_id:'.SiteSer::siteId(), $username) || Users::where('username', $username)->exists()) {
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
