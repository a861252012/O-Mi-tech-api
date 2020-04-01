<?php
/**
 * 發送推廣獎勵
 * @author Weine
 * @date 2020-04-01
 */
namespace App\Listeners;

use App\Entities\UserShare;
use App\Events\ShareUser;
use App\Models\Users;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ShareReward
{
    const REWARD_POINTS = 5;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(ShareUser $event)
    {
        $user  = UserShare::where('uid', $event->user->uid)->first();
        $share = Users::find($user->share_uid);
        $share->points += self::REWARD_POINTS;
        $share->save();

        info($user->share_uid . " 推廣用戶獲得推廣獎勵 " . self::REWARD_POINTS . ' 鑽');
    }
}
