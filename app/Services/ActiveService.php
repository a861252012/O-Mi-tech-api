<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;

/**
 * Created by PhpStorm.
 * User: nicholas
 * Date: 2018/3/8
 * Time: 15:22
 */
class ActiveService
{
    /**
     * 活动调用方法
     */
    public function doHuodong($money, $uid, $tradeno)
    {
        //活动接口url
        $activityUrl = $this->container->config['config.VFPHP_HOST_NAME'] . $this->container->config['config.ACTIVITY_URL'];
        //获取下token值
        $token = $this->generateUidToken($uid);
        //活动名称
        $activityName = $this->container->config['config.ACTIVITY_NAME'];
        //echo $token;exit;
        //发送的数据
        $activityPostData = array(
            'ctype' => $activityName, //活动类型
            'money' => $money, //充值的金额
            'uid' => $uid, //用户id
            'token' => $token, //口令牌
            'order_num' => $tradeno, //定单号
        );

        $postData = http_build_query($activityPostData);
        $send_result = $this->sendCurlRequest($activityUrl, $postData);
        $activityResult = $send_result['data'];
        $curl_errno = $send_result['errno'];

        //记录下活动日志
        $hdlogPath = BASEDIR . '/app/logs/firstcharge_' . date('Y-m-d') . '.log';
        $chargeHuodong = "充值活动结果" . $tradeno . "\n";
        $chargeHuodong .= $activityResult;
        try {
            $activityResult = json_decode($activityResult, true);
            if ($curl_errno == 0 && is_array($activityResult)) {
                $this->logResult($chargeHuodong . "\n", $hdlogPath);
            } else {
                $hdlogPath2 = BASEDIR . '/app/logs/firstcharge_error_' . date('Y-m-d') . '.log';
                file_put_contents($hdlogPath2, $postData . PHP_EOL, FILE_APPEND);
            }
        } catch (\Exception $e) {
            $hdlogPath2 = BASEDIR . '/app/logs/firstcharge_error_' . date('Y-m-d') . '.log';
            unset($activityPostData['token']);
            file_put_contents($hdlogPath2, $postData . PHP_EOL, FILE_APPEND);
        }

    }
}