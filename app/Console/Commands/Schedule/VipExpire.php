<?php

namespace App\Console\Commands\Schedule;

use App\Models\Users;
use App\Models\VideoMail;
use App\Models\VideoPack;
use App\Services\User\UserService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

class VipExpire extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vip_expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $_redisInstance = Redis::resolve();
//每天发私信计数
        $key1 = (array)$_redisInstance->keys('hvideo_mail*');

//当天验证密码计数，大于5就要出现验证码
        $key2 = (array)$_redisInstance->keys('keys_room_passwd*');//数组合并
//每天投诉建议计数
        $key3 = (array)$_redisInstance->keys('key_complaints_flag*');
//每天限制ip注册计数
        $key4 = (array)$_redisInstance->keys('hreg_ip_limit*');
        $keys = array_merge($key1,$key2,$key3,$key4);

        foreach( $keys as $item ){
            $_redisInstance->del($item);
        }

// TODO　贵族体系的到期通知
// 即将到期的通知
        $date = time() + 7*24*60*60; //提前7天通知 每天一条
        $data = Users::query()->where('vip','!=',0)->whereBetween('vip_end',[date('Y-m-d H:i:s'),date('Y-m-d H;i:s',$date)])->get();
        if($data){
            $msg = array(
                'rec_uid'=>'',
                'content'=>__('messages.Crontab.vipNearExpInfo'),
                'category'=>1,
                'created'=>date('Y-m-d H:i:s')
            );
            $temp = [];
            foreach($data as $value){
                $level_name = $_redisInstance->hGet('hgroups:special'.$value['vip'],'level_name');
                $msg['rec_uid'] = $value['uid'];
                $msg['content'] = __('messages.Crontab.vipNearExpInfo.reminder_msg',
                    [
                        'level_name' => $level_name,
                        'vip_end'    => $value['vip_end'],
                    ]
                );
                // 发送消息
                array_push($temp,$msg);
            }
            VideoMail::query()->insert($temp);
        }

// 已经到期的下掉贵族
        //$sql = 'select uid,vip,vip_end from video_user where vip!=0 and vip_end<"'.date('Y-m-d H:i:s').'"';
        $data= Users::query()->where('vip','!=',0)->where('vip_end','<',date('Y-m-d H:i:s'))->get();
        $pack = VideoPack::query();
        foreach($data as $user){
            $updateData = [
                'vip' => '0',
                'hidden' => '0',
                'vip_end' => '',
            ];
            resolve(UserService::class)->updateUserInfo($user['uid'], $updateData);

            $pack->where('uid',$user['uid'])->whereBetween('gid',[120101,120107])->delete();
            $_redisInstance->del('user_car:'.$user['uid']);
        }
    }
}
