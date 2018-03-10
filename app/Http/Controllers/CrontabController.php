<?php
/**
 * Created by PhpStorm.
 * User: irwin
 * Date: 2016/3/17
 * Time: 15:42
 */
namespace App\Controller;

use App\Models\Users;
use App\Models\Messages;
use App\Models\VideoPack;
use DB;

class CrontabController extends BaseController
{
    /**
     * @var $opCode 后台任务码对应不同任务执行
     * clearIp 清除ip功能
     * vipNearExpInfo 贵族将要到期提醒
     * vipExpNotify 贵族到期通知
     */
    private $opCode = array(
        0 => 'clearIp',
        1 => 'vipNearExpInfo',
        2 => 'vipExpNotify',
        3 => 'duraRoomMsgSend',
        4 => 'usrLoginTimeUpdate',
        5 => 'activityErrHandler'
    );

    /**
     * 获取一个任务名称，分发到各自任务函数中
     *
     * @return Response
     */
    public function index($task)
    {
        $code = array_search($task, $this->opCode);

        //按照操作码分发到各任务函数处理
        if ($code == 0) {
            $this->clearIp(); //每天清除redis缓存信息
        }
        if ($code == 1) {
            $this->vipNearExpInfo(); //贵族体系的即将到期通知
        }
        if ($code == 2) {
            $this->vipExpNotify(); //贵族体系的到期通知
        }
        if ($code == 3) {
            $this->duraRoomMsgSend(); //每5分钟定时推送预约房间开始信息
        }
        if ($code == 4) {
            $this->usrLoginTimeUpdate(); //用户登录时间更新
        }
        if ($code == 5) {
            $this->activityErrHandler(); //充值送礼活动发生超时等问题的容错处理
        }
    }

    /**
     * 每天清除redis缓存信息
     */
    private function clearIp()
    {
        // TODO　每天清除redis缓存信息
//        //每天发私信计数
//        $key1 = (array)$_redisInstance->keys('hvideo_mail*');
        //当天验证密码计数，大于5就要出现验证码
        $key2 = (array)$this->make('redis')->keys('keys_room_passwd*');//数组合并
        //每天投诉建议计数
        $key3 = (array)$this->make('redis')->keys('key_complaints_flag*');
        //每天限制ip注册计数
        $key4 = (array)$this->make('redis')->keys('hreg_ip_limit*');
        $keys = array_merge($key2, $key3, $key4);

        //清除以上redis键值
        foreach ($keys as $item) {
            $this->make('redis')->del($item);
        }
    }

    /**
     * 贵族即将到期提醒
     *
     */
    private function vipNearExpInfo()
    {
        // TODO　贵族体系的即将到期通知
        // 即将到期的通知
        $date = time() + 7 * 24 * 60 * 60; //提前7天通知 每天一条
        $data = Users::where('vip', '<>', 0)->where('vip_end', '>', date('Y-m-d H:i:s'))->where('vip_end', '<', date('Y-m-d H;i:s', $date))->get();
        if ($data) {
            $msg = array(
                'rec_uid' => '',
                'content' => '贵族保级即将失败提醒：您的贵族即将到期！请尽快充值保级！',
                'category' => 1,
                'created' => date('Y-m-d H:i:s')
            );
            foreach ($data as $key => $value) {
                $level_name = $this->make('redis')->hGet('hgroups:special' . $value['vip'], 'level_name'); //redis改键值里没有level_name字段
                $msg['rec_uid'] = $value['uid'];
                $msg['content'] = '贵族保级即将失败提醒：您的' . $level_name . '贵族到期日：' . $value['vip_end'] . '！请尽快充值保级！';

                //发送私信给用户
                Messages::create(array(
                    'send_uid' => 0,
                    'rec_uid' => $msg['rec_uid'],
                    'content' => $msg['content'],
                    'category' => 1,
                    'status' => 0,
                    'created' => date('Y-m-d H:i:s'),
                ));
            }
        }
    }

