<?php

namespace Pay\facede\DGPay;

use App\Models\PayNotice;
use Pay\c\pay;
use Pay\Log;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Created by PhpStorm.
 * User: nicholas
 * Date: 2017/11/14
 * Time: 16:38
 */
abstract class DGPay extends pay
{
    use Log;
    public $view_param="";

    public function __construct($cid)
    {
        parent::__construct($cid);
    }

    /**
     * pay a order.
     */
    public function pay($data)
    {
        $query = [
            'out_trade_no' => strval($data['pay_id']),
            'method' => $this->payTypeCode,
            'rmb_fee' => intval($data['money']),
            'sign' => $this->sign($data['pay_id'], $this->account, $this->key, $this->payTypeCode),
            'pid' => intval($this->account),
            'return_url' => $this->returnUrl(),
            'notify_url' => $this->noticeUrl(),
            'is_mobile' => $data['is_mobile'] ? 1 : 0
        ];
        $this->view_param = $query;
        return $this;
    }

    protected function sign($oid, $pid, $key, $method)
    {
        return substr(md5($oid . $pid . $key . $method), 8, 8);
    }

    public function build()
    {
        $data = $this->view_param;
        $pay_url = $this->payUrl;
        $param = http_build_query($data);
        $this->log($pay_url . "?" . $param);
        return RedirectResponse::create($pay_url . "?" . $param);
    }

    /**
     * verify notify.
     *
     * @param mixed $data
     * @param string $sign
     * @param bool $sync
     * @return $this
     */
    public function submit($data, $sign = null, $sync = false)
    {
        $this->log($data);
        PayNotice::create([
            'content'=>$data,
            'cid'=>$this->cid,
        ]);
        $obj=json_decode($data);
        $this->pay_id = $obj->out_trade_no;
        if ($obj->status==='success'){
            $ok=true;
        }else{
            $this->log('第三方返回失败 '.$data);
            $ok=false;
        }
        if (!$this->verifySign($obj)){
            $this->log('签名校验失败 '.$data);
            $ok=false;
            //die('sign error');
        }
        $this->response = $ok ? "success" : "fail";
        $this->verify=$ok;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse(){
        echo $this->response;
    }
    protected function verifySign($data)
    {
        return $this->sign($data->out_trade_no,$this->account,$this->key,$this->payTypeCode)===$data->sign;
    }
    public function getPayId(){
        return $this->pay_id;
    }
    public function getVerify()
    {
        return $this->verify;
    }
}