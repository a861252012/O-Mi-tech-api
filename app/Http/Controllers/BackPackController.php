<?php
/**
 * 背包功能
 * @apiDefine UserItem 用戶背包
 */

namespace App\Http\Controllers;

use App\Services\BackPackService;

class BackPackController extends Controller
{
    protected $backPackService;

    public function __construct(BackPackService $backPackService)
    {
        $this->backPackService = $backPackService;
    }

    /**
     * @api {get} /user/items 取得用戶背包物品列表
     * @apiGroup UserItem
     * @apiName 取得用戶背包物品列表
     * @apiVersion 1.0.0
     *
     * @apiError (Error Status) 999 API執行錯誤
     *
     * @apiSuccess {Int} status 開通執行狀態(1為開通成功,1以外為執行失敗)
     * @apiSuccess {String} msg 執行結果敘述
     *
     * @apiSuccess {Int} id 流水號
     * @apiSuccess {String} item_id 商品id
     * @apiSuccess {String} item_name 商品名稱
     * @apiSuccess {Int} frontend_mode 前端處理模式(0:不使用,1:需確認,2:直播間內使用,3:飛屏券)
     *
     * @apiSuccessExample {json} 成功回應
     * {
     * "status": 1,
     * "msg": "OK",
     * "data": [
     * {
     * "id": 1,
     * "item_id": "G001",
     * "uid": 9493318,
     * "item_type": 1,
     * "item_name": "贵族体验券 白尊+7天",
     * "frontend_mode": 1
     * }
     * ]
     * }
     */
    public function getItemList()
    {
        try {
            $data = $this->backPackService->getItemList();

            $this->setStatus(1, 'OK');
            $this->setRootData('data', $data);

            return $this->jsonOutput();
        } catch (\Exception $e) {
            report($e);
            $this->setStatus(999, 'api執行失敗');
            return $this->jsonOutput();
        }
    }

    /**
     * @api {get} /user/item/use/:id 使用背包物品
     * @apiGroup UserItem
     * @apiName 使用背包物品
     * @apiVersion 1.0.0
     *
     * @apiParam {Int} id 商品流水號
     *
     * @apiError (Error Status) 999 API執行錯誤
     * @apiError (Error Status) 0 使用失敗
     */
    public function useItem($id)
    {
        try {
            $res = $this->backPackService->useItem($id);
            if ($res) {
                $this->setStatus(1, 'OK');
            } else {
                $this->setStatus(0, '使用失敗');
            }
            return $this->jsonOutput();
        } catch (\Exception $e) {
            report($e);
            $this->setStatus(999, 'API執行錯誤');
            return $this->jsonOutput();
        }
    }
}