    /**
     * 贵族到期通知
     */
    private function vipExpNotify()
    {
        // TODO　贵族体系的到期通知
        //查询已过期贵族
        $data = Users::where('vip', '<>', 0)->where('vip_end', '<', date('Y-m-d H:i:s'))->get();
        if (!$data) {
            return;
        }

        //定义日志文件路径
        $logPath = BASEDIR . '/app/logs/cron_' . date('Y-m-d') . '.log';

        foreach ($data as $key => $value) {
            $msg = array(
                'uid' => $value['uid'],
                'vip' => $value['vip'],
                'vip_end' => $value['vip_end']
            );
            //开启事务
            try {
                DB::beginTransaction(); //DB::commit(); DB::rollback;
                Users::where('uid', $msg['uid'])->update(array(
                    'vip' => 0,
                    'vip_end' => null,
                    'hidden'=>0
                ));
                $this->make('redis')->hSet('huser_info:' . $msg['uid'], 'vip', 0);
                $this->make('redis')->hSet('huser_info:' . $msg['uid'], 'vip_end', '');
                $this->make('redis')->hSet('huser_info:' . $msg['uid'], 'hidden', 0);
                VideoPack::where('uid', $msg['uid'])->where('gid', '>=', 120101)->where('gid', '<=', 120107)->delete();
                $this->make('redis')->del('user_car:' . $msg['uid']);
                DB::commit();
            } catch (\Exception $e) {
                $this->logResult("UID：" . $msg['uid'] . " 事务结果：" . $e->getMessage() . "\n", $logPath);
                DB::rollback();
                return;
            }
        }
    }

    /**
     * 每五分钟定时推送预约房间开始信息
     */
    private function duraRoomMsgSend()
    {
        // TODO 每5分钟定时推送房间开始信息
        $keys = $this->make('redis')->getKeys('hroom_duration:*');
        if ($keys == false) {
            exit('没有预约房间记录');
        } else {
            //遍历所有预约房间 对于符合在当前时间之后2分半至7分半之间的开始预约信息发送系统消息
            foreach ($keys as $item) {
                $roomlist = $this->make('redis')->hGetAll($item);
                foreach ($roomlist as $room) {//每个rediskey值底下的所有key-value
                    $room = json_decode($room, true); //json转数组
                    $timecheck = date('Y-m-d H:i:s', strtotime($room['starttime']));//获取预约开始时间
                    $start = date('Y-m-d H:i:s', time() + 150);//当前时间后两分半 根据需求更改此处
                    $end = date('Y-m-d H:i:s', time() + 450);//当前时间后7分半 根据需求更改此处
                    if ($start < $timecheck && $end > $timecheck) {
                        if ($room['status'] == 0 && $room['reuid'] != 0) {
                            //发送系统消息给主播
                            Messages::create(array(
                                'send_uid' => 0,
                                'rec_uid' => $room['uid'],
                                'content' => '您开设的' . $room['starttime'] . '一对一约会房间快要开始了,请做好准备哦',
                                'category' => 1,
                                'status' => 0,
                                'created' => date('Y-m-d H:i:s'),
                            ));
                            //发送系统消息给用户
                            Messages::create(array(
                                'send_uid' => 0,
                                'rec_uid' => $room['reuid'],
                                'content' => '您预约的一对一预约房间' . $room['starttime'] . '快要开始了，请做好准备哦',
                                'category' => 1,
                                'status' => 0,
                                'created' => date('Y-m-d H:i:s'),
                            ));
                        }
                    }
                }
            }
        }
    }

