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

abstract class RockPay implements pay
{
    use Log, Twig;

    public $config = "";
    public $verify = null;
    public $pay_id = null;
    public $view_para = [];
    public function __construct($config)
    {
        $this->log(json_encode($config));
        $this->config = $config;
    }

    /**
     *
     * 输入pid, money
     *
     * @return mixed
     */
    public function _pay($data){
        $app = $this->config['app'];
        $r_url = $this->config['r_url'];
        $aoid=$data['pay_id'];
        $p=intval($data['money']);
        $pw="Demo";
        $key=$this->config['key'];
        $m1=$this->config['key'];
        $m2="";
        $v_code1 = MD5($app.$aoid.$p.strtolower($pw).$key);
        $v_code2 = MD5($app.$aoid.$m1.$m2.$r_url.$key);

        $data = [
            'app'=>$this->config['app'],
            'aoid'=>$aoid,
            'p'=>$p,
            'pw'=>$this->config['pw'],
            'r_url'=>$this->config['r_url'],
            'm1'=>$this->config['key'],
            'm2'=>"",
            'v_code1'=>$v_code1,
            'v_code2'=>$v_code2,
        ];
        $this->view_para = $data;
        return $this;
    }

    /**
     *
     * 输入pid, money
     *
     * @return mixed
     */
    public function pay($data){
        $app = $this->config['app'];
        $r_url = $this->config['r_url'];
        $aoid=$data['pay_id'];
        $p=intval($data['money']);
        $pw="Demo";
        $key=$this->config['key'];
        $m1='uid';
        $m2="";

        $v_code = MD5(implode('*',[
            strtolower($app), strtolower($aoid),$r_url,$m1.$m2.$pw.strtolower($p).$key
        ]));

        $data = [
            'app'=>$this->config['app'],
            'aoid'=>$aoid,
            'r_url'=>$this->config['r_url'],
            'pw'=>$this->config['pw'],
            'p'=>$p,
            'm1'=>$m1,
            'm2'=>$m2,
            'v_code'=>strtolower($v_code),
        ];
        $this->view_para = $data;
        return $this;
    }


    public function _build(){
        $data = $this->view_para;
        $pay_url = $this->config['pay_url'];
        $param = http_build_query($data);

        $this->log($pay_url."?".$param);
        return new RedirectResponse($pay_url."?".$param);
    }



