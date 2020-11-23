<?php
/**
 * 金流相關資料交換 控制器
 * @author Weine
 * @date 2020-11-13
 * @apiDefine Transfer 金流相關資料交換
 */
namespace App\Http\Controllers\v1;

use App\Facades\UserSer;
use App\Http\Requests\v1\TransferDeposit;
use App\Services\TransferService;
use App\Services\VpubService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TransferController extends Controller
{
    protected $vpubService;
    protected $transferService;

    public function __construct(VpubService $vpubService, TransferService $transferService)
    {
        $this->vpubService = $vpubService;
        $this->transferService = $transferService;
    }

    /**
     * @api {post} /api/v1/deposit 鑽石上分
     * @apiGroup Transfer
     * @apiName deposit
     *
     */
    public function deposit(TransferDeposit $request)
    {
        try {

            /* 設定合作站來源 */
            if (!$this->vpubService->setOrigin($request->origin)) {
                Log::error('來源不正確');
                $this->setStatus(101, '來源不正確');
                return $this->jsonOutput();
            }

            //驗證時間
            if (!$this->vpubService->checkTimestamp($request->timestamp)) {
                Log::error('時間戳記不正確');
                $this->setStatus(103, '時間戳記不正確');
                return $this->jsonOutput();
            }

            //驗證API KEY
            $apiKey = $this->vpubService->getApiKey();
            if (empty($apiKey)) {
                Log::error('查無API Key或尚未設置');
                $this->setStatus(102, '查無API Key或尚未設置');
                return $this->jsonOutput();
            }

            //驗證簽名
            if (!$this->vpubService->checkSignature($request->except('sign'), $request->sign, $apiKey)) {
                Log::error('簽名不正確');
                $this->setStatus(104, '簽名不正確');
                return $this->jsonOutput();
            }

            //驗證用戶
            $userInfo = $this->vpubService->checkUser($request->username);
            if (empty($userInfo)) {
                $userInfo = $this->vpubService->registerUser($request->username, $request->uuid);
            }

            if (empty($userInfo)) {
                Log::error('查無用戶資料或註冊失敗');
                $this->setStatus(105, '查無用戶資料或註冊失敗');
                return $this->jsonOutput();
            }

            // 鑽石轉移
            $points = $userInfo['points'] + $request->points;
            if (!UserSer::updateUserInfo($userInfo['uid'], ['points'=> $points])) {
                $this->transferService->addFailedLog($request->except('sign'));
                Log::error('鑽石轉移失敗');
                $this->setStatus(106, '鑽石轉移失敗');
                return $this->jsonOutput();
            }

            //成功寫入recharge紀錄
            $this->transferService->addSuccessLog($userInfo, $request->except('sign'));

            $this->setStatus(1, 'OK');
            return $this->jsonOutput();

        } catch (\Exception $e) {
            report($e);
            $this->setStatus(999, 'API執行錯誤，請洽技術人員');
            return $this->jsonOutput();
        }
    }
}
