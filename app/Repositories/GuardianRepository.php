<?php
/**
 * 守護用戶紀錄 資源庫
 * @author Weine
 * @date 2020/02/18
 */

namespace App\Repositories;

use App\Entities\Guardian;
use App\Entities\UserHost;
use App\Models\MallList;
use App\Models\Users;
use Illuminate\Support\Facades\DB;

class GuardianRepository
{
    protected $guardian;
    protected $users;

    public function __construct(Guardian $guardian, Users $users)
    {
        $this->guardian = $guardian;
        $this->users = $users;
    }

    public function getMy($id)
    {
        return $this->users->join('video_guardian_setting as a', 'a.id', '=', 'video_user.guard_id')
                ->join('video_guardian as b', 'b.uid', '=', 'video_user.uid')
                ->where('video_user.uid', $id)
                ->select('video_user.guard_id', 'video_user.hidden', 'a.name as guardian_name')
                ->selectRaw('MAX(b.expire_date) AS expire_date')
                ->selectRaw('MAX(IF(b.pay_type = 1, b.pay_date, "")) AS last_activate_date')
                ->selectRaw('MAX(IF(b.pay_type = 2, b.pay_date, "")) AS last_renewal_date')
                ->selectRaw('SUM(IF(b.pay_type = 2, 1, 0)) AS renewal_count')
                ->first();
    }


    /* 新增DB送禮紀錄 */
    public function insertGiftRecord($giftRecord = array())
    {
        if (!empty($giftRecord)) {
            return MallList::insert($giftRecord);
        }
    }

    /* 新增守護記錄 */
    public function insertGuardianRecord($guardianRecord = array())
    {
        if (!$guardianRecord['sale']) {
            unset($guardianRecord['sale']);
        }

        return Guardian::insert($guardianRecord);
    }

    /* 取得用戶守護大頭貼，房間內就撈主播海報(video_user_host)，房間外用官方固定的守護圖 */
    public function getHeadImg($rid)
    {
        $headimg = UserHost::where('id', $rid)->value('cover');

        return $headimg;
    }

    public function getHistory($uid, $start, $end)
    {
        return $this->guardian->join('video_guardian_setting as gs', 'gs.id', '=', 'video_guardian.guard_id')
            ->where('video_guardian.uid', $uid)
            ->whereBetween('video_guardian.created_at', [$start, $end])
            ->orderBy('video_guardian.created_at', 'desc')
            ->select('video_guardian.*', 'gs.name as guard_name')
            ->paginate();
    }
}