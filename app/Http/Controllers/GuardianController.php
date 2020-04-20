<?php
/**
 * 守護功能 控制器
 * @author Weine
 * @date 2020/02/15
 * @apiDefine Guardian 守護功能
 */

namespace App\Http\Controllers;

use App\Facades\SiteSer;
use App\Http\Requests\Guardian\GuardianBuy;
use App\Models\Users;
use App\Entities\Guardian;
use App\Services\GuardianService;
use App\Services\Message\MessageService;
use App\Services\User\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Auth;
use DB;


class GuardianController extends Controller
{
    const VALID_DAY_ARR = array(1 => 30, 2 => 90, 3 => 365);
    const GUARDIAN_GIFT_ID = array(1 => 700004, 2 => 700005, 3 => 700006);
    const PAY_TYPE_CH = array(1 => '开通成功', 2 => '续费成功');
    const PAY_TYPE_EN = array(1 => 'activate', 2 => 'renewal');

    protected $guardianService;

    protected $messageService;

    public function __construct(
        GuardianService $guardianService,
        MessageService $messageService,
        UserService $userService
    ) {
        $this->guardianService = $guardianService;
        $this->messageService = $messageService;
        $this->userService = $userService;
    }

    /**
     * @api {get} /guardian/get_setting 取得守護功能設定資訊
     * @apiGroup Guardian
     * @apiName 守護功能設定資訊
     * @apiVersion 1.0.0
     *
     * @apiError (Error Status) 999 API執行錯誤
     *
     * @apiSuccess {String} faq FAQ連結
     * @apiSuccess {Array} guard_settings 守護設定
     * @apiSuccess {Int} id 守護ID
     * @apiSuccess {String} name 守護名稱
     * @apiSuccess {Object[]} activate 開通費用設定
     * @apiSuccess {Array} activate.days 天數(30天/90天/365天)
     * @apiSuccess {Array} activate.sale 優惠價格
     * @apiSuccess {Array} activate.price 一般價格
     * @apiSuccess {Object} renewal 續費設定(內容格式與開通相同)
     * @apiSuccess {Int} activate_notify 開通通知(0:關/1:房間通知/2:全站通知)
     * @apiSuccess {Boolean} welcome_notify 進場歡迎通知(0:關/1:開)
     * @apiSuccess {Boolean} shot_border 頭像邊框(0:關/1:開)
     * @apiSuccess {Boolean} rename 改名限制(0:不可修改/1:可修改)
     * @apiSuccess {Int} rename_limit 改名限制次數
     * @apiSuccess {Boolean} feiping 專屬飛頻(0:關/1:開)
     * @apiSuccess {Int} feiping_count 贈送飛頻數
     * @apiSuccess {Boolean} chat_bg 聊天底圖(0:關/1:開)
     * @apiSuccess {Boolean} chat_limit 聊天文字限制開關(0:關/1:開)
     * @apiSuccess {Int} chat_freq_limit 聊天文字時間限制(0:不限制)
     * @apiSuccess {Int} chat_length_limit 聊天文字長度限制(0:不限制)
     * @apiSuccess {Boolean} forbid 防禁言(0:關/1:開)
     * @apiSuccess {Int} forbid_count 禁言用戶數
     * @apiSuccess {Boolean} kick 防踢人(0:關/1:開)
     * @apiSuccess {Int} kick_count 踢人數
     * @apiSuccess {Int} show_discount 看秀折扣(%)
     * @apiSuccess {Boolean} hidden 是否允許隱身(0:關/1:開)
     *
     * @apiSuccessExample {json} 成功回應
     * {
     * "status": 1,
     * "msg": "成功",
     * "data": {
     * "faq": "http:\/\/localhost\/99\/static\/faq\/guardian.html",
     * "guard_settiings": [
     * {
     * "id": 2,
     * "name": "紫色守护",
     * "activate": {
     * "days": [
     * 30,
     * 90,
     * 365
     * ],
     * "sale": [
     * 10,
     * 100,
     * 300
     * ],
     * "price": [
     * 20,
     * 200,
     * 500
     * ]
     * },
     * "renewal": {
     * "days": [
     * 30,
     * 90,
     * 365
     * ],
     * "sale": [
     * 10,
     * 100,
     * 300
     * ],
     * "price": [
     * 20,
     * 200,
     * 500
     * ]
     * },
     * "activate_notify": false,
     * "room_notify": false,
     * "all_notify": false,
     * "welcome_notify": false,
     * "shot_border": false,
     * "rename": false,
     * "rename_limit": 0,
     * "feiping": false,
     * "feiping_count": 0,
     * "chat_bg": false,
     * "chat_limit": true,
     * "chat_freq_limit": 0,
     * "chat_length_limit": 0,
     * "forbid": false,
     * "forbid_count": 0,
     * "kick": false,
     * "kick_count": 0,
     * "show_discount": 0,
     * "hidden": false
     * }
     * ]
     * }
     * }
     */
    public function getSetting()
    {
        try {
            $data = $this->guardianService->getSetting();
            if (empty($data)) {
                $this->setStatus(0, '設定為空');
                return $this->jsonOutput();
            }

            $faq = SiteSer::config('cdn_host') . '/' . SiteSer::config('publish_version') . '/static/faq/guardian.html';

            $this->setStatus(1, '成功');
            $this->setData('faq', $faq);
            $this->setData('guard_settiings', $data);
            return $this->jsonOutput();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $this->setStatus(999, 'api執行失敗');
            return $this->jsonOutput();
        }
    }

