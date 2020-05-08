<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;

class OmeyController extends Controller
{

    public function index(Request $request)
    {
        try {

            echo 'request()->ip() : ' . $request->ip() . "<br>\n";
            echo '加入proxy方式: ' . $this->laGetIp() . "<br>\n";
            echo '純PHP方式: ' . $this->getIp() . "<br>\n";
            echo '純PHP方式2: ' . $this->getIp2() . "<br>\n";
            echo '純PHP方式3: ' . $this->getIp3() . "<br>\n";

        } catch (\Exception $e) {
            report($e);

        }
    }

    public function fakeCall()
    {
        $apiUri = 'http://nginx/api/m/fake_call';

        $client = new Client(['X-Forwarded-For' => '172.104.108.204, 162.158.119.171, 172.104.108.204']);
        $result = $client->request('GET', $apiUri);

        return $result->getBody()->getContents();
    }

    private function laGetIp()
    {
//        dd(\Illuminate\Http\Request::HEADER_X_FORWARDED_FOR);
        request()->setTrustedProxies(request()->getClientIps() ?? [], \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR);
        return request()->ip();
    }

    private function getIp()
    {
        $arr_ip_header = [
            'HTTP_CDN_SRC_IP',
            'HTTP_PROXY_CLIENT_IP',
            'HTTP_WL_PROXY_CLIENT_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR',
        ];

        $client_ip = 'unknown';

        foreach ($arr_ip_header as $key) {
            if (!empty($_SERVER[$key]) && strtolower($_SERVER[$key]) != 'unknown') {
                $client_ip = $_SERVER[$key];
                break;
            }
        }

        return $client_ip;
    }

    public function getIp2()
    {
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key){
            if (array_key_exists($key, $_SERVER) === true){
                foreach (explode(',', $_SERVER[$key]) as $ip){
                    $ip = trim($ip); // just to be safe

                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
                        return $ip;
                    }
                }
            }
        }
    }

    public function getIp3() {
        $ip = 'unknow';
        foreach (array(
                     'HTTP_CLIENT_IP',
                     'HTTP_X_FORWARDED_FOR',
                     'HTTP_X_FORWARDED',
                     'HTTP_X_CLUSTER_CLIENT_IP',
                     'HTTP_FORWARDED_FOR',
                     'HTTP_FORWARDED',
                     'REMOTE_ADDR') as$key) {
            if (array_key_exists($key, $_SERVER)) {
                foreach (explode(',', $_SERVER[$key]) as$ip) {
                    $ip = trim($ip);
                    //會過濾掉保留地址和私有地址段的IP，例如 127.0.0.1會被過濾
                    //也可以修改成正則驗證IP
                    if ((bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return$ip;
                    }
                }
            }
        }
        return$ip;
    }
}
