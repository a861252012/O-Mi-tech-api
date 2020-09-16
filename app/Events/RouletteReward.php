<?php
/**
 * 輪盤遊戲中獎 事件
 * @author Weine
 * @date 2020-9-14
 */

namespace App\Events;

use App\Entities\RouletteHistory;
use App\Models\Users;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class RouletteReward
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $reward;
    public $rid;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Users $user, $reward, $rid)
    {
        $this->user = $user;
        $this->reward = $reward;
        $this->rid = $rid;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
