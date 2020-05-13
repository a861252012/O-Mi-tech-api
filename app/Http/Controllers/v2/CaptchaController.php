<?php
/**
 * Captcha圖片驗證
 * @author Weine
 * @date 2020-03-03
 * @apiDefine Captcha
 */

namespace App\Http\Controllers\v2;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Mews\Captcha\Facades\Captcha;

class CaptchaController extends Controller
{

    /* 有效時間(分鐘) */
    const EXPIRE_TIME = 6;

    const PRESHARED_KEY = '2fdb390824b7aaeaec9e5557dbbfaec1';

    /* HTTP錯誤代碼  */
    const HTTP_ERROR_401 = '未授权的请求，请重啟应用后再尝试';
    const HTTP_ERROR_403 = '拒绝存取';
    const HTTP_ERROR_410 = '验证码已逾期，请重啟应用后再尝试';

    private function _httpError($code)
    {
        $msg = [
            'msg' => constant('self::HTTP_ERROR_' . $code)
        ];

        $header = [
            'Content-Type' => 'json',
            'Captcha-Message' => $msg['msg'],
        ];

        return Response::make($msg, $code, $header);
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

    /**
     * @api {get} /v2/captcha/:cKey?k= 取得驗證圖片
     * @apiGroup Captcha
     * @apiName 取得驗證圖片
     * @apiVersion 2.0.0
     *
     * @apiParam {String} cKey
     * @apiParam {String} k
     */
    public function index($cKey = null)
    {
        try {
            $c = explode('.', $cKey);

            if (empty($this->vaildateTime($c[0]))) {
                return $this->_httpError(410);
            }

            if (empty($this->vaildateClient($c[1]))) {
                return $this->_httpError(401);
            }

            if (empty($this->vaildateK())) {
                return $this->_httpError(403);
            }

            return Captcha::create()->header(session()->getName(), session()->getId(), true);
        } catch (\Exception $e) {
            report($e);
        }
    }
}
