<?php

namespace App\Http\Controllers;

use App\Models\ChargeList;
use App\Models\GiftActivity;
use App\Models\PayConfig;
use App\Models\PayOptions;
use App\Models\Recharge;
use App\Models\RechargeConf;
use App\Models\RechargeWhiteList;
use App\Models\Users;
use DB;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class ChargeController extends Controller
{
    /**
     * dm对象
     */
    private $_gm;

    /**
     * 根路径
     */
    private $_appRoot;
    /**
     * @var DataModel
     */
    private $_dmodel;

    private $codeMsg = array(
        -1 => '未知',
        0 => '已接受',
        1 => '处理中',
        2 => '处理成功',
        3 => '处理失败'
    );

    /**
     * 默认跳转页面
     **/
    public function index($action)
    {
        // 充钱成功后 检测用户的贵族状态
//        if ($this->userInfo['origin']>=50&&$this->userInfo['origin']<=59){
//            /** XO用户跳回XO */
//            $rs=$this->make('roomService');
//            header('Location:'.$rs->getXOPayUrl());
//        }
//        if($this->userInfo['origin']>=60&&$this->userInfo['origin']<=69){
//            $rs=$this->make('roomService');
//            $backurl = json_decode($rs->getPlatPayUrl($this->userInfo['origin']),true);
//            header('Location:'.$rs->getPlatHost($this->userInfo['origin']).$backurl['pay']);
//        }
        $origin = $this->userInfo['origin'];
        if ($origin >= 50) {
            $rs = $this->make('roomService');
            header('Location:' . $rs->getPlatUrl($origin)['pay']);
        }
        if ($action != 'notice2' && $action != 'notice' && $action != 'moniCharge' && $action != 'moniHandler' && $action != 'callFailOrder' && $action != 'checkKeepVip') {
            if (Auth::guest()) {
                return new RedirectResponse('/?handle=reg');
//                return $this->redirect($this->generateUrl('video_project_index') . '?handle=reg', 301);
            }
        }

        $action = $action . 'Action';
        if (!method_exists($this, $action)) {
            return new Response('The method is not exists!', 500);
        } else {
            return $this->$action();
        }
    }

    /**
     * 充值页面
     * 于20160817重构多充值方式开发需求(旧版已注释)
     * @author dc
     * @version 20160817
     * @return \Core\Response
     */
    public function orderAction()
    {
        $uid = Auth::id();
        $origin = $this->request()->get('origin') ?: 12;
        $user = $this->make('userServer')->getUserByUid($uid);

        $var['user'] = $user;

        $open = $this->make("redis")->hget("hconf", "open_pay") ?: 0;
        if ($open) {
            $jwt = $this->make('JWTAuth');
            $jwt->getTokenFromRequest($this->request());
            $token = $jwt->token;
            $var['options'] = PayOptions::with('channels')->where('device', 'PC')->get();
            $var['payConfig'] = PayConfig::where('open', 1)->get(['id', 'cid', 'bus', 'channel']);
            $var['origin'] = $origin;
            $jwt && $var['jwt'] = $token;
            //右边广告图
            $var['ad'] = '';
            $ad = $this->make('redis')->hget('img_cache', 3);// 获取右边的广告栏的数据
            if ($ad) {
                $a = json_decode($ad, true);
                $var['ad'] = $a[0];
            }
            return $this->render('Charge/order2', $var);
        }

        // 后台配置的充值限制条件群组信息
        $rechargeGroup = $this->make('redis')->get('recharge_group');
        if (!$rechargeGroup) {
            $group = RechargeConf::where('dml_flag', '!=', 3)->get()->toArray();
            // 格式化数组格式 array('[id]'=>array())
            $rechargeGroup = array();
            foreach ($group as $value) {
                $rechargeGroup[$value['auto_id']] = $value;
            }
            $this->make('redis')->set('recharge_group', json_encode($rechargeGroup));
        } else {
            //还原数组
            $rechargeGroup = json_decode($rechargeGroup, true);
        }


        //充值方法,会根据这个数来限制
        $RechargeTypes = 0;//默认没渠道

        // 1 验证白名单 和 黑名单 都在同一张表 type 0是白名单 1是黑名单
        $whiteList = RechargeWhiteList::where('uid', $uid)->where('dml_flag', '!=', 3)->first();

        // 如果在名单里面，就判断是白名单 还是 黑名单
        if ($whiteList) {

            if ($whiteList->type == 0) {
                //白名单根据后台白单配置
                $RechargeTypes = $rechargeGroup[1]['isopen'] < 2 ? intval($rechargeGroup[1]['isopen']) : $rechargeGroup[1]['recharge_type'];

            } else if ($whiteList->type == 1) {

                // 黑名单根据后台黑名单配置
                $RechargeTypes = $rechargeGroup[2]['isopen'] < 2 ? intval($rechargeGroup[2]['isopen']) : $rechargeGroup[2]['recharge_type'];
            }


            // 2 当用户没在名单中时就进行下面的验证
        } else {

            //因为要加上统计后台充值数据，所以改从video_recharge充值记录进行统计
            $paymoney = Recharge::where('uid', $uid)
                ->where('pay_status', 2)//统计已成功记录
                ->where(function ($query) {
                    $query->orWhere('pay_type', 1)->orWhere('pay_type', 4);//4=只统计银行充值和后台充值记录
                })->sum('paymoney');

            //将钻石转化成实际充值金额，目前算法 /10;

            $paymoney = $paymoney ?: 0;

            //循环匹配充值组
            foreach ($rechargeGroup as $rid => $val) {
                if ($rid < 3) continue; //黑名单、白名单不进入循环

                if ($val['recharge_min'] <= $paymoney && $paymoney <= $val['recharge_max']) {

                    //判断充值时间
                    $created = strtotime($user['created']);
                    $regTimeMax = time() - $val['reg_time_max'] * 86400;
                    $regTimeMin = time() - $val['reg_time_min'] * 86400;

                    //判断充值时间,充值金额权限
                    if ($created <= $regTimeMin && $created >= $regTimeMax) {
                        $RechargeTypes = $val['isopen'] < 2 ? intval($val['isopen']) : $val['recharge_type'];
                    }
                }

            }
        }


        // 没有充值的权限
        if ($RechargeTypes === 0) {
            return $this->render('error', array('title' => '尊敬的用户，您好，恭喜您成为今日幸运之星，请点击在线客服领取钻石，感谢您的支持与理解！', 'msg' => ''));

            // 联系客服
        } else if ($RechargeTypes === 1) {
            return $this->render('error', array('title' => '<a id="live800iconlink" href="javascript:void(0)" onclick="return openChat(this) " lim_company="410769">需要充值请<u style="color:blue;">联系客服</u>！！！</a>', 'msg' => ''));

        } else {
            //显示充值渠道
            $RechargeTypes = @unserialize($RechargeTypes);

            $var['active'] = GiftActivity::where('type', 2)->get();

            //右边广告图
            $var['ad'] = '';
            $ad = $this->make('redis')->hget('img_cache', 3);// 获取右边的广告栏的数据
            if ($ad) {
                $a = json_decode($ad, true);
                $var['ad'] = $a[0];
            }

            //充值方式数组
            $var['recharge_type'] = $RechargeTypes ?: array();

            //检查用户登录权限
            //$var['user_login_asset'] = $this->make('userServer')->checkUserLoginAsset(Auth::id());
            $var['user_login_asset'] = true;

            //最小充值限制
            $var['user_money_min'] = $this->container->config['config.user_points_min'] / 10;

            //充值金额删选数组
            $var['recharge_money'] = $this->make('redis')->get('recharge_money') ?: json_encode(array());
            return $this->render('Charge/order', $var);
        }


    }

    /**
     * 删除订单操作
     */
    public function delAction()
    {
        $uid = $this->request()->getSession()->get(self::SEVER_SESS_ID);
        //判断下是不是删除操作
        if ($this->request()->getMethod() == 'POST') {
            $lid = $this->request()->get('lid');
            $chargeObj = ChargeList::where('id', $lid)->where('uid', $uid)->first();

            //判断下这条记录是不是这个用户的
            if (!$chargeObj) {
                $retData = array(
                    'info' => '这条记录不是你的!',
                    'ret' => false
                );
                return new JsonResponse($retData);
            }
            //执行删除操作
            $chargeObj->del = 1;
            $chargeObj->save();

            $retData = array(
                'info' => '删除成功！',
                'ret' => true
            );
            return new JsonResponse($retData);
        }
        //不是正常途径
        $retData = array(
            'info' => '非法操作',
            'ret' => false
        );
        return new JsonResponse($retData);
    }

    public function notice2Action()
    {
        //获取下数据
        $postdata = $_POST['postdata'];
        //拿到通知的数据
        if (empty($postdata) || strpos($postdata, '.') === false) {
            return JsonResponse::create(['status' => -1, 'msg' => 'invalid input!']);
        }
        list($jsonData, $sign) = explode('.',$postdata);
        $jsonData = base64_decode($jsonData);
        $data = json_decode($jsonData,true);
        if (json_last_error() > 0) {
            return JsonResponse::create(['status' => -1, 'msg' => 'JSON format error']);
        }
        //记录下日志
        $logPath = BASEDIR . '/app/logs/charge2_' . date('Y-m-d') . '.log';
        $loginfo = date('Y-m-d H:i:s') . "\n传输的数据记录: \n" . $jsonData . "\n";

        $tradeno = $data['order_id'];//拿出1个账单号
        //验证签名
        $key = $this->container->config['config.BACK_PAY_SIGN_KEY'];
        if (hash_hmac('sha256', $jsonData, $key) !== $sign) {
            $signError = "订单号：" . $tradeno . "\n签名没有通过！\n";
            $loginfo .= $signError;
            $this->logResult($loginfo, $logPath);
            return new JsonResponse(array('status' => -1, 'msg' => $signError));
        }
        $paytradeno = $data['pay_id'];
        $money = $data['money'];
        $chargeResult = $data['result'];
        $channel = $data['channel'];
        $complateTime = $data['complateTime'];

        return $this->orderHandler($tradeno, $paytradeno, $loginfo, $logPath, $money, $chargeResult, $channel, $complateTime);


    }

    /**
     * @param $tradeno v项目订单号
     * @param $paytradeno 财务订单号
     * @param $loginfo 日志
     * @param $logPath 日志的路径
     * @param $money 金钱数目
     * @param $chargeResult 结果
     * @return JsonResponse
     * 充提返回值：-1未知 0已接受 1处理中 2处理成功
     * @Author Orino
     */
    private function orderHandler($tradeno, $paytradeno, $loginfo, $logPath, $money, $chargeResult, $channel = '', $complateTime = '')
    {
        //开启事务
        try {
            DB::beginTransaction();
            $sql = 'SELECT t.* FROM `video_recharge` t WHERE t.pay_type in(1,50) AND t.pay_status < 2 AND order_id = "' . $tradeno . '" LIMIT 1 FOR UPDATE';
            //强制查询主库
            //$stmt = DB::select('/*' . MYSQLND_MS_MASTER_SWITCH . '*/' . $sql);
            $stmt = DB::select($sql);

            if (empty($stmt)) {
                $dealOrCannotFind = "订单号：" . $tradeno . "\n数据已处理完毕，请查看'充值记录！'\n";
                $loginfo .= $dealOrCannotFind;
                $this->logResult($loginfo, $logPath);
                return new JsonResponse(array('status' => 0, 'msg' => $dealOrCannotFind));
            }
            $stmt = (array)$stmt[0];
            //第一步，写日志
            $loginfo .= "订单号：" . $tradeno . " 收到，并且准备更新：\n";
            $points = $stmt['points'];
            DB::table('video_recharge')->where('id', $stmt['id'])->update(array(
                'paymoney' => $money,
                'pay_status' => $chargeResult,
                //'ttime' => date('Y-m-d H:i:s'),
                'ttime' => $complateTime,
                //'channel' => $channelId,
                'pay_id' => $paytradeno
            ));

            $chargeStatus = false;//成功状态标记位
            if ($chargeResult == 2 && !empty($paytradeno)) {

                $rs = DB::table('video_user')->where('uid', $stmt['uid'])->increment('points', $points);
                if ($rs) {
                    $chargeStatus = true;//成功状态为1
                } else {
                    $loginfo .= '充值成功，增加的钱数:' . $points . ' 失败';
                }
            }

            DB::commit();

            //刷新redis钻石
            if ($chargeStatus) {
                $userObj = DB::table('video_user')->where('uid', $stmt['uid'])->first();//Users::find($stmt['uid']);
                $loginfo .= '增加的钱数: paymoney '.$money.' points:' . $points . ' 最终的钱数:' . $userObj->points;
                $this->make('redis')->hincrby('huser_info:' . $stmt['uid'], 'points', $points);
            }

            // 充钱成功后 检测用户的贵族状态
            $uinfo = Users::find($stmt['uid']);
            $this->checkUserVipStatus($uinfo);

        } catch (\Exception $e) {
            $this->logResult("订单号：" . $tradeno . " 事务结果：" . $e->getMessage() . "\n", $logPath);
            DB::rollback();
            return new JsonResponse(array('status' => 1, 'msg' => '程序内部异常'));
        }

        //首次充值时间
        if ($chargeStatus) {
            $uid = $stmt['uid'];
            Users::where('uid', $uid)->whereNull('first_charge_time')->update(array('first_charge_time' => date('Y-m-d H:i:s')));
            $this->make('redis')->hset('huser_info:' . $uid, 'first_charge_time', date('Y-m-d H:i:s', time()));
        }

        //第二步，更新数据
        $loginfo .= "订单号：" . $tradeno . " 数据处理成功！\n";
        //成功才触发充值自动送礼
        if ($chargeStatus && $this->container->config['config.ACTIVITY_OPEN']) {
            //活动的调用写到对应的方法内
            $this->doHuodong($money, $stmt['uid'], $tradeno);
        }

        //封装下结果给充提
        $rtn2back = $this->back2Charge($chargeResult, $tradeno, $paytradeno);
        $loginfo .= "返回给充提中心的结果：\n" . $rtn2back . "\n";
        $this->logResult($loginfo, $logPath);
        return new JsonResponse(array('status' => 0, 'msg' => $rtn2back));

    }

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

    protected function sendCurlRequest($url, $data)
    {
        $ch = curl_init(); //初始化CURL句柄
        curl_setopt($ch, CURLOPT_URL, $this->container->config['config.PAY_CALL_URL']); //设置请求的URL
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        $output = curl_exec($ch);
        $errstr = curl_error($ch);
        $curl_errno = curl_errno($ch);
        curl_close($ch);
        return ['data' => $output, 'errstr' => $errstr, 'errno' => $curl_errno];
    }

    /**
     * 返回给充提的结果
     */
    public function back2Charge($chargeResult, $tradeno, $paytradeno)
    {
        //数据统计好后，根据状态来返回结果
        if ($chargeResult == 0) {
            $chargeResult2 = ' 已接受！';
        } elseif ($chargeResult == 1) {
            $chargeResult2 = ' 处理中！';
        } elseif ($chargeResult == 2 && !empty($paytradeno)) {
            $chargeResult2 = ' 处理成功！';
        } elseif ($chargeResult == 3) {
            $chargeResult2 = ' 处理失败！';
        }
        //  return '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">订单号：' . $tradeno . $chargeResult2 . ' \n';
        return $tradeno . $chargeResult2 . $paytradeno;
    }

    public function pay2Action()
    {
        $option_id = $this->request()->get('oid');
        $cid = $this->request()->get('cid');
        $origin = $this->request()->get('origin') ?: 12;
        $remark = $this->request()->get('remark') ?: '';
        $option = PayOptions::find($option_id);
        $errors = [];
        if (!$option || !$option->exists) {
            $errors[] = '金额错误，请返回重试';
            return $this->render('Charge/pay', compact('errors'));
        }
        $amount = number_format(intval($option['rmb']), 2, '.', '');
        if (!$amount || $amount < 1) {
            $errors[] = '请输入正确的金额!';
            return $this->render('Charge/pay', compact('errors'));
        }
        //获取下渠道
        $channel = PayConfig::where('cid', $cid)->where('open', 1)->first();
        //判断下渠道存不存在
        if (!$channel || !$channel->exists) {
            $errors[] = '请选择充值渠道!';
            return $this->render('Charge/pay', compact('errors'));
        }
        $rtn = [];
        if (empty($errors)) {
            try {
                $remoteUrl = $this->container->config['config.BACK_PAY_CALL_URL'];
                $notice = $this->container->config['config.BACK_PAY_NOTICE_URL'];
                $key = $this->container->config['config.BACK_PAY_SIGN_KEY'];
                $time = time();
                $timeHex = dechex($time);
                $orderId = $this->generateOrderId();
                $postdata = json_encode([
                    'order_id' => $orderId,
                    'money' => $amount,
                    'channel' => $cid,
                    'notice' => $notice,
                    'isMobile' => $origin >= 20 && $origin <= 39,
                    'origin' => $origin,
                    'plat_code' => $this->container->config['config.BACK_PLAT_CODE'],
                    'uid' => Auth::id(),
                    'user_name' => $this->userInfo['username'],
                    'remark' =>$remark,
                    't' => $timeHex
                ]);
                $sign = hash_hmac('sha256', $postdata, $key);
                //跳转页面需要的信息，设置session数据
                $rtn = [
                    'postdata' => collect([base64_encode($postdata), $sign])->implode('.'),
                    'remoteUrl' => $remoteUrl,
                ];
                $record = Recharge::create([
                    'uid' => Auth::id(),
                    'created' => date('Y-m-d H:i:s', $time),
                    'pay_status' => 0,// 刚开始受理
                    'pay_type' => 1, // 银行充值
                    'del' => 0,
                    'paymoney' => $amount,
                    'points' => $option->points,
                    'order_id' => $orderId,
                    'postdata' => $postdata,
                    'nickname' => $this->userInfo['nickname'],
                    'channel' => $cid,
                    'origin' => $origin
                ]);
            } catch (\Exception $e) {
                $rtn['errors'] = ['创建订单失败'];
                return $this->render('Charge/pay', $rtn);
            }
        } else {
            $rtn['errors'] = $errors;
        }
        return $this->render('Charge/pay', $rtn);
    }

    public function generateOrderId()
    {
        return date('ymdHis') . mt_rand(10, 99) . sprintf('%08s', strrev(Auth::id())) . '';
    }

    /**
     * 支付处理
     * 要组的格式
     * {
     *   "serviceCode": "",
     *   "version": "",
     *   "serviceType": "",
     *   "signType": "",
     *   "sign": "",
     *   "sysPlatCode": "",
     *   "sentTime": "",
     *   "expTime": "",
     *   "charset": "",
     *   "sMessageNo": "",
     *   "Datas": [
     *     {
     *       "dataNo": " FCDATA2014021014555633236522",
     *       "orderId": " H544514545544111",
     *       "accountChangeTime": "2014-08-09 09:54:02"
     *     }
     *   ]
     * }
     *
     */
    public function payAction()
    {
        $amount = isset($_POST['price']) ? number_format(intval($_POST['price']), 2, '.', '') : 0;
        $origin = $this->request()->get('origin') ?: 12;

        if (!$amount || $amount < 1) {
            $msg = '请输入正确的金额!';
            return new JsonResponse(array('status' => 1, 'msg' => $msg));
        }
        $fee = 0;
        if($giftactive = GiftActivity::query()->where('moneymin',intval($amount))->first())    $fee = $giftactive->fee;

        //获取下渠道
        $channel = $_POST['vipLevel'];
        $mode_type = $_POST['mode_type'];
        //判断下渠道存不存在
        if (empty($channel)) {
            $msg = '请选择充值渠道!';
            return new JsonResponse(array('status' => 1, 'msg' => $msg));
        }


        //渠道转化为数字入库
        $serviceCode = $this->container->config['config.PAY_SERVICE_CODE'];
        $version = $this->container->config['config.PAY_VERSION'];
        $serviceType = $this->container->config['config.PAY_SERVICE_TYPE'];
        $signType = $this->container->config['config.PAY_SIGNTYPE'];
        $sysPlatCode = $this->container->config['config.PAY_SYSPLATCODE'];
        $charset = $this->container->config['config.PAY_CHARSET'];
        $priviteKey = $this->container->config['config.PAY_PRIVATEKEY'];
        $remoteUrl = $this->container->config['config.PAY_CALL_URL'];

        //$randValue = mt_rand(1000, 9999);
        $sentTime = date('Y-m-d H:i:s');
        $expTime = '';
        $orderDate = date('YmdHis');

        $date_array = explode(" ",microtime());
        $milliseconds = $date_array[1].($date_array[0]*10000);
        $milliseconds = explode ( ".", $milliseconds );
        $randValue = substr($milliseconds[0],-4);
        //唯一订单号
        $sMessageNo = $serviceCode . date('YmdHis') . $randValue;

        //通知地址
        //获取下当前域名
//        $noticeUrl = $GLOBALS['CUR_URL'] . $this->container->config['config.PAY_NOTICE_URL'];
        //2017.2.23 改为config固定地址
        $noticeUrl = $this->container->config['config.PAY_NOTICE_URL'];
        $returnUrl = $GLOBALS['CUR_URL'] . $this->container->config['config.PAY_REBACK_URL'];
        $username = $this->userInfo['username'];
        $isMobile = "false";
        $Datas = array(
            array(
                'dataNo' => 'FCDATA' . $orderDate . $randValue,
                'amount' => $amount,
                'noticeUrl' => $noticeUrl,
                'returnUrl' => $returnUrl,
                'remark' => $username,
                'channel' => "",
                'vipLevel' => $channel,
                'bankCode' => "",
                'lan' => "",
                'currency' => "",
                'isMobile' => $isMobile,
            )
        );
        $sign_str = "";
        foreach($Datas[0] as $v){
            $sign_str .= $v;
        }
        //生成签名 签名是由非signType，sign的字符串+ Datas的第一个成员的所有属性，再加私密钥拼接而成
        $sign = MD5($serviceCode . $version . $serviceType . $sysPlatCode . $sentTime . $expTime . $charset . $sMessageNo.$sign_str.
            $priviteKey);
        $postdataArr = array(
            'serviceCode' => $serviceCode,
            'version' => $version,
            'serviceType' => $serviceType,
            'signType' => $signType,
            'sign' => $sign,
            'sysPlatCode' => $sysPlatCode,
            'sentTime' => $sentTime,
            'expTime' => $expTime,
            'charset' => $charset,
            'sMessageNo' => $sMessageNo,
            'Datas' => $Datas
        );
        $postdata = json_encode($postdataArr);

        //记录下数据库
        $uid = Auth::id();
        Recharge::create(
            array(
                'uid' => $uid,
                'created' => date('Y-m-d H:i:s'),
                'pay_status' => 0,// 刚开始受理
                'pay_type' => 1, // 银行充值
                'del' => 0,
                'paymoney' => $amount,
                'points' => ceil($amount * 10)+$fee,
                'order_id' => $sMessageNo,
                'postdata' => $postdata,
                'nickname' => $this->userInfo['nickname'],
                'channel' => $channel,
                'mode_type' => $mode_type,
                'origin' => $origin
            )
        );

        $rtn = array(
            'orderId' => $sMessageNo,
            'gotourl' => ' /charge/translate'
        );
        //跳转页面需要的信息，设置session数据
        $this->request()->getSession()->set(
            'orderInfo',
            array(
                'postdata' => $postdata,
                'remoteUrl' => $remoteUrl,
            )
        );
        return new JsonResponse(array('status' => 0, 'msg' => $rtn));
    }

    /**
     * @return Response
     * @author Orino
     */
    public function translateAction()
    {
        $rtn = $this->request()->getSession()->get('orderInfo');
        if (!!$rtn) {
            //销毁session，保证页面的提交，每次是新的订单
            $this->request()->getSession()->set('orderInfo', null);
        } else {
            $rtn = array();
//            return new Response("101错误订单不允许重复提交，请重新生成订单");
        }

//        $postdata = $rtn['postdata'];
//        $remoteUrl = $rtn['remoteUrl'];
//        $ch = curl_init();
//        curl_setopt($ch, CURLOPT_URL, $remoteUrl); //设置请求的URL
//        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
//        curl_setopt($ch,CURLOPT_HEADER, false);
//        curl_setopt($ch, CURLOPT_POST, 1);
//        curl_setopt($ch, CURLOPT_POSTFIELDS, ['postDatas'=>$postdata]);
//        $output=curl_exec($ch);
//        $errstr = curl_error($ch);
//        curl_close($ch);
//
//
//        //验证下签名
//        if ($errstr) {
//            $loginfo = date('Y-m-d H:i:s') . "\n 充提第一次请求 网络错误: \n" . $errstr . "\n";
//        }else{
//            $loginfo = date('Y-m-d H:i:s') . "\n 充提第一次请求: \n" . $postdata . "\n";
//        }
//        $output = preg_replace(
//            [
//                '/..\/css\//',
//                '/..\/js\//','/..\/img\//',
//                '/..\/service\/d2p/',
//                '/..\/service\/qrImg/',
//            ],
//            [
//                $remoteUrl.'/../../css/',
//                $remoteUrl.'/../../js/',
//                $remoteUrl.'/../../img/',
//                $remoteUrl.'/../../service/d2p',
//                //$remoteUrl.'/../../service/qrImg',
//                '/ajaxProxy?uri='.urlencode($remoteUrl.'/../../service/qrImg'),
//            ],
//            $output);
//
//        $logPath = BASEDIR . '/app/logs/charge_' . date('Y-m-d') . '.log';
//        $this->logResult($loginfo, $logPath);
//        return new Response($output);

        return $this->render('Charge/pay', $rtn);
    }

    /**
     * 检查订单号唯一性
     * @param $sMessageNo
     * @Author Orino
     */
    public function checkOrderNu($serviceCode, $sMessageNo)
    {
        $sql = 'SELECT t.* FROM `video_charge_list` t WHERE  AND tradeno = "' . $sMessageNo . '"';
        //强制查询主库
        $ret = DB::select('/*' . MYSQLND_MS_MASTER_SWITCH . '*/' . $sql);
        if (!$ret) {
            return $sMessageNo;
        } else {
            $sMessageNo = $serviceCode . date('YmdHis') . mt_rand(1000, 9999);
            return $this->checkOrderNu($serviceCode, $sMessageNo);
        }
    }

    /**
     * 重新去支付
     */
    public function repayAction()
    {
        exit('功能废弃');//因为这样容易产生一个v项目订单号对应多个财务订单号
        $id = is_numeric($_GET['id']) ? $_GET['id'] : 0;
        if (!$id) {
            $msg = '没有这一条记录！';
            Header("Location:/charge/order?msg=" . $msg);
            exit;
        }

        $remoteUrl = $this->container->config['config.PAY_CALL_URL'];

        //如果是重新发送，拿一下postdata的值
        $gm = $this->get('doctrine')->getManager();
        $uid = $this->get('session')->get(self::SEVER_SESS_ID);
        $chargeObj = $gm->getRepository('Video\ProjectBundle\Entity\VideoChargeList')->findOneBy(
            array('uid' => $uid, 'del' => 0, 'status' => 0, 'id' => $id)
        );
        if (!$chargeObj) {
            $msg = '没有这一条记录！';
            Header("Location:/charge/order?msg=" . $msg);
            exit;
        }
        $postdata = $chargeObj->getPostdata();
        $rtn = array(
            'postdata' => $postdata,
            'remoteUrl' => $remoteUrl
        );

        return $this->render('VideoProjectBundle:Charge:pay.html.twig', $rtn);
    }


//    /**
//     * 写一个模拟充提handler处理
//     */
//    public function moniHandlerAction()
//    {
//
//        $payOrderJson=array(
//            'orderId'=>'FC0028201507112130551649',
//            'payOrderId'=>'FC0028201507112130551649',
//            'amount'=>'500',
//            'result'=>'2'
//        );
//
//        $this->comHandler($payOrderJson);
//    }

    /**
     * 回调地址(暂时不会有)
     */
    public function rebackAction()
    {


    }

    /**
     * 通知地址
     */
    public function noticeAction()
    {
        //获取下数据
        $postResult = file_get_contents("php://input");
        //拿到通知的数据
        if (!$postResult) {
            return new JsonResponse(array('status' => 1, 'msg' => 'no data input!'));
        }

        $jsondatas = json_decode($postResult, true);
        $len = $jsondatas['Datas'] ? count($jsondatas['Datas']) : 0;
        if (json_last_error() > 0 || $len == 0) {
            return new JsonResponse(array('status' => 1, 'msg' => 'json string ie error!'));
        }
        //记录下日志
        $logPath = BASEDIR . '/app/logs/charge_' . date('Y-m-d') . '.log';
        $loginfo = date('Y-m-d H:i:s') . "\n传输的数据记录: \n" . $postResult . "\n";
        $tradeno = $jsondatas['Datas'][0]['orderId'];//拿出1个账单号
        //验证下签名
        if (!$this->checkSign($jsondatas)) {
            $signError = "订单号：" . $tradeno . "\n签名没有通过！\n";
            $loginfo .= $signError;
            $this->logResult($loginfo, $logPath);
            return new JsonResponse(array('status' => 1, 'msg' => $signError));
        }
        if ($len == 1) {
            $paytradeno = $jsondatas['Datas'][0]['payOrderId'];
            $money = $jsondatas['Datas'][0]['amount'];
            $chargeResult = $jsondatas['Datas'][0]['result'];
            $channel = $jsondatas['Datas'][0]['channel'];
            $complateTime = $jsondatas['Datas'][0]['complateTime'];
        } else {
            for ($i = 0; $i < $len; $i++) {
                $paytradeno = $jsondatas['Datas'][$i]['payOrderId'];
                $money = $jsondatas['Datas'][$i]['amount'];
                $chargeResult = $jsondatas['Datas'][$i]['result'];
                $channel = $jsondatas['Datas'][$i]['channel'];
                $complateTime = $jsondatas['Datas'][$i]['complateTime'];
                //存在同一个v项目订单号对应2个以上的财务财务订单号
                if ($chargeResult == 2 && !empty($paytradeno)) {
                    break;
                }
            }
        }

        //test code
//        $tradeno='0000000000000000000000001';
//        $paytradeno='83170079';
//        $money=10;
//        $chargeResult=2;
//        $logPath = BASEDIR . '/app/logs/charge_' . date('Y-m-d') . '.log';
//        $loginfo = date('Y-m-d H:i:s') . "\n传输的数据记录: \n" . "\n";
//        $channel = '';
//        $complateTime = ''.date('Y-m-d H:i:s');
        return $this->orderHandler($tradeno, $paytradeno, $loginfo, $logPath, $money, $chargeResult, $channel,$complateTime);
    }

    /**
     * 验证下签名是否通过
     */
    private function checkSign($postResult)
    {
        //接口名称
        $serviceCode = $postResult['serviceCode'];
        //版本
        $version = $postResult['version'];
        //接口类型
        $serviceType = $postResult['serviceType'];
        //平台代码
        $sysPlatCode = $postResult['sysPlatCode'];
        //发送时间
        $sentTime = $postResult['sentTime'];

        $expTime = $postResult['expTime'];
        //编码格式
        $charset = $postResult['charset'];
        //messageNo
        $sMessageNo = $postResult['sMessageNo'];
        //数据包
        $Datas = $postResult['Datas'];
        //私钥
        $priviteKey = $this->container->config['config.PAY_PRIVATEKEY'];
        //传过来的sign
        $oldSign = $postResult['sign'];
        //生成签名
        $newDatas1 = $Datas;
        $newDatas = $newDatas1[0];
        //生成签名 签名是由非signType，sign的字符串+ Datas的第一个成员的所有属性，再加私密钥拼接而成
        $str = $serviceCode . $version . $serviceType . $sysPlatCode . $sentTime . $expTime . $charset . $sMessageNo;
        foreach ($newDatas as $value) {
            $str .= $value;
        }
        $str .= $priviteKey;
        if (MD5($str) == $oldSign) {
            return true;
        }
        return false;
    }

    /**
     * 更新后台用户看到的信息
     */
    public function updateReChargeInfo($chargeStatus, $points, $username, $tradeno, $paytradeno, $uid)
    {
        //成功支付订单，状态为1，其他为2
        $chargeStatus = $chargeStatus ? 1 : 2;

        $flag = Recharge::where('order_id', $tradeno)->update(array(
            'pay_status' => $chargeStatus,
            'pay_id' => $paytradeno,
        ));
        if ($flag == false) {
            $paymoney = round($points/10,1);
            Recharge::create(array(
                'points' => $points,
                'paymoney' => $paymoney,
                'created' => date('Y-m-d H:i:s'),
                'nickname' => $username,
                'order_id' => $tradeno,
                'pay_status' => $chargeStatus,
                'pay_type' => 1,
                'pay_id' => $paytradeno,
                'uid' => $uid,
            ));
        }

    }

    /**
     * 写一个模拟充提中心的中转
     */
    public function moniChargeAction()
    {
        if (!getenv('STRESS_ENV')) {
            exit;
        }
        $priviteKey = $this->container->config['config.PAY_PRIVATEKEY'];
        $jsondatas = json_decode($_POST['postDatas'], true);
        echo "转化成充提中心给我的json格式\n";
        //取出postresult里面需要的数据
        $datas = array(
            array(
                'dataNo' => $jsondatas['Datas']['0']['dataNo'],
                'orderId' => $jsondatas['sMessageNo'],
                'payOrderId' => mt_rand(10000000, 99999999),
                'amount' => $jsondatas['Datas']['0']['amount'],
                'result' => 2,
                'remark' => '',
                'channel' => 'NP',
                'complateTime' => date('Y-m-d H:i:s'),
            )
        );
        $newDatas = $datas[0];
        //生成下签名
        $serviceCode = $this->container->config['config.PAY_SERVICE_CODE'];
        $version = $this->container->config['config.PAY_VERSION'];
        $serviceType =  $this->container->config['config.PAY_SERVICE_TYPE'];;
        $signType = $this->container->config['config.PAY_SIGNTYPE'];
        $sysPlatCode =  $this->container->config['config.PAY_SYSPLATCODE'];
        $charset = $this->container->config['config.PAY_CHARSET'];
        $sentTime = $jsondatas['sentTime'];
        $expTime = '';
        $sMessageNo = $jsondatas['sMessageNo'];
        //生成签名 签名是由非signType，sign的字符串+ Datas的第一个成员的所有属性，再加私密钥拼接而成
        $str = $serviceCode . $version . $serviceType . $sysPlatCode . $sentTime . $expTime . $charset .
            $sMessageNo;
        foreach ($newDatas as $value) {
            $str .= $value;
        }
        $sign = MD5($str.$priviteKey);
        $postdataArr = array(
            "serviceCode" => $serviceCode,
            'version' => $version,
            'serviceType' => $serviceType,
            'signType' => $signType,
            'sysPlatCode' => $sysPlatCode,
            'charset' => $charset,
            'sentTime' => $jsondatas['sentTime'],
            'expTime' => '',
            'sign' => $sign,
            'sMessageNo' => $sMessageNo,
            'Datas' => $datas
        );

        $payNoticeUrl = $this->container->config['config.VFPHP_HOST_NAME'] . $this->container->config['config.PAY_NOTICE_URL'];
//2017.2.23 改为配置文件绝对地址
//        $payNoticeUrl = $this->container->config['config.PAY_NOTICE_URL'];//todo
        $send_result = $this->sendCurlRequest($payNoticeUrl, $postdataArr);
        $server_output = $send_result['data'];
        return new Response($server_output);
    }

    /**
     * @return JsonResponse
     * @Author Orino
     */
    public function callFailOrderAction()
    {
        $payOrderJson = file_get_contents("php://input");
        $type = 2;
//        $this->_appRoot = $this->get('kernel')->getRootDir();
        $logPath = BASEDIR . '/app/logs/charge_' . date('Y-m-d') . '_' . $type . '.log';
        $this->logResult($payOrderJson, $logPath);
        if (!$payOrderJson) {
            return new JsonResponse(array('status' => 1, 'msg' => '传入的数据存在问题'));
        }
        $payOrderJson = json_decode($payOrderJson, true);
        if (!$this->verifyUidToken($payOrderJson['uid'], $payOrderJson['token'])) {
            return new JsonResponse(array('status' => 1, 'msg' => '非法操作！'));
        }
        return $this->comHandler($payOrderJson, $type);
    }

    /**
     * 公共的处理函数，log文件类型
     * “正常支付流程”是app/logs/charge_20150728.log,
     * ”已完成支付“调用已充值是app/logs/charge_20150728_3.log,
     * “后台调用支付”是app/logs/charge_20150728_2.log
     * @param $payOrderJson
     * @param int $type
     * @return JsonResponse
     * @Author Orino
     */
    private function comHandler($payOrderJson, $type = 1)
    {
        $tradeno = $payOrderJson['orderId'];
        $paytradeno = $payOrderJson['payOrderId'];
        $money = $payOrderJson['amount'];
        $logPath = BASEDIR . '/app/logs/charge_' . date('Y-m-d') . '_' . $type . '.log';
        $loginfo = json_encode($payOrderJson);
        $chargeResult = $payOrderJson['result'];
        $complateTime = $payOrderJson['complateTime'];
        $channel = '';
        return $this->orderHandler($tradeno, $paytradeno, $loginfo, $logPath, $money, $chargeResult, $channel, $complateTime);
    }

    public function checkKeepVipAction()
    {
        $msg = file_get_contents("php://input");
        $logPath = BASEDIR . '/app/logs/charge_' . date('Y-m-d') . '_checkKeepVip.log';
        $this->logResult($msg, $logPath);
        if (!$msg) {
            return new JsonResponse(array('status' => 1, 'msg' => '传入的数据存在问题'));
        }
        $msg = json_decode($msg, true);
        // 充钱成功后 检测用户的贵族状态
        $uinfo = Users::find($msg['uid']);
        $this->checkUserVipStatus($uinfo);
        return new JsonResponse(array('status' => 0, 'msg' => 'It is ok！'));
    }

    /**
     * 通过订单号查询
     * @return Response
     * @Author Orino
     */
    public function checkChargeAction()
    {
        $orderId = isset($_GET['orderId']) ? $_GET['orderId'] : '';
        if (!$orderId) {
            return new JsonResponse(array('status' => 1, 'msg' => '该订单号不存在！'));
        }
        $sql = 'SELECT * FROM `video_recharge` WHERE  order_id ="' . $orderId . '"';
        //强制查询主库
        $ret = DB::select($sql);
        //$ret = DB::select('/*' . MYSQLND_MS_MASTER_SWITCH . '*/' . $sql);
        $ret = (array)$ret[0];// stdClass 转数组
        if (!$ret) {
            return new JsonResponse(array('status' => 1, 'msg' => '该订单号不存在！'));
        }
        if ($ret['pay_status'] == 2) {
            return new JsonResponse(array('status' => 0, 'msg' => '该订单号已经成功支付,请返回会员中心的"充值记录"查看！'));
        }
        if ($ret['pay_status'] == 3) {
            return new JsonResponse(array('status' => 0, 'msg' => '该订单号支付已经失败,请返回会员中心的"充值记录"查看！'));
        }
        $serviceCode = $this->container->config['config.PAY_FIND_CODE'];//查询接口的名称FC0029
        $version = $this->container->config['config.PAY_VERSION'];
        $serviceType = $this->container->config['config.PAY_SERVICE_TYPE'];
        $signType = $this->container->config['config.PAY_SIGNTYPE'];
        $sysPlatCode = $this->container->config['config.PAY_SYSPLATCODE'];
        $charset = $this->container->config['config.PAY_CHARSET'];
        $priviteKey = $this->container->config['config.PAY_PRIVATEKEY'];
        //$randValue = mt_rand(1000, 9999);
        $sentTime = date('Y-m-d H:i:s');
        $expTime = '';

        $date_array = explode(" ", microtime());
        $milliseconds = $date_array[1] . ($date_array[0] * 10000);
        $milliseconds = explode(".", $milliseconds);
        $randValue = substr($milliseconds[0], -4);
        $sMessageNo = $serviceCode . date('YmdHis') . $randValue;//随意给的，只是让校验产生随机性质
        $Datas = array(
            array(
                "dataNo" => $orderId,
                "orderId" => $orderId,
                "payOrderId" => "",
                "type" => 1 //查询接口类型
            )
        );
        //生成签名 签名是由非signType，sign的字符串+ Datas的第一个成员的所有属性，再加私密钥拼接而成
        $sign = MD5(
            $serviceCode . $version . $serviceType . $sysPlatCode . $sentTime . $expTime . $charset . $sMessageNo .
            $Datas[0]['dataNo'] . $Datas[0]['orderId'] . $Datas[0]['payOrderId'] . $Datas[0]['type']
            . $priviteKey
        );
        $POST_Array = array(
            'serviceCode' => $serviceCode,
            'version' => $version,
            'serviceType' => $serviceType,
            'signType' => $signType,
            'sign' => $sign,
            'sysPlatCode' => $sysPlatCode,
            'sentTime' => $sentTime,
            'expTime' => $expTime,
            'charset' => $charset,
            'sMessageNo' => $sMessageNo,
            'Datas' => $Datas
        );
//        $this->_appRoot = $this->get('kernel')->getRootDir();
        $logPath = BASEDIR . '/app/logs/charge_' . date('Y-m-d') . '_3.log';

        $send_result = $this->sendCurlRequest($this->container->config['config.PAY_CALL_URL'], json_encode($POST_Array));
        $output = $send_result['data'];
        $errstr = $send_result['errstr'];

        $this->logResult($this->container->config['config.PAY_CALL_URL'] . PHP_EOL . 'output' . $output . PHP_EOL . 'error' . $errstr, $logPath);
        if (!empty($errstr)) {
            return new JsonResponse(array('status' => 1, 'msg' => '充提查询接口出问题：' . $errstr));
        }
        $output = json_decode($output, true);
        if (!isset($output['Datas'])) {
            return new JsonResponse(array('status' => 1, 'msg' => '充提返回数据有问题'));
        }
        //测试自己内部程序的数据
        /*   $output['Datas'] = array(array(
               'result'=> 2,
               'payOrderId'=> 'FC000V201507110028063180016',
               'amount'=> '100.00',
               'orderId'=> $orderId,
           ));
        */
        $len = count($output['Datas']);
        $payOrderJson = '';
        for ($i = 0; $i < $len; $i++) {
            //存在同一个v项目订单号对应2个以上的财务财务订单号
            if ($output['Datas'][$i]['result'] == 2 && !empty($output['Datas'][$i]['payOrderId'])) {
                $payOrderJson = $output['Datas'][$i];
                break;
            }
        }
        //校验到成功的订单号，应该走原来通知回调的逻辑
        if (!!$payOrderJson) {
            return $this->comHandler($payOrderJson, 3);
        } else {
            $sts = $output['Datas'][0]['result'];
            $updat_data = array();
            //订单都没有提交到那边去
            if ($sts === '') {
                $updat_data['pay_status'] = -1;
            } else {
                /**
                 * 写入到recharge表中的充值记录，用于获取到财务订单号处理掉单的情况
                 */
                $updat_data['pay_status'] = $sts;
                $updat_data['pay_id'] = $output['Datas'][0]['payOrderId'];
            }

            // 更新订单状态
            //Recharge::where('id', $ret['id'])->update(['pay_status' => $sts]);
            $msg = isset($this->codeMsg[$sts]) ? $this->codeMsg[$sts] : '失败';
            return new JsonResponse(array('status' => 0, 'msg' => '该订单充值' . $msg . '！'));
        }
    }

    protected function checkChargeLevel()
    {

    }

    private function random_str($length)
    {
        //生成一个包含 大写英文字母, 小写英文字母, 数字 的数组
        $arr = array(
            0, 1, 2, 3, 4, 5, 6, 7, 8, 9,
            'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'
        );
        $str = '';
        $arr_len = count($arr);
        for ($i = 0; $i < $length; $i++) {
            $rand = mt_rand(0, $arr_len - 1);
            $str .= $arr[$rand];
        }

        return $str;
    }
}
