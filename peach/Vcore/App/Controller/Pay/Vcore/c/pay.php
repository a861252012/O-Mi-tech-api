<?php
/**
 * Created by PhpStorm.
 * User: raby
 * Date: 2017/10/24
 * Time: 9:16
 */

namespace Pay\c;

use App\Models\PayConfig;

abstract class pay
{
    //public  $config=[];
    public  $payUrl="";
    public  $noticeUrl="";
    public  $findUrl="";

    public  $key="";
    public  $account="";

    public  $cid="";
    public $payTypeCode="";  //支付类型代号：如：DGPay支付宝是z_ali，微信等

    public $pay_id="";
    public $verify="";

    public $response="";

    public function __construct($cid)
    {
        $pay = PayConfig::query()->where('cid',$cid)->first();

        $this->payUrl = $pay->view_url;
        $this->noticeUrl = $pay->notice_url;
        $this->findUrl = $pay->find_url;
        $this->key = $pay->key;
        $this->account = $pay->account;
        $this->cid = $pay->cid;
        $this->payTypeCode = $pay->pay_type_code;
    }
    public function noticeUrl(){
        return 'http://'.$_SERVER['HTTP_HOST'].'/pay/'.$this->cid;
    }
    public function returnUrl(){
        return 'http://'.$_SERVER['HTTP_HOST'].'/member/charge';
    }
    /**
     * pay a order.
     * @return $this
     */
    abstract function pay($config_biz);

    /**
     * verify notify.
     *
     * @param mixed  $data
     * @param string $sign
     * @param bool   $sync
     *
     * @return $this
     */
    abstract function submit($data, $sign = null, $sync = false);

    abstract function getVerify();

    /**
     * 响应数据
     */
    abstract function getResponse();

    abstract function build();

    abstract function getPayId();
}