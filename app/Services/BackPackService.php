<?php
/**
 * 背包功能 服務
 * @date 2020/06/19
 */

namespace App\Services;

use App\Http\Resources\BackPack\BackPackResource;
use App\Repositories\UserItemRepository;
use App\Repositories\UserBuyGroupRepository;
use App\Repositories\LevelRichRepository;
use App\Services\User\UserService;
use App\Models\UserGroup;
use App\Services\Message\MessageService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Facades\SiteSer;
use DB;

class BackPackService
{
    protected $UserItemRepository;
    protected $userService;
    protected $messageService;
    protected $userBuyGroupRepository;
    protected $levelRichRepository;
    protected $userGroup;

    public function __construct(
        UserItemRepository $UserItemRepository,
        UserService $userService,
        MessageService $messageService,
        UserBuyGroupRepository $userBuyGroupRepository,
        LevelRichRepository $levelRichRepository,
        UserGroup $userGroup
    ) {
        $this->UserItemRepository = $UserItemRepository;
        $this->userService = $userService;
        $this->messageService = $messageService;
        $this->userBuyGroupRepository = $userBuyGroupRepository;
        $this->levelRichRepository = $levelRichRepository;
        $this->userGroup = $userGroup;
    }

    /* 取得背包物品列表 */
    public function getItemList()
    {
        return BackPackResource::collection($this->UserItemRepository->getUserBackPack(Auth::id()));
    }

    /**
     * 使用背包物品
     *
     * @param $id int 物品流水id
     * @return mixed
     */
    public function useItem($id)
    {
        $userItem = $this->UserItemRepository->getItemById($id);

        switch ($userItem->item->item_type) {
            case 1:
                $res = $this->useVip($id);
                break;
            default:
                $res['status'] = $this->UserItemRepository->updateItemStatus($id, 1);
        }
        return $res;
    }

    public function useVip($id)
    {
        //檢查用戶是否有貴族身份
        if (Auth::user()->vip) {
            return ['status' => 102, 'msg' => '目前已是贵族身份，无法使用喔！'];
        }

        $level = $this->levelRichRepository->getLevelByGid(30);

        DB::beginTransaction();

        $updateVip = [
            'vip'     => $level->level_id,
            'vip_end' => date('Y-m-d H:i:s', strtotime("+7 day")),
        ];
        //賦予用戶貴族身份
        $updateUser = $this->userService->updateUserInfo(Auth::id(), $updateVip);

        if (!$updateUser) {
            Log::error('異動用戶資料錯誤');
            DB::rollBack();

            return ['status' => 0, 'msg' => '使用失敗'];
        }

        //新增貴族開通紀錄
        $levelRich = unserialize($this->userGroup->where('gid', 30)->first()->system, ['allowed_classes' => false]);

        $record = array(
            'uid'        => Auth::id(),
            'gid'        => 30,
            'level_id'   => $level->level_id,
            'type'       => 4,//操作类型:1 开通,2保级,3赠送 4.首充好禮物-貴族體驗券
            'create_at'  => date("Y-m-d H:i:s"),
            'rid'        => 0,
            'status'     => 1,
            'end_time'   => $updateVip['vip_end'],
            'open_money' => $levelRich['open_money'],
            'keep_level' => $levelRich['keep_level'],
            'site_id'    => SiteSer::siteId(),
        );

        $insertGroupRecord = $this->userBuyGroupRepository->insertRecord($record);

        if (!$insertGroupRecord) {
            Log::error('新增貴族紀錄錯誤');
            DB::rollBack();
            return ['status' => 0, 'msg' => '使用失敗'];
        }

        $message = [
            'category'  => 1,
            'mail_type' => 3,
            'rec_uid'   => Auth::id(),
            'content'   => '亲爱的用户，您的白尊体验将从 ' . date('Y-m-d') . '至' .
                date('Y-m-d', strtotime($updateVip['vip_end'])),
            'site_id'   => SiteSer::siteId(),
        ];

        //新增開通守護的系統消息
        $insertMsgRecord = $this->messageService->sendSystemToUsersMessage($message);

        if (!$insertMsgRecord) {
            Log::error('新增系統訊息錯誤');
            DB::rollBack();
            return ['status' => 0, 'msg' => '使用失敗'];
        }

        //更改物品狀態為使用
        $res = $this->UserItemRepository->updateItemStatus($id, 1);

        if (!$res) {
            Log::error('更新物品狀態錯誤');
            DB::rollBack();
            return ['status' => 0, 'msg' => '使用失敗'];
        }

        DB::commit();

        return ['status' => 1, 'msg' => 'OK'];
    }
}
