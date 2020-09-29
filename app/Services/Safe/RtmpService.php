<?php


namespace App\Services\Safe;

use App\Services\Service;
use App\Services\Safe\SafeService;

class RtmpService extends Service
{
    public $sid = "";
    private $rid = 0;
    private $isHost = false;

    public function setRoom($rid)
    {
        $this->rid = $rid;
        return $this;
    }
    public function isHost($isHost)
    {
        $this->isHost = $isHost;
        return $this;
    }
    public function getURL()
    {
        // host
        if ($this->isHost) {
            return $this->getUpstreamRTMP();
        }

        // user
        return $this->getDownstreamRTMP();
    }

    /**
     * 获取房间的rtmp上播地址
     * @param $rid
     * @return array
     */
    protected function getUpstreamRTMP()
    {
        return [
            'rtmp' => $this->getRTMPServers(),
        ];
    }
    protected function getRTMPServers()
    {
        $redis = $this->make('redis');
        $srtmp = [];
        $srtmp = $redis->smembers('srtmp_server');
        if (empty($srtmp)) {
            $tmp = $redis->get('rtmp_live');
            if (!empty($tmp)) {
                $srtmp[] = $tmp;
            }
        }
        return $srtmp;
    }

    /**
     * 根据主播，获取房间的下播地址
     * @param $rid
     * @return array
     */
    protected function getDownstreamRTMP()
    {
        $redis = $this->make('redis');

        // 取得房間 rtmp host & port
        $host = $redis->hget('hvediosKtv:' . $this->rid, 'rtmp_host') ?: "";
        $port = $redis->hget('hvediosKtv:' . $this->rid, 'rtmp_port') ?: "";
        $host_port = $host . ($port == '' ? '' : ':'.$port);
        if ($host == '') {
            return [];
        }
        $hls_only = $redis->hget('hvediosKtv:' . $this->rid, 'hls_only') ?: 0;

        // 取得 rtmp (含 application path)
        $srtmp = $redis->sMembers('srtmp_server');
        $rtmp_up = '';
        $rtmp_name = '';
        $cdn_id = '';
        foreach ($srtmp as $up) {
            if (strpos($up, $host_port) === false) {
                continue;
            }
            list($rtmp_up, $rtmp_name) = explode('@@', $up);

            if (strpos($rtmp_name, ':') !== false) {
                list($rtmp_name, $cdn_id) = explode(':', $rtmp_name);
            }
            break;
        }
        if ($rtmp_up == '') {
            return [];
        }

        // 取 auth params
        $sid = $redis->hget('hvedios_ktv_set:' . $this->rid, 'sid');
        $params = $this->getParams($cdn_id, $sid);

        $rtmp_down = $redis->smembers("srtmp_user:$rtmp_up");
        if (is_array($rtmp_down)) {
            $rtn['rtmp'] = explode('@@', $rtmp_down[0])[0] .'/'. $sid . $params['rtmp'];
        }

        $flv_down = $redis->smembers("srtmp_flv:$rtmp_up");
        if (is_array($flv_down) && !$hls_only) {
            $rtn['flv'] = str_replace('{SID}', $sid, $flv_down[0]) . $params['flv'];
        }

        $hls_down = $redis->smembers("srtmp_hls:$rtmp_up");
        if (is_array($hls_down)) {
            $rtn['hls'] = str_replace('{SID}', $sid, $hls_down[0]) . $params['hls'];
        }

        return $rtn;
    }

    /**
     * 获取cdn参数
     */
    public function getParams($cdn_id, $sid)
    {
        // 取 rtmp 鑑權設定
        $redis = $this->make('redis');
        $rtmpConf = $redis->hgetall('hrtmp_cdn:'. $cdn_id);
        $key = $rtmpConf['key'];
        $expireTime = isset($rtmpConf['expireTime']) ? intval($rtmpConf['expireTime'])/1000 : 300;

        switch ($cdn_id) {
            // 网宿
            case '1':
            case '2':
            case '3':
                $t = time(); // 此時間 +120 到 -240 之間表示有效
                $txSecret = md5($key .'/iev/'. $sid . $t);
                $protocols = [
                    'rtmp' => ['rtmp://', ''],
                    'flv' => ['http://', '.flv'],
                    'hls' => ['http://', '/playlist.m3u8'],
                ];
                $params = [];
                foreach ($protocols as $protocal => $data) {
                    list($protocol_prefix, $suffix) = $data;
                    $txSecret = md5($key .'/iev/' . $sid . $suffix . $t);
                    $qs = '?' . http_build_query([
                        'k' => $txSecret,
                        't' => $t,
                    ]);
                    $params[$protocal] = $qs;
                }
                break;

            // toffs => 費用太高，目前沒使用
            case '4':
            case '5':
            case '6':
                $param_arr = [];
                break;

            // 騰訊雲
            case '7':
            case '8':
            case '9':
                $t = time() + $expireTime;
                $txTime = strtoupper(base_convert($t, 10, 16));
                $txSecret = md5($key . $sid . $txTime);
                $param_arr = [
                    'txSecret' => $txSecret,
                    'txTime'   => $txTime
                ];
                $qs = '?'. http_build_query($param_arr);
                $params = [
                    'rtmp' => $qs,
                    'flv' => $qs,
                    'hls' => $qs,
                ];
                break;

            // 帝聯
            case '999-000':
                // 舊代碼裡面僅帝聯的邏輯內有加上 certi，因此把這段先留著參考
                $certi = $this->make("safeService")->getLcertificate("cdn");
                $ext = ['certi' => $certi];
                $param_arr = $this->getDilian($cdn_id); // deprecated!!
                $qs = '?'. http_build_query(array_merge($param_arr, $ext));
                $params = [
                    'rtmp' => $qs,
                ];
                break;

            default:
                $params = [
                    'rtmp' => '',
                    'flv' => '',
                    'hls' => '',
                ];
        }
        return $params;
    }

    /**
     * @deprecated deprecated since v2.18
     */
    private function getDilian($cdnID)
    {
        $redis = $this->make('redis');
        $rtmp_cdn = $redis->hgetall('hrtmp_cdn:'. $cdnID);
        $time = dechex(time());

        $k = hash('md5', $rtmp_cdn['key'] .'/iev'. $time);
        $param_arr = [
            'k' => $k,
            't' => $time
        ];
        return $param_arr;
    }
    /**
     * 白云山
     * @deprecated deprecated since v2.18
     * @return array
     */
    private function getBaiyunshan($cdnID)
    {
        $redis = $this->make('redis');
        $rtmp_cdn = $redis->hgetall('hrtmp_cdn:'. $cdnID);
        $time = dechex(time()+$rtmp_cdn['down_expire_sec']);
        $k = hash('md5', $rtmp_cdn['key'] .'/iev'. $time);
        $param_arr = [
            'sign' => $k,
            't' => $time
        ];
        return $param_arr;
    }

    /**
     * 星云
     * @deprecated deprecated since v2.18
     * @return array
     */
    private function getXinyun($cdnID)
    {
        $redis = $this->make('redis');
        $rtmp_cnd_key = $redis->hgetall('hrtmp_cdn:'. $cdnID);
        $time = time();
        $k = hash('md5', $rtmp_cnd_key['key'] . $time);
        $param_arr = [
            'k' => $k,
            'time' => $time
        ];
        return $param_arr;
    }
}
