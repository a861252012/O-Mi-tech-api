<?php
/**
 * Created by PhpStorm.
 * User: raby
 * Date: 2017/10/24
 * Time: 9:10
 */
namespace App\Controller\Pay;

use App\Models\Pay;
use App\Models\PayConfig;
use Illuminate\Support\Arr;
use Pay\facede\DGPay\DGPay;
use Pay\HttpUrl;

define('PAY_DIR',BASEDIR.'/Vcore/App/Controller/Pay/Vcore');

class PayController extends \Core\Controller
{
    /**
     *  功能，容错，日志，引导
     *
     * 访问第三方页面
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     */
    public function index()
    {
        $postdata = isset($_POST['postDatas']) ? $_POST['postDatas']: "";

        if(empty($postdata)) return "无数据";

        $arr = collect(explode('.',$postdata))->map(function ($v,$k){
            return $k==0 ?  base64_decode($v) : $v;
        });

        list($postdata,$sign) = $arr;
        file_put_contents((PAY_DIR.'/log/index.txt'), date("Y-m-d H:i:s")."\r数据:".$postdata."  sign:".$sign."\r\n", FILE_APPEND);

        $postdataObj = json_decode($postdata,true);

        //写前台
        $platConfig =  $this->platConfig($postdataObj['plat_code']);
        if(hash_hmac('sha256', $postdata, $platConfig['key']) !== $sign){
            return "签名失败";
        }

        //$postdataObj['order_id'] = time();
        if(Pay::query()->where('order_id',$postdataObj['order_id'])->first()) return "重复提单";

        $pay_id = $postdataObj['plat_code'].date('YmdHis') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

        $pay = PayConfig::query()->where('cid',$postdataObj['channel'])->first();
        $data2 = Arr::only($postdataObj,['money','order_id','plat_code','uid','user_name','remark','origin']);
        $data1 = [
            'cid'=>$postdataObj['channel'],
            'pay_id'=>$pay_id,
            'appid'=>$pay['account'],
        ];
        $data = array_merge($data1,$data2);
        Pay::create($data);
        if (isset($postdataObj['isMobile'])){
            $data['is_mobile']=$postdataObj['isMobile']?1:0;
        }
        $html = \Pay\Pay::driver($data['cid'])->pay($data)->build();
        return $html;
    }


    /**
     * @return mixed
     */
    public function find(){
        $pay_id = $this->request()->get('pay_id');
        $data = Pay::query()->where('pay_id',$pay_id)->first();
        $obj = $data['cid'];
        return \Pay\Pay::driver($obj)->find([
            'pay_id'=>$pay_id
        ]);
    }

    private function platConfig($plat=""){
        $config = include "Vcore/config/config.php";
        return  $config['insert_plat'][$plat];
    }

    /**
     * @param $action
     * 需求：
     * 根据通知地址， 获取相应的对象， 完成支付和认证，通知平台，  写入回调数据
     */
    public function notify($action)
    {
        file_put_contents((PAY_DIR.'/log/notify.txt'), "收到通知".json_encode($_POST)."\r\n", FILE_APPEND);

        $obj = $action;
        if('inner'==$obj)  $obj=35;

        $temp = [];
        switch ($_SERVER['REQUEST_METHOD']){
            case 'POST':
                $temp = $_POST;
                break;
            case 'GET':
                $temp = $_GET;
                break;
        }
        $data =json_encode($temp);


        $pay = \Pay\Pay::driver($obj)->submit($data);
        $rs = $pay->getVerify();
        if ($rs) {
            $g2p = [
                'pay_id'=>  $pay->getPayId()
            ];

            $time = date('Y-m-d H:i:s');
            $pay_data = Pay::query()->where('pay_id',$g2p['pay_id'])->first();
            $pay_data->update([
                'pay_at'=>$time,
                'status'=>2,
            ]);

            $pay->getResponse();

            $v2 = [
                'pay_id'=>$g2p['pay_id'],
                'order_id'=>$pay_data['order_id'],
                'money'=>$pay_data['money'],
                'result'=>$pay_data['status'],      //result   2  成功，0等支付   1处理中   -1 未知
                'channel'=>$pay_data['channel'],
                'complateTime'=>$time,
            ];

            $platConfig = $this->platConfig($pay_data['plat_code']);

            $notice = $platConfig['notice_url'];

            $postdata = json_encode($v2);
            $sign =  hash_hmac('sha256', $postdata, $platConfig['key']);
            $postdataStr = base64_encode($postdata).'.'.$sign;

            //echo $postdataStr.PHP_EOL;
            $http = HttpUrl::init()->post([
                'postdata'=>$postdataStr
            ])->submit($notice);


            $log = "";
           // echo $http->getStatus().PHP_EOL;

            $log = $http->getResponse();
            //echo $log.PHP_EOL;
            if($http->getStatus()==200){
                $log = $http->getResponse();

                $pay_data->update([
                    'finish_at'=>date('Y-m-d H:i:s'),
                ]);
                $log .= "充值成功\r\n";
            }else{
                $log = "充值失败 ".$http->getStatus().' url:'.$notice.' '.$http->getError();
            }
            file_put_contents((PAY_DIR.'/log/notify.txt'), $log."\r\n", FILE_APPEND);
        } else {
            $log =  json_encode($rs)."失败\r\n";
            $pay->getResponse();
            file_put_contents((PAY_DIR.'/log/notify.txt'), "$log\r\n", FILE_APPEND);
        }
    }
}

require_once "vendor/autoload.php";
//$pay = new PayController();
//if(isset($_GET['a'])){
//    Header("Location: ".$pay->index());
//}else{
//    $_POST = "";
//    $pay->notify("notify");
//}

?>