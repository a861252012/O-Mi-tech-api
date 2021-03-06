<?php

namespace App\Services\Safe;
use App\Facades\SocketCertificate;
use App\Services\System\SystemService;
use App\Services\Service;
use DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Facades\SiteSer;
class SafeService extends Service
{
    const DEFAULT_AES_KEY = '4fc358add16ebd2bb3226523ba0d91dd'; // md5('Hello Omey!')

    //
    public function auth($uid)
    {
        if (!isset($_SERVER['HTTP_X_FORWARDED_FOR'])) return true;
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];

        $redis = $this->make('redis');
        $back_ips = $redis->smembers('back_ip:' . $ip);
        $back_uids = $redis->smembers('back_uid:' . $uid);
        $uid = $uid?:0;
        //check back list;
        if (in_array($uid, $back_ips)) return false;
        if (in_array($ip, $back_uids)) return false;

        $date = date('Y-m-d H:i:s');
        // yes first view
        if (!$redis->exists("ip:$ip")) $redis->hmset("ip:$ip", ['first_time' => $date, 'last_time' => $date, 'start_time' => $date, 'count' => 0, 'total_count' => 0,]);
        $redis->hmset("ip:$ip", ['last_time' => $date]);

        $ip_info = $redis->hgetall("ip:$ip");
        $min = $this->make('redis')->hget('hsite_config'.SiteSer::siteId(),'back_hz_min');
        //current time is >= start time + 10minis
        if (strtotime($date) >= (strtotime($ip_info['start_time']) + 60 * $min)) $redis->hmset("ip:$ip", ['start_time' => $date, 'count' => 0]);
        $redis->hIncrBy("ip:$ip", "count", 1);
        $redis->hIncrBy("ip:$ip", "total_count", 1);

        //
        $hz = $this->make('redis')->hget('hsite_config'.SiteSer::siteId(),'back_hz_count');
        if ( ++$ip_info['count'] >= $hz) {
            $redis->sAdd('back_ip:' . $ip, $uid);
            $redis->sAdd('back_uid:' . $uid, $ip);

            return false;
        }
        return true;
    }

    /**
     * 获取票据
     */
    public function getLcertificate($type=""){
        $data = "";
        switch ($type){
            case 'cdn':
                return $this->getCdnCertificate();
                break;
            case 'socket':
            default:
                $data = $this->getSocketCertificate();
            return $this->_encrypt($data);
        }
    }

    /**
     * @return string
     */
    private function getSocketCertificate()
    {
        /**
         * @var $redis RedisServices
        */
        $redis = $this->make('redis')->connection('ceri');

        $hz = $redis->hExists('hsite_config'.SiteSer::siteId(),'certificate_hz') ? $redis->hget('hsite_config'.SiteSer::siteId(),'certificate_hz') :0;

        if(empty($hz)){
            $lcertificate = SocketCertificate::generateCertification();
//            $lcertificate = $redis->rpop("lsocket_certi");
            Log::channel('room')->info('lcertificate:' . $lcertificate);
            return $lcertificate;
        }

        $expire = SiteSer::config('hcertificate_start_expire');

        $ip = $this->make("request")->getClientIp();

        $start = $redis->hget("hcertificate_start:$ip",'start_time');
        if(!$start){
            $start = date('Y-m-d H:i:s');
            $redis->hset("hcertificate_start:$ip",'start_time',$start);
            $redis->expire("hcertificate_start:$ip",$expire);
        }
        if( time()<=$hz+strtotime($start)) return $redis->hget("hcertificate_start:$ip",'certificate');

        //get
        $data = SocketCertificate::generateCertification();
//        $data =  $redis->rpop("lsocket_certi");
        $redis->hmset("hcertificate_start:$ip",[
            'start_time'=>date('Y-m-d H:i:s'),
            'certificate'=>$data,
        ]);
        $redis->expire("hcertificate_start:$ip",$expire);
        return $data;
    }

    /**
     * @return string
     */
    private function getCdnCertificate(){
        /**
         * @var $redis \Redis
         */
        $redis = $this->make('redis')->connection('ceri');
        $hz = $redis->hExists('hsite_config'.SiteSer::siteId(),'certificate_hz') ? $redis->hget('hsite_config'.SiteSer::siteId(),'certificate_hz') :0;

        if(empty($hz)){
//            $lcertificate = $redis->rpop("lcdn_certi");
//            Log::channel('room')->info('lcertificate:' . $lcertificate);
//            return $lcertificate;
            return '';
        }
        $expire = SiteSer::config('hcertificate_start_expire');

        $ip = $this->make("request")->getClientIp();

        $start = $redis->hget("hcertificate_start:$ip",'start_time');
        if(!$start){
            $start = date('Y-m-d H:i:s');
            $redis->hset("hcertificate_start:$ip",'start_time',$start);
            $redis->expire("hcertificate_start:$ip",$expire);
        }
        if( time()<=$hz+strtotime($start)) return $redis->hget("hcertificate_start:$ip",'certificate');

        //get
//        $data =  $redis->rpop("lcdn_certi");
        $data = '';
        $redis->hmset("hcertificate_start:$ip",[
            'start_time'=>date('Y-m-d H:i:s'),
            'certificate'=>$data,
        ]);
        $redis->expire("hcertificate_start:$ip",$expire);
        return $data;
    }

    public function _encrypt($str){
        $iv = $this->rand_string(6);
        $private = "csoemi";
        $key = $private.$iv;
        $keylen = strlen($key);
        $crytxt = "";
        for($i=0;$i<strlen($str);$i++)
        {
            $k = $i%$keylen;
            $crytxt .= $str[$i] ^ $key[$k];
        }
        return $crytxt.'.'.$iv;
    }

    public function rand_string($pw_length){
        $randpwd = "";
        for ($i = 0; $i < $pw_length; $i++)
        {
            $randpwd .= chr(mt_rand(97, 122));
        }
        return $randpwd;
    }

    public function _decrypt($str){
        $key = "hello";
        $keylen = strlen($key);
        $crytxt = "";
        for($i=0;$i<strlen($str);$i++)
        {
            $k = $i%$keylen;
            $crytxt .= $str[$i] ^ $key[$k];
        }
        return $crytxt;
    }

    public function padZero($data)
    {
        $len = 16;
        if (strlen($data) % $len) {
            $padLength = $len - strlen($data) % $len;
            $data .= str_repeat("\0", $padLength);
        }
        return $data;
    }

    public function AESEncrypt($data, $key = self::DEFAULT_AES_KEY)
    {
        $ivsize = openssl_cipher_iv_length('AES-256-CBC');
        $iv = openssl_random_pseudo_bytes($ivsize);

        $options = OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING;
        $encrypted = openssl_encrypt($this->padZero($data), 'AES-256-CBC', $key, $options, $iv);
        return [
            'base64_iv' => base64_encode($iv),
            'base64_encrypted' => base64_encode($encrypted),
        ];
    }

    public function AESDecrypt($base64_encrypted, $base64_iv, $key = self::DEFAULT_AES_KEY)
    {
        $iv = base64_decode($base64_iv);
        $encrypted = base64_decode($base64_encrypted);
        $options = OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING;
        $data = openssl_decrypt($encrypted, 'AES-256-CBC', $key, $options, $iv);
        $data = rtrim($data, chr(0));
        return $data;
    }
}
