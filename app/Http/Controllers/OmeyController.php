<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OmeyController extends Controller
{
    public function __construct()
    {

    }

    public function index(Request $request)
    {
        try {

            echo 'request()->ip() : ' . $request->ip() . '<br>';
            echo '加入proxy方式: ' . $this->laGetIp() . '<br>';
            echo '純PHP方式: ' . $this->getIp() . '<br>';

        } catch (\Exception $e) {
            report($e);

        }
    }

    private function laGetIp()
    {
//        dd(\Illuminate\Http\Request::HEADER_X_FORWARDED_FOR);
        request()->setTrustedProxies(request()->getClientIps ?? [], \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR);
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
}