    /**
     * @api {get} /guardian/my_info 我的守護權限
     * @apiGroup Guardian
     * @apiName 守護功能
     * @apiVersion 1.0.0
     *
     * @apiError (Error Status) 999 API執行錯誤
     *
     * @apiSuccess {String} faq FAQ連結
     * @apiSuccess {String} guard_id 守護id
     * @apiSuccess {Int} guardian_name 守護名稱
     * @apiSuccess {Int} last_activate_date 最後開通日
     * @apiSuccess {Int} last_renewal_date 最後續費日
     * @apiSuccess {String} expire_date 到期日
     * @apiSuccess {String} hidden 隱身狀態(0:關/1:開)
     * @apiSuccess {String} renewal_count 續費次數
     * @apiSuccess {Object[]} guardian_permission 守護特權
     * @apiSuccess {String} activate_notify 開通通知
     * @apiSuccess {String} welcome_notify 歡迎通知
     * @apiSuccess {String} shot_border 頭像邊框(0:關/1:開)
     * @apiSuccess {String} rename 改名限制(0:不可修改/1:可修改)
     * @apiSuccess {String} rename_limit 改名限制次數
     * @apiSuccess {String} feiping 專屬飛頻(0:關/1:開)
     * @apiSuccess {String} feiping_count 贈送飛頻數
     * @apiSuccess {String} chat_bg 聊天底圖(0:關/1:開)
     * @apiSuccess {String} chat_limit 聊天文字限制開關(0:關/1:開)
     * @apiSuccess {String} chat_freq_limit 聊天文字時間限制(0:不限制)
     * @apiSuccess {String} chat_length_limit 聊天文字長度限制(0:不限制)
     * @apiSuccess {String} forbid 防禁言(0:關/1:開)
     * @apiSuccess {String} forbid_count 禁言用戶數
     * @apiSuccess {String} kick 防踢人(0:關/1:開)
     * @apiSuccess {String} kick_count 踢人數
     * @apiSuccess {String} show_discount 看秀折扣(%)
     * @apiSuccess {String} hidden 是否允許隱身(0:關/1:開)
     *
     * @apiSuccessExample {json} 成功回應
     * {
     * "status": 1,
     * "msg": "成功",
     * "data": {
     * "faq": "http:\/\/localhost\/99\/static\/faq\/guardian.html",
     * "guard_id": 1,
     * "guardian_name": "黄色守护",
     * "last_activate_date": "2019-12-10",
     * "last_renewal_date": "2020-02-14",
     * "expire_date": "2020-03-14",
     * "hidden": 0,
     * "renewal_count": 2,
     * "guardian_permission": {
     * "activate_notify": 0,
     * "welcome_notify": 0,
     * "shot_border": 0,
     * "rename": 0,
     * "rename_limit": 0,
     * "feiping": 0,
     * "feiping_count": 0,
     * "chat_bg": 0,
     * "chat_limit": 0,
     * "chat_freq_limit": 0,
     * "chat_length_limit": 0,
     * "forbid": 0,
     * "forbid_count": 0,
     * "kick": 0,
     * "kick_count": 0,
     * "show_discount": 0,
     * "hidden": 0
     * }
     * }
     * }
     */
    public function myInfo()
    {
        try {
            $data = $this->guardianService->getMyInfo();

            $this->setStatus(1, '成功');
            $this->setRootData('data', $data);
            return $this->jsonOutput();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $this->setStatus(999, 'api執行失敗');
            return $this->jsonOutput();
        }
    }

