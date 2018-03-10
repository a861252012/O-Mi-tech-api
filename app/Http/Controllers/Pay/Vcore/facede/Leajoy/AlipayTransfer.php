<?php
/**
 * Created by PhpStorm.
 * User: raby
 * Date: 2017/10/24
 * Time: 9:15
 */
namespace Pay\facede\Leajoy;

use App\Models\LeajoyAlipayTransfer;
use App\Models\PayAccount;
use App\Models\PayNotice;
use Pay\c\pay;
use Pay\Log;
use Pay\Twig;

class AlipayTransfer extends Leajoy
{
    use Log, Twig;

    public $config = "";
    public $verify = null;
    public $pay_id = null;
    public $view_para = [];



    /**
    88: 充值成功
    44:没有完整的数据
    79: 提现金额限制
    -77: 没有该条记录
    -78:已经处理
    -3 :md5验证错误
    -1234 接口异常
    -55 校验流水 ，已上过分
    -56 校验时间 充值超过30分钟
    1.0.0.7    【2017-12-12 16:24:12】:    韩德俊^13^1.00^^6214832303609189^0.00^
    现在返回是空白
     * @return string   processed
     */
    const SUCCESS = 88;
    const DATA_ERROR = 44;
    const MONENY_LIMIT = 79;
    const NOT_RECORD = -77;
    const PROCESSED = -78;
    const SIGN_ERROR = -3;
    const INTERFACE_EXP = -1234;
    const VERIFY = 55;
    public function __construct($cid)
    {
        parent::__construct($cid);
    }

    /**
     *
     * 输入
     *
     * table:  pay_acount
     * channel_id
     * card_origin
     * name
     * account
     *
     * @return mixed
     */
    public function pay($data){
        $pid = $data['pay_id'];
        $money = $data['money'];
        $remark = $data['remark'];
        $order_id = $data['order_id'];
        $uid = $data['uid'];
        $username = $data['user_name'];


        $obj = LeajoyAlipayTransfer::query()->where('amount',$money)->where('comment',$remark)->orderBy('created_at','desc')->first();
        if($obj && strtotime($obj['created_at'])+120*60>time()){
            $this->view_para['msg'] = "2小时内，不能提同一金额，同一姓名的订单";
            return $this;
        }



        $account = PayAccount::query()->where('cid',2)->first();
        $accountdata=[
            'bank'=>$account->card_name,
            'rec_name'=>$account->name,
            'account'=>$account->account,
        ];

        $this->pay_id = $pid;

        $this->view_para = [
            'pay_id' => $pid,
            'money' => $money,
            'remark' => $remark,
        ];
        $this->view_para = array_merge($this->view_para,$accountdata);

        //创建记录  请求
        LeajoyAlipayTransfer::create([
            'pay_id'=>$pid,
            'order_id'=>$order_id,
            'uid'=>$uid,
            'username'=>$username,
            'amount'=>$money,
            'comment'=>$remark,
        ]);

        return $this;
    }

    public function build(){
        $data = $this->view_para;
        return $this->render('inner/alipaytransfer',$data);
    }
    /**
     * @param $data
     * @return $this
     */
    public function submit($data, $sign = null, $sync = false){
        $this->log($data);
        PayNotice::create([
            'content'=>$data,
            'cid'=>isset($this->cid) ? $this->cid : 0,
        ]);

        $temp = json_decode($data);

        $obj = json_decode($temp->data);
        $verifymd5 = $temp->verifymd5;

        //更新响应
        $serial_num =  $obj->serial_num;

        $bank_id =  $obj->bank_id;
        $amount =  $obj->amount;
        $usercard_num =  $obj->usercard_num;
        $incomebankcard =  $obj->incomebankcard;
        $fee =  $obj->fee;
        $pay_type =  $obj->pay_type;
        $processtime =  isset($obj->processtime) ? $obj->processtime : null;
        $strKeyInfo = $this->key;


        $strEncypty = "";
        switch ($bank_id){
            case "13":
                $comment =  $obj->comment;
                $strEncypty = MD5($amount . $comment . $strKeyInfo);
                break;
            default:
                $comment =  $obj->comment;
                $strEncypty = MD5($amount . $incomebankcard . $strKeyInfo);
        }



        //      echo $obj->verifymd5.PHP_EOL;
        //      echo $strEncypty.PHP_EOL;
        if($verifymd5!=$strEncypty){
            echo "签名错误 ".$pay_type." ".$verifymd5." ".$strEncypty;
            $this->log("签名错误 ".$pay_type." ".$verifymd5." ".$strEncypty);
            $this->verify = false;
            return $this;
        };


        //更新
        $obj = LeajoyAlipayTransfer::query()->where('amount',$amount)->where('comment',$comment)->orderBy('created_at','desc')->first();

        if(!$obj) {
            $this->log("无此订单  $amount   $comment");
            $this->verify = false;  return $this;
        }

        $rs = $obj->update([
            'serial_num'=>$serial_num,
            'bank_id'=>$bank_id,
            'pay_type'=>$pay_type,
            'usercard_num'=>$usercard_num,
            'fee'=>$fee,
            'incomebankcard'=>$incomebankcard,
            'processtime'=>$processtime,
            'rec_at'=>date('Y-m-d H:i:s'),
        ]);

        //time()-c>120
        $this->pay_id = $obj->pay_id;
        if(strtotime($obj['created_at'])+120*60<time()){
            //手动上分
            $obj->update([
                'status'=>3     //超过120分钟
            ]);
            //echo "超时";
            $this->log("第三方 超时 ".$obj['created_at']);
            $this->verify = false;
        }else{
            //手动上分
            $obj->update([
                'status'=>1
            ]);
            $this->log("第三方 成功 ".$obj['created_at']);
            $this->response = self::SUCCESS;
            $this->verify = true;
        }
        return $this;
    }


    /**
    88: 充值成功
    44:没有完整的数据
    79: 提现金额限制
    -77: 没有该条记录
    -78:已经处理
    -3 :md5验证错误
    -1234 接口异常
    -55 校验流水 ，已上过分
    -56 校验时间 充值超过30分钟
    1.0.0.7    【2017-12-12 16:24:12】:    韩德俊^13^1.00^^6214832303609189^0.00^
    现在返回是空白
     * @return string
     */
    public function getResponse(){
        echo $this->response;
    }
    public function getPayId(){
        return $this->pay_id;
    }
    public function getVerify(){
        return $this->verify;
    }
}