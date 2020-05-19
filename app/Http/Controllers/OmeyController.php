<?php

namespace App\Http\Controllers;

use App\Facades\SiteSer;
use App\Services\User\UserService;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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

    // 二站整合測試
    public function v2(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            echo '請先登入';
            return;
        }

        $act = $request->route('act');
        if ($act === 'check') {
            $this->v2Check($rid);
        } elseif (is_null($act)) {
            $this->v2Index();
        } else {
            $this->v2Live($act);
        }
    }

    private function v2Index()
    {
        $hosts = json_decode(file_get_contents(Storage::path('/public/s1/videolistall.json')), true);

        $cdnHost = SiteSer::config('cdn_host');
        $liveHosts = [];
        foreach ($hosts['rooms'] as $h) {
            if ($h['live_status'] == 0) {
                continue;
            }
            $liveHosts[] = [
                'rid' => $h['rid'],
                'cover' => $h['cover'],
            ];
        }
        if (count($liveHosts) == 0) {
            echo '目前無主播上線';
            return;
        }

        echo '<h1>PC</h1>';
        foreach ($liveHosts as $h) {
            echo '<a href="/api/omey/v2/', $h['rid'], '">',
                '<img width="100" src="', $cdnHost, '/storage/uploads/s88888/anchor/', $h['cover'], '">',
                '</a>';
        }

        echo '<h1>Mobile</h1>';
        foreach ($liveHosts as $h) {
            $v2URL = $this->v2GetEntryURL($h['rid']). '&m=1';
            echo '<a href="', $v2URL, '">',
                '<img width="100" src="', $cdnHost, '/storage/uploads/s88888/anchor/', $h['cover'], '">',
                '</a>';
        }
    }

    private function v2Live($rid)
    {
        echo '<h1>蜜坊</h1>';
        echo '<iframe src="', $this->v2GetEntryURL($rid), '" width="100%" height="90%"></iframe>';
    }

    private function v2GetEntryURL($rid)
    {
        $user = Auth::user();
        $platforms = $this->make('redis')->hgetall('hplatforms:60');
        $host = 'http://'. $_SERVER['HTTP_HOST'];

        $sskey = $user->uid;    // 不同合作站會有自己的加密資料。
        $callback = $rid;
        $key = $platforms['key'];
        $signData = [$sskey, $callback, $key];
        $sign = md5(implode('', $signData));
        $q = [
            'origin' => 60,
            'sskey' => $sskey,
            'callback' => $callback,
            'sign' => $sign,
            'httphost' => $host,
        ];
        $qs = http_build_query($q);

        $v2Host = str_replace('v1.com', 'v2.com', $host);
        $v2URL =  $v2Host .'/recvSskey?'. $qs;
        return $v2URL;
    }

    public function v2Check(Request $request)
    {
        $uid = $request->get('sskey');
        $userService = resolve(UserService::class);
        $userInfo = $userService->getUserByUid($uid);
        if (!$userInfo) {
            return JsonResponse::create([
                'status' => 0,
                'msg'    => '用户不存在',
            ]);
        }

        $d = [
            'data' => [
                'uuid' => $uid,
                'nickename' => $userInfo->nickname,
            ],
        ];
        echo json_encode($d);
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
