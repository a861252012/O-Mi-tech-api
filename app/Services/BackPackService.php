<?php
/**
 * 背包功能 服務
 * @date 2020/06/19
 */

namespace App\Services;

use App\Http\Resources\BackPack\BackPackResource;
use App\Repositories\UserItemRepository;
use App\Repositories\UserBuyGroupRepository;
use App\Services\User\UserService;
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

    public function __construct(
        UserItemRepository $UserItemRepository,
        UserService $userService,
        MessageService $messageService,
        UserBuyGroupRepository $userBuyGroupRepository
    ) {
        $this->UserItemRepository = $UserItemRepository;
        $this->userService = $userService;
        $this->messageService = $messageService;
        $this->userBuyGroupRepository = $userBuyGroupRepository;
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
        $userItem = $this->UserItemRepository->getItemByGid($id);

        switch ($userItem->item->item_type) {
            case 1:
                $res = $this->useVip($id);
                break;
            default:
                $res = $this->updateItemStatus($id);
                break;
        }
        return $res;
    }

    public function useVip($id)
    {
        if (Auth::user()->vip) {
            return ['status' => 102, 'msg' => '您已经是贵族'];
        }

        $levelInfo = $this->UserItemRepository->getLevelByGid(30);

        DB::beginTransaction();

        $updateVip = [
            'vip'     => $levelInfo->level_id,
            'vip_end' => date('Y-m-d H:i:s', strtotime("+8 day")),
        ];

        $updateUser = $this->userService->updateUserInfo(Auth::id(), $updateVip);

        if (!$updateUser) {
            Log::error('異動用戶資料錯誤');
            DB::rollBack();
            return false;
        }

        $level = $this->UserItemRepository->getLevelByGid(30);

        $record = array(
            'uid'        => Auth::id(),
            'gid'        => 30,
            'level_id'   => $level->level_id,
            'type'       => 4,//操作类型:1 开通,2保级,3赠送 新增type 4:贵族体验券
            'create_at'  => date("Y-m-d H:i:s"),
            'rid'        => Auth::user()->rid ?? 0,
            'status'     => 1,
            'end_time'   => $updateVip['vip_end'],
            'open_money' => 0,
            'keep_level' => 1500,
            'site_id'    => SiteSer::siteId(),
        );

        $insertGroupRecord = $this->userBuyGroupRepository->insertRecord($record);

        if (!$insertGroupRecord) {
            Log::error('新增貴族紀錄錯誤');
            DB::rollBack();
            return false;
        }

        $message = [
            'category'  => 1,
            'mail_type' => 3,
            'rec_uid'   => Auth::id(),
            'content'   => '亲爱的用户，您的白尊体验将从 ' . date('Y-m-d') . '至' .
                date('Y-m-d', strtotime('-1 day', strtotime($updateVip['vip_end']))),
        ];

        $insertMsgRecord = $this->messageService->sendSystemToUsersMessage($message);

        if (!$insertMsgRecord) {
            Log::error('新增系統訊息錯誤');
            DB::rollBack();
            return false;
        }
        DB::commit();

        return $this->updateItemStatus($id);
    }

    public function updateItemStatus($id)
    {
        DB::beginTransaction();

        $res = $this->UserItemRepository->updateItemStatus($id, 1);

        if (!$res) {
            Log::error('更新物品狀態錯誤');
            DB::rollBack();
            return ['status' => 101, 'msg' => '物品ID有误'];
        }

        DB::commit();

        return ['status' => 1, 'msg' => 'OK'];
    }
}
