<?php
/**
 * 推廣獎勵收件夾通知訊息
 * @author Weine
 * @date 2020-04-01
 */
namespace App\Listeners;

use App\Entities\UserShare;
use App\Events\ShareUser;
use App\Services\Message\MessageService;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ShareRewardNotification
{
    protected $messageService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(ShareUser $event)
    {
        $user = UserShare::where('uid', $event->user->uid)->first();

        $data = [
            'rec_uid' => $user->share_uid,
            'content' => "恭喜你邀请的用户 ID {$event->user->uid}，完成了手机验证。获得了5个钻石奖励。",
        ];

        if (empty($this->messageService->sendSystemToUsersMessage($data))) {
            info("發送推廣獎勵通知失敗");
        } else {
            info("已發送推廣獎勵通知至用户({$user->share_uid})");
        }
    }
}
