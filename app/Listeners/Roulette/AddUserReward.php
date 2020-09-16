<?php
/**
 * 發放用戶獎勵 事件
 * @author Weine
 * @date 2020-9-16
 */
namespace App\Listeners\Roulette;

use App\Events\RouletteReward;
use App\Facades\UserSer;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class AddUserReward
{
    // 加鑽石獎勵類型
    const ADD_POINT_TYPE = 1;

    // 加經驗獎勵類型
    const ADD_EXP_TYPE = 2;

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
     * @param  RouletteReward  $event
     * @return void
     */
    public function handle(RouletteReward $event)
    {
        try {
            // 獎項依類型歸類
            $reward = collect($event->reward)->mapToGroups(function ($item, $key) {
                return [$item['type'] => $item['amount']];
            });

            // 發放獎勵
            foreach ($reward as $type => $v) {
                //加鑽
                if ($type === self::ADD_POINT_TYPE) {
                    UserSer::updateUserInfo($event->user->uid, ['points' => $event->user->points + $v->sum()]);
                }

                //加經驗
                if ($type === self::ADD_EXP_TYPE) {
                    UserSer::updateUserInfo($event->user->uid, $data = ['exp' => $event->user->exp + $v->sum()]);
                }

                // 加物品

            }
        } catch (\Exception $e) {
            report($e);
            Log::error('發放中獎用戶輪盤獎勵錯誤!');
        }
    }
}