    public function build(){
        $data = $this->view_para;
        $pay_url = $this->config['pay_url'];

        $data['pay_url'] = $pay_url;
        $tmp = HttpUrl::init()->post([
            'app'=>$data['app'],
            'aoid'=>$data['aoid'],
            'r_url'=>$data['r_url'],
            'pw'=>$data['pw'],
            'p'=>$data['pw'],
            'm1'=>$data['pw'],
            'm2'=>$data['pw'],
            'v_code'=>$data['v_code'],
        ])->header([
            'Content-type'=>'application/json'
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
    public function _find($out_trade_no=""){
        $transaction_id = $out_trade_no;
        $key = $this->config['key'];
        $app = $this->config['app'];

        $verify_code = strtolower(MD5($app.$transaction_id .strtolower($key)));
        $ap_param = array(
            'app_id'     =>    $app,
            'transaction_id'     =>    $transaction_id,
            'verify_code'     =>    $verify_code,
        );
        $return = $this->sendWebServer($this->config['query_url'],$ap_param);

        $code = $return['code'];
        $info = $return['info'];
        if(empty($code)) return ['code'=>false,'msg'=>'web server '.$this->config['query_url'].'error'];

        $arr = $info->CheckTransactionWithCurrencyV2Result->string;
        return [
            'code'=>$arr[0]==1 ? true : false,
            'msg'=>json_encode($arr),
        ];

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
        $app = $this->config['app'];
        $key = $this->config['key'];
        $url = $this->config['search_url'];
        $stime = isset($search['stime']) ? $search['stime']:"";
        $etime = isset($search['stime']) ? $search['stime']:"";
        $pay_id = isset($search['pay_id']) ? $search['pay_id']:"";
        $order_id = isset($search['order_id']) ? $search['order_id']:"";
        $v_code = MD5(implode('*',array(
            strtolower($app),$stime,$etime,$pay_id,strtolower($order_id),$key
        )));

        $findObj = HttpUrl::init()->header([
            'Content-type'=>'application/json'
        ])->post([
            'app'=>$app,
            'SearchstartTime'=>$stime,
            'SearchEndTime'=>$etime,
            'RojoOrderID'=>$pay_id,
            'aoid'=>$order_id,
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

    public function sendWebServer($router,$ap_param){
        $soapClient = new \SoapClient($this->config['wsdl'].'?wsdl',
            array( 'trace' => true, 'exceptions' => true ) );
        $code = 1;
        try {
            $info = $soapClient->__call($router, array($ap_param));
        } catch (\SoapFault $fault) {
            $code = 0;
            $info = " alert('Sorry, blah returned the following ERROR: ".$fault->faultcode."-".$fault->faultstring.". We will now take you back to our home page.');
            window.location = 'main.php'; ";
        }

        $this->log($router."\t\t".json_encode([
            'code'=>$code,
            'info'=>$info
        ]));
        return [
            'code'=>$code,
            'info'=>$info
        ];
    }

    /**
     * verify notify.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @param mixed  $data
     * @param string $sign
     * @param bool   $sync
     *
     *
    //array(5) { ["aoid"]=> string(10) "1508905494" ["m1"]=> string(7) "pgstest" ["m2"]=> string(0) "" ["tid"]=> string(18) "1508905501test913d" ["v_code"]=> string(32) "ae4ade5d14db6a251909f574eed7140f" }
     *
     * @return array|bool
     */
    public function _submit($data, $sign = null, $sync = false){

        $this->log($data);
        PayNotice::query()->create([
            'content'=>$data
        ]);
        //查询
        //array(5) { ["aoid"]=> string(10) "1508905494" ["m1"]=> string(7) "pgstest" ["m2"]=> string(0) "" ["tid"]=> string(18) "1508905501test913d" ["v_code"]=> string(32) "ae4ade5d14db6a251909f574eed7140f" }
        $temp = json_decode($data);
        $this->pay_id = $temp->aoid;
        $transaction_id = $temp->tid;


        $this->find($transaction_id);

        //确认
        $key = $this->config['key'];
        $app = $this->config['app'];

        $verify_code = strtolower(MD5($app.$transaction_id .strtolower($key)));
        $ap_param = array(
            'app_id'     =>    $app,
            'transaction_id'     =>    $transaction_id,
            'verify_code'     =>    $verify_code,
        );
        $rs = $this->sendWebServer($this->config['submit_url'],$ap_param);
        $info = $rs['info'];

        $arr = $info->CommitTransactionResult->string;

        //todo test
        //$this->verify = $arr[0]==-1003 ? true :false;
        $this->verify = $arr[0]==1 ? true :false;
        $this->msg = json_encode($arr);

        return $this;
    }


    public function submit($data, $sign = null, $sync = false){

        $this->log($data);
        PayNotice::query()->create([
            'content'=>$data
        ]);
        //查询
        //array(5) { ["aoid"]=> string(10) "1508905494" ["m1"]=> string(7) "pgstest" ["m2"]=> string(0) "" ["tid"]=> string(18) "1508905501test913d" ["v_code"]=> string(32) "ae4ade5d14db6a251909f574eed7140f" }
        $temp = json_decode($data,true);
        $this->pay_id = $temp['aoid'];

        if($temp['p']!=$temp['rp']){
            $this->log("金额不一致".$data);
            return $this;
        }
        if($temp['TransType']!=1){
            $this->log("交易失败".$data);
            return $this;
        }

        //确认
        $transaction_id = $temp['tid'];
        $key = $this->config['key'];
        $app = $this->config['app'];

        $verify = MD5(implode('*',[
            $app,
            $this->pay_id,
            $transaction_id,
            $temp['TransType'],
            $temp['m1'],
            $temp['m2'],
            $temp['pw'],
            $temp['p'],
            $temp['rp'],
            $key,
        ]));
        if($verify!=$temp['v_code']) $this->log("签名失败");
        return $this;
    }
    public function getPayId(){
        return $this->pay_id;
    }
    public function getVerify(){
        return $this->verify;
    }


}