    /**
     * @api {post} /guardian/buy 開通守護
     * @apiGroup Guardian
     * @apiName 開通守護
     * @apiVersion 1.0.0
     *
     * @apiParam {Int} rid (主播房間)，0或沒傳表示走房間外開通.
     * @apiParam {Int} uid (用戶id)，0或沒傳表示走房間外開通.
     * @apiParam {Int} guardId (開通守護等級(1黃、2紫、3黑)).
     * @apiParam {Int} payType (消費類型(1開通、2續費)).
     * @apiParam {Int} daysType (開通天數對應，1：30天，2：90天，3：365天，避免被串改，不直接傳天數)
     *
     * @apiError (Error Status) 999 API執行錯誤
     *
     * @apiError (Error Status) 101 欲開通之守護系統方案未開啟
     * @apiError (Error Status) 102 用戶鑽石不足,無法開通
     * @apiError (Error Status) 103 用戶现在的级别已大於要開通/續費的等级
     * @apiError (Error Status) 104 用戶已開通該級別守護，故僅能續費该等級守護
     * @apiError (Error Status) 105 用戶尚未開通該級別守護，故無法續費
     *
     * @apiSuccess {Int} status 開通執行狀態(1為開通成功,1以外為執行失敗)
     * @apiSuccess {String} msg 執行結果敘述
     *
     * @apiSuccess {Date} expireDate 守護到期日 (yyyy-mm-dd)
     * @apiSuccess {String} payTypeName 開通類型(续费成功,开通成功)
     * @apiSuccess {String} guardianName 守護類型(1 => '黄色守护', 2 => '紫色守护', 3 => '黑色守护')
     *
     * @apiSuccessExample {json} 成功回應
     * {
     * "status": 1,
     * "msg": "开通守护执行成功",
     * "data": {
     * "expireDate": "2037-07-09",
     * "guardianName": "紫色守护",
     * "payTypeName": "续费成功"
     * }
     * }
     */
    public function buy(GuardianBuy $request)
    {
        $data = $this->guardianService->purchaseProcess(
            Auth::user(),
            $request->payType,
            $request->daysType,
            $request->guardId,
            $request->rid);

        if ($data['status'] != 200) {
            $this->setStatus($data['status'], $data['msg']);
            return $this->jsonOutput();
        } else {
            unset($data['status']);
        }

        $this->setStatus(1, '开通守护执行成功');
        $this->setRootData('data', $data);

        return $this->jsonOutput();
    }

    /**
     * @api {get} /guardian/history 取得守護消費紀錄
     * @apiGroup Guardian
     * @apiName history
     * @apiVersion 1.0.0
     *
     * @apiSuccessExample {json} 成功回應
     *{
     * "status": 1,
     * "msg": "OK",
     * "data": {
     * "list": {
     * "current_page": 1,
     * "data": [
     * {
     * "id": 54,
     * "uid": 9493540,
     * "pay_date": "2020-03-13",
     * "valid_day": 30,
     * "price": 100,
     * "sale": 0,
     * "pay": 100,
     * "expire_date": "2020-04-13",
     * "guard_id": 1,
     * "pay_type": 1,
     * "created_at": "2020-03-13 17:16:30",
     * "updated_at": "2020-03-13 09:16:31"
     * },
     * {
     * "id": 55,
     * "uid": 9493540,
     * "pay_date": "2020-03-14",
     * "valid_day": 30,
     * "price": 100,
     * "sale": 0,
     * "pay": 100,
     * "expire_date": "2020-04-14",
     * "guard_id": 2,
     * "pay_type": 2,
     * "created_at": "2020-03-13 17:17:05",
     * "updated_at": "2020-03-13 09:17:05"
     * }
     * ],
     * "first_page_url": "http:\/\/localhost\/api\/m\/guardian\/history?page=1",
     * "from": 1,
     * "last_page": 1,
     * "last_page_url": "http:\/\/localhost\/api\/m\/guardian\/history?page=1",
     * "next_page_url": null,
     * "path": "http:\/\/localhost\/api\/m\/guardian\/history",
     * "per_page": 15,
     * "prev_page_url": null,
     * "to": 2,
     * "total": 2
     * },
     * "type": "guardian"
     * }
     * }
     */
    public function history()
    {
        try {
            $this->setStatus(1, 'OK');
            $this->setData('list', Auth::user()->guardian()->paginate());
            $this->setData('type', 'guardian');
            return $this->jsonOutput();
        } catch (\Exception $e) {
            report($e);
            $this->setStatus(999, 'api執行失敗');
            return $this->jsonOutput();
        }
    }
}
