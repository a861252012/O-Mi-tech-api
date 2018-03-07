<?php
/**
 * Created by PhpStorm.
 * User: raby
 * Date: 2017/10/25
 * Time: 11:16
 */

namespace Pay;

trait Log {
    function log($data="",$file="") {

        $filePath = PAY_DIR."/log/";
        $file = $file ?: 'test_'.date('Y-m-d').'.log';

        $data = date('Y-m-d H:i:s')."\t".$data."\n";
        file_put_contents($filePath.$file,$data,FILE_APPEND);
    }
    function getReturnDescription() { /*2*/ }
}