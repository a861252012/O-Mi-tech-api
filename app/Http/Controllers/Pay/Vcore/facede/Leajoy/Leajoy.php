<?php
/**
 * Created by PhpStorm.
 * User: raby
 * Date: 2017/10/24
 * Time: 9:15
 */
namespace Pay\facede\Leajoy;

use Pay\c\pay;
use Pay\Log;

abstract class Leajoy extends pay
{
    use Log;

    public $config = "";
    public $verify = null;
    public $pay_id = null;
    public $view_para = [];
    public function __construct($cid)
    {
        parent::__construct($cid);
    }


    public function build(){
    }

    public function submit($data, $sign = null, $sync = false){

    }
    public function getPayId(){
        return $this->pay_id;
    }
    public function getVerify(){
        return $this->verify;
    }


}