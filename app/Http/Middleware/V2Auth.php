<?php
/**
 * API V2 Challenge-Response Auth
 * https://docs.google.com/document/d/13xvDaAj4lRudCKF4EwgYrWfBl74vxxxQB597t_wdxD8/edit#heading=h.uci36bvxs2xv
 *
 * @author Hunter
 * @date 2020-12-03
 */

namespace App\Http\Middleware;

use App\Traits\Commons;
use Closure;
use Illuminate\Support\Facades\Response;

class V2Auth
{
    use Commons;

    /* 有效時間(分鐘) */
    const EXPIRE_TIME = 6;
    const PRESHARED_KEY = '2fdb390824b7aaeaec9e5557dbbfaec1';

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $path = str_before(request()->getRequestUri(), '?');
        $cKey = last(explode('/', $path));
        if (strpos($cKey, '.') === false) {
            return $this->httpError(403);
        }

        $c = explode('.', $cKey);
        if (empty($this->vaildateTime($c[0]))) {
            return $this->httpError(410);
        }
        if (empty($this->vaildateClient($c[1]))) {
            return $this->httpError(401);
        }
        if (empty($this->vaildateK())) {
            return $this->httpError(403);
        }

        return $next($request);
    }

    /* 驗證c key timestamp */
    private function vaildateTime($c)
    {
        $cTime = hexdec($c);
        $now = time();
        $vaildTime = strtotime("+" . self::EXPIRE_TIME . " minutes", $cTime);
        /* 判斷現在時間是否超過有效期間 and c key時間不能超過現在時間 */
        if ($now <= $vaildTime && strtotime("+1 minute", $now) >= $cTime) {
            return true;
        }
        return false;
    }
    /* 驗證client ip */
    private function vaildateClient($c)
    {
        $clientIdentificationCode = substr(md5($this->getIp()), 1, 6);
        if ($c === $clientIdentificationCode) {
            return true;
        }
        return false;
    }
    /* 驗證k值 */
    private function vaildateK()
    {
        $k = md5(self::PRESHARED_KEY . str_before(request()->getRequestUri(), '?'));
        if ($k === request('k')) {
            return true;
        }

        return false;
    }

    private function httpError($code)
    {
        $msg = [
            'msg' => __("messages.V2Ath.HTTP_ERROR_{$code}")
        ];
        $header = [
            'Content-Type' => 'json',
            'Captcha-Message' => $msg['msg'],
        ];
        return Response::make($msg, $code, $header);
    }

    private function hint()
    {
        $path = str_before(request()->getRequestUri(), '?');
        $pathArr = explode('/', $path);
        $c = dechex(time()) . '.' . substr(md5($this->getIp()), 1, 6);
        $pathArr[count($pathArr) - 1] = $c;
        $path = join('/', $pathArr);
        $k = md5(self::PRESHARED_KEY . $path);

        $msg = [
            $path = $path,
            $k = $k,
            $q = $path .'?k='. $k,
        ];
        return Response::make($msg);
    }
}
