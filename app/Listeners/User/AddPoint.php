<?php
/**
 * 用戶加鑽石 事件
 * @author Weine
 * @date 2020-9-14
 */
namespace App\Listeners\User;

use App\Events\RouletteReward;
use App\Facades\UserSer;
use App\Models\Users;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class AddPoint
{
    protected $user;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Users $user)
    {
        $this->user = $user;
    }

    /**
     * Handle the event.
     *
     * @param  RouletteReward  $event
     * @return void
     */
    public function handle(RouletteReward $event)
    {
        if ($event->rouletteHistory->type === 1) {
            $user = $this->user->find($event->user->uid);
            $user->increment('points', $event->rouletteHistory->amount);
            $user->save();

            UserSer::cacheUserInfo($event->user->uid);
        }
    }
}
