<?php
/**
 * Created by PhpStorm.
 * User: raby
 * Date: 2017/10/24
 * Time: 9:24
 */
namespace Pay;
class Pay
{
    private function __construct(){
    }

    /**
     * @param string $cid
     * @return \Pay\c\pay
     */
    public static function driver($cid=""){
        $config = include "config/config.php";
        $bus_config = [];

        list($dir,$class) = explode('.',$config['router'][$cid]);
        $obj = '\\Pay\\facede\\'.$dir.'\\'.$class;
        $bus_config['cid'] = $cid;

        try{
           return new $obj($bus_config);
        }catch (\Exception $e){
            echo "渠道配置有问题 $obj".$e->getMessage();
        }
    }
}