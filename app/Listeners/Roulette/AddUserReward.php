<?php
/**
 * 發放用戶輪盤獎勵 事件
 * @author Weine
 * @date 2020-9-15
 */
namespace App\Listeners\Roulette;

use App\Events\RouletteReward;
use App\Models\Users;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class AddUserReward
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
        //
    }
}
