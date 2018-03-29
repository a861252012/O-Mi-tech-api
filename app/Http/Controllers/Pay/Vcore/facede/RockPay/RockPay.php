<?php
/**
 * Created by PhpStorm.
 * User: raby
 * Date: 2017/10/24
 * Time: 9:15
 */
namespace Pay\facede\RockPay;

use App\Models\PayNotice;
use Pay\c\pay;
use Pay\HttpUrl;
use Pay\Log;
use Pay\Twig;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

/*
            'app' => 'testapp',
            'pay_url' => 'http://api.lulurepay.com/CreateOrder/Json',
            'search_url' => 'http://api.lulurepay.com/TransLogSearch/Json',

            'key' => '6c9720e78a0342298e9a1df536cc493c',
            'pw' => 'qqpay',
*/
abstract class RockPay extends pay
{
    use Log, Twig;

    public $config = "";
    public $verify = null;
    public $pay_id = null;
    public $view_para = [];
    public function __construct($cid)
    {
        parent::__construct($cid);
    }

    /**
     *
     * 输入pid, money
     *
     * @return mixed
     */
    public function pay($data){
        $app = $this->account;
        $r_url = $this->noticeUrl();

        $aoid=$data['pay_id'];
        $p=intval($data['money']);
        $pw=$this->payTypeCode;
        $key=$this->key;
        $m1='uid';
        $m2="";

        $v_code = MD5(implode('*',[
            strtolower($app), strtolower($aoid),$r_url,$m1,$m2,strtolower($pw),$p,$key
        ]));

        $data = [
            'app'=>$app,
            'aoid'=>$aoid,
            'r_url'=>$r_url,
            'pw'=>$pw,
            'p'=>$p,
            'm1'=>$m1,
            'm2'=>$m2,
            'v_code'=>strtolower($v_code),
        ];
        $this->view_para = $data;
        return $this;
    }

    public function sign(){

    }


    public function build(){
        $data = $this->view_para;
        $pay_url = $this->payUrl;

        $data['pay_url'] = $pay_url;
        $tmp = HttpUrl::init()->post([
            'app'=>$data['app'],
            'aoid'=>$data['aoid'],
            'r_url'=> $this->noticeUrl(),
            'pw'=>$data['pw'],
            'p'=>$data['p'],
            'm1'=>$data['m1'],
            'm2'=>$data['m2'],
            'v_code'=>$data['v_code'],
        ])->header([
            'Content-Type:application/json'
        ])->submit($pay_url);

// {
//    "ResultCode": 1,
//    "ErrorMessage": "",
//    "TransUrl": "http://www.lulurepay.com/Default/ddFR"
//}
        $tempArr = $tmp->getArrayResponse();
        if(empty($tempArr)) return "";
        if($tempArr['ResultCode'] != 1) return json_encode($tempArr);

        $url = $tempArr['TransUrl'];
        return new RedirectResponse($url);
    }

    /**
     * find a order.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @param string $out_trade_no
     *
     * @return array|bool
     */
    public function find($search){
        $app = $this->account;
        $key = $this->key;
        $url = $this->findUrl;
        $stime = isset($search['stime']) ? $search['stime']:"";
        $etime = isset($search['stime']) ? $search['stime']:"";
        $pay_id = isset($search['pay_id']) ? $search['pay_id']:"";
        $RojoOrderID = isset($search['RojoOrderID']) ? $search['RojoOrderID']:"";
        $v_code = MD5(implode('*',array(
            strtolower($app),$stime,$etime,$RojoOrderID,strtolower($pay_id),$key
        )));

        $findObj = HttpUrl::init()->header([
            'Content-type'=>'application/json'
        ])->post([
            'app'=>$app,
            'SearchstartTime'=>$stime,
            'SearchEndTime'=>$etime,
            'RojoOrderID'=>$RojoOrderID,
            'aoid'=>$pay_id,
            'v_code'=>strtolower($v_code),
        ])->submit($url);

        $data = $findObj->getArrayResponse();
        if(isset($data['ResultCode']) && $data['ResultCode']==1){
            return [
                'code'=>true,
                'msg'=>json_encode($data['ListLog']),
            ];
        }
        return [
            'code'=>false,
            'msg'=>json_encode($data),
        ];
    }

    public function submit($data, $sign = null, $sync = false){

        $this->log($data);
        PayNotice::create([
            'content'=>$data,
            'cid'=>isset($this->cid) ? $this->cid : 0,
        ]);
        //查询
        //array(5) { ["aoid"]=> string(10) "1508905494" ["m1"]=> string(7) "pgstest" ["m2"]=> string(0) "" ["tid"]=> string(18) "1508905501test913d" ["v_code"]=> string(32) "ae4ade5d14db6a251909f574eed7140f" }
        $temp = json_decode($data,true);
        $this->pay_id = $temp['aoid'];

        if($temp['p']!=$temp['rp']){
            $this->verify = false;
            $this->log("金额不一致".$data);
            return $this;
        }
        if($temp['TransType']!=1){
            $this->verify = false;
            $this->log("交易失败".$data);
            return $this;
        }else{
            $this->log("交易成功".$data);
        }

        //确认
        $transaction_id = $temp['tid'];
        $key = $this->key;
        $app = $this->account;

        $verify = MD5(implode('*',[
            strtolower($app),
            strtolower($this->pay_id),
            strtolower($transaction_id),
            $temp['TransType'],
            strtolower($temp['m1']),
            strtolower($temp['m2']),
            strtolower($temp['pw']),
            $temp['p'],
            $temp['rp'],
            $key,
        ]));
        if(strtolower($verify)!=strtolower($temp['v_code'])){
            $this->verify = false;
            $this->log("签名失败");
            return $this;
        }
        $this->verify = true;
        return $this;
    }
    public function getPayId(){
        return $this->pay_id;
    }
    public function getVerify(){
        return $this->verify;
    }

    public function getResponse(){
        echo $this->response;
    }

}