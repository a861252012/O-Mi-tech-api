<?php

namespace App\Services\Safe;

use App\Services\Service;

class RtmpService extends Service
{
    /**
     * 加参后地址
     * @var string
     */
    public $url = "";

    /**
     * rtmp路径
     * @var string
     */
    public $uri = "";
    /**
     * rtmp名字
     * @var string
     */
    public $name = "";
    public $path = "";
    /**
     * 与房间绑定，防盗
     * @var string
     */
    public $sid = "";
    /**
     * rtmp原始地址
     * @var string
     */
    public $origin_url = "";
    /**
     * 额外属性
     * @var string
     */
    public $ext = [];
    /**
     * @var string 加参
     */
    public $params = "";
    public function getRtmp($url="",$sid=""){
        $urlArr = explode('@@',$url);
        $this->sid = $sid;
        $this->origin_url = $sid ? $urlArr[0].'/'.$this->sid : $urlArr[0];
        $this->uri = parse_url($this->origin_url,PHP_URL_PATH);

        $this->path = parse_url($urlArr[0],PHP_URL_PATH);
        $this->name = $urlArr[1];
        return $this;
    }
    /**
     * 获取cdn参数
     */
    public function getParams(){
        $param_arr = [];
        switch ($this->name){
            case 'superVIP:3':
                $this->ext = [];
                $param_arr = $this->getBaiyunshan();
                break;
            case 'superVIP:4':
                $param_arr = $this->getDilian();
                break;
            case 'superVIP:1':
                $this->ext = [];
                $param_arr = $this->getXinyun();
                break;
            default:
                $this->ext = [];
                $param_arr = [];
        }
        $logPath = BASEDIR . '/app/logs/room' . date('Y-m') . '.log';
        $this->make('systemServer')->logResult(' 线路:' . $this->name, $logPath);

        $this->params = http_build_query(array_merge($param_arr,$this->ext));
        return $this->params;
    }

    public function append($params=[]){
        $this->ext = $params;
        return $this;
    }
    /**
     * 获取完整的CDN地址
     * @return string
     */
    public function getUrl(){
        return $this->url = $this->origin_url.'?'.$this->params;
    }

    private function getDilian(){
        $redis = $this->make('redis');
        $rtmp_cdn = $redis->hgetall('hrtmp_cdn:4');
        $time = dechex(time());

        $k = hash('md5', $rtmp_cdn['key'] .$this->uri. $time);
        $param_arr = [
            'k' => $k,
            't' => $time
        ];
        return $param_arr;
    }
    /**
     * 白云山
     * @return array
     */
     private function getBaiyunshan(){
        $redis = $this->make('redis');
        $rtmp_cdn = $redis->hgetall('hrtmp_cdn:3');
        $time = dechex(time()+$rtmp_cdn['down_expire_sec']);
        $k = hash('md5', $rtmp_cdn['key'] .$this->uri. $time);
        $param_arr = [
            'sign' => $k,
            't' => $time
        ];
        return $param_arr;
    }

    /**
     * 星云
     * @return array
     */
    private function getXinyun(){
        $redis = $this->make('redis');
        $rtmp_cnd_key = $redis->hgetall('hrtmp_cdn:1');
        $time = time();
        $k = hash('md5', $rtmp_cnd_key['key'] . $time);
        $param_arr = [
            'k' => $k,
            'time' => $time
        ];
        return $param_arr;
    }
}
