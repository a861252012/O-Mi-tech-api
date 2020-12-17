<?php
/**
 * Captcha圖片驗證
 * @author Weine
 * @date 2020-03-03
 * @apiDefine Captcha
 */

namespace App\Http\Controllers\v2;

use App\Traits\Commons;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Mews\Captcha\Facades\Captcha;

class CaptchaController extends Controller
{
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
        return Captcha::create()->header(session()->getName(), session()->getId(), true);
    }
}
