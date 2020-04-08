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
use App\Services\User\UserService;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ShareReward
{
    const REWARD_POINTS = 5;

    protected $userService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
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
        $user->is_mobile_match = 1;
        $user->match_date = date('Y-m-d');
        $user->save();

        $points = $this->userService->getUserInfo($user->share_uid, 'points');
        $this->userService->updateUserInfo($user->share_uid, ['points' => $points + self::REWARD_POINTS]);

        info($user->share_uid . " 推廣用戶獲得推廣獎勵 " . self::REWARD_POINTS . ' 鑽');
    }
}
