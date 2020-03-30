<?php
/**
 * 用戶推廣 控制器
 * @author Weine
 * @date 2020-03-25
 * @apiDefine Share 用戶推廣
 */
namespace App\Http\Controllers;

use App\Services\ShareService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShareController extends Controller
{
    protected $shareService;

    public function __construct(ShareService $shareService)
    {
        $this->shareService = $shareService;
    }

    /**
     * @api {post} /install_log 安裝資訊紀錄點
     * @apiGroup Share
     * @apiName install_log
     * @apiVersion 1.0.0
     *
     * @apiParam {Int} origin 來源
     * @apiParam {Int} site_id 站別ID
     *
     * @apiParamExample {json} Request-Example:
     * {
    "origin":22,
    "site_id":1
    }
     *
     * @apiError (Error Status) 999 API執行錯誤
     *
     * @apiSuccessExample {json} 成功回應
     * {
    "status": "1",
    "msg": "OK",
    "data": {}
    }
     */
    public function installLog(Request $request)
    {
        try {
            $this->shareService->addInstallLog($request->origin, $request->site_id);

            $this->setStatus('1','OK');
            return $this->jsonOutput();
        } catch (\Exception $e) {
            report($e);
            $this->setStatus('999', 'API執行錯誤');
            return $this->jsonOutput();
        }
    }

}
