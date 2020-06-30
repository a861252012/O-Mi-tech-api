<?php
/**
 * 背包功能 服務
 * @date 2020/06/19
 */

namespace App\Services;

use App\Http\Resources\BackPack\BackPackResource;
use App\Repositories\BackPackRepository;
use App\Services\User\UserService;
use App\Services\Message\MessageService;
use Illuminate\Support\Facades\Auth;
use App\Models\UserBuyGroup;

class BackPackService
{
    protected $backPackRepository;

    public function __construct(BackPackRepository $backPackRepository)
    {
        $this->backPackRepository = $backPackRepository;
    }

    /* 取得背包物品列表 */
    public function getItemList()
    {
        return BackPackResource::collection($this->backPackRepository->getUserBackPack(Auth::id()));
    }

    /**
     * 使用背包物品
     *
     * @param $id int 物品流水id
     * @param $status int 物品status 0為未使用,1為已使用
     * @return mixed
     */
    public function useItem($id, $status)
    {
        $item = $this->backPackRepository->getUserItem($id);
        $levelInfo = $this->backPackRepository->getLevel(30);

        if ($item['item_id'] === 'G003') {
            if (Auth::user()->vip) {
                return ['status' => 102, 'msg' => '您已经是贵族'];
            }
            $updateVip = [
                'vip'     => $levelInfo['level_id'],
                'vip_end' => date('Y-m-d H:i:s', strtotime("+8 day")),
            ];

            resolve(UserService::class)->updateUserInfo(Auth::id(), $updateVip);

            $this->backPackRepository->insertGroupRecord(30, $updateVip['vip_end']);

            $message = [
                'category'  => 1,
                'mail_type' => 3,
                'rec_uid'   => Auth::id(),
                'content'   => '亲爱的用户，您的白尊体验将从 ' . date('Y-m-d') . '至' .
                    date('Y-m-d', strtotime('-1 day', strtotime($updateVip['vip_end']))),
            ];

            resolve(MessageService::class)->sendSystemToUsersMessage($message);
        }
        $res = $this->backPackRepository->updateItemStatus($id, $status);

        if ($res) {
            return ['status' => 1, 'msg' => 'OK'];
        } else {
            return ['status' => 101, 'msg' => '物品ID有误'];
        }
    }
}