    /**
     * 用户登录时间更新
     */
    private function usrLoginTimeUpdate()
    {
        // TODO 用户登录时间更新
        $filename = date('YmdHi', strtotime('-1 minutes'));//前一分钟的文件名称
        $path = BASEDIR . '/user-logtime/' . $filename;
        if (!is_file($path) || !is_readable($path)) {
            exit('目标文件不存在或者不可写');
        }

        //初始化变量
        $confArr = array();
        $data = array();
        $spl = '';

        //判断文件换行符：\r\n \n\r \n \r PHP_EOL 或者无换行符
        if (strstr(file_get_contents($path), "\n") != false || strstr(file_get_contents($path), "\r") != false) {
            if (strstr(file_get_contents($path), "\n\r") != false) {
                $spl = "\n\r";
                $data = explode($spl, file_get_contents($path));//按\n\r拆分文件
            } else if (strstr(file_get_contents($path), "\r\n") != false) {
                $spl = "\r\n";
                $data = explode($spl, file_get_contents($path));//按\r\n拆分文件
            } else {
                $spl = strstr(file_get_contents($path), "\n") == false ? "\r" : "\n";
                //按\r或\n拆分文件
                $data = explode($spl, file_get_contents($path));
            }
        } else if (strstr(file_get_contents($path), PHP_EOL) != false) {
            $spl = PHP_EOL;
            $data = explode($spl, file_get_contents($path));//按行拆分文件
        } else {
            $data = array(file_get_contents($path)); //无换行符
        }
        if (!$data) {
            exit('空文件');
        }

        //每行遍历，将|前部作为key，后部作为value赋值到$confArr中
        foreach ($data as $key => $content) {
            if (strcmp($spl, $content) == 0) {
                continue;
            }
            $item = explode('|', $content);
            $confArr[$item[0]] = $item[1];
        }

        //定义日志文件路径
        $logPath = BASEDIR . '/app/logs/cron_' . date('Y-m-d') . '.log';

        //数据库更新每行用户id的登录时间
        foreach ($confArr as $key => $item) {
            $result = Users::where('uid', $key)->update(array(
                'logined' => $item
            ));
            if (strcmp($result, "1") != 0) {
                $this->logResult("UID：" . $key . " 登录时间更新：" . $item . "更新失败" . "\n", $logPath);
            }
        }
    }


    /**
     * 充值送礼活动发生超时等问题的容错处理
     */
    private function activityErrHandler()
    {

        $filename = BASEDIR . '/app/logs/firstcharge_error_' . date('Y-m-d') . '.log';

        if (!file_exists($filename)) {
            return;
        }

        //初始化变量
        $spl = '';

        //判断文件换行符：\r\n \n\r \n \r PHP_EOL 或者无换行符
        if (strstr(file_get_contents($filename), "\n") != false || strstr(file_get_contents($filename), "\r") != false) {
            if (strstr(file_get_contents($filename), "\n\r") != false) {
                $spl = "\n\r";
                $data = explode($spl, file_get_contents($filename));//按\n\r拆分文件
            } else if (strstr(file_get_contents($filename), "\r\n") != false) {
                $spl = "\r\n";
                $data = explode($spl, file_get_contents($filename));//按\r\n拆分文件
            } else {
                $spl = strstr(file_get_contents($filename), "\n") == false ? "\r" : "\n";
                //按\r或\n拆分文件
                $data = explode($spl, file_get_contents($filename));
            }
        } else if (strstr(file_get_contents($filename), PHP_EOL) != false) {
            $spl = PHP_EOL;
            $data = explode($spl, file_get_contents($filename));//按行拆分文件

        } else {
            $data = array(file_get_contents($filename)); //无换行符
        }

        if (!$data) {
            exit('空文件');
        }

        $data = array_filter($data); //仅仅过滤空行和单行仅为0的记录
        if (empty($data))
            return;
        //file_put_contents($filename,'');

        foreach ($data as $activityPostData) {
            //实现cure通讯报文
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->make('config')['VFPHP_HOST_NAME'] . $this->make('config')['ACTIVITY_URL']);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, trim($activityPostData) . '&vsign=' . $this->make('config')['VFPHP_SIGN']);//$activityPostData已经是k1=v2&k2=v2的字符串
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_exec($ch);
            //$res= curl_exec($ch);
            curl_close($ch);
        }


    }
}
