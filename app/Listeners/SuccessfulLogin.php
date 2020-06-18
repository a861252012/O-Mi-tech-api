<?php

namespace App\Listeners;

use App\Events\Active;
use App\Events\Login;
use App\Models\UserLoginLog;
use App\Models\Users;
use App\Traits\Commons;
//use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SuccessfulLogin
{
    use Commons;
    /**
     * Create the event listener.
     *
     * @return void
     */
    private $request = null;

    public function __construct(Request $request)
    {
        //
        $this->request = $request;
    }

    /**
     * Handle the event.
     *
     * @param Active $event
     * @return void
     */
    public function handle(Login $event)
    {
        $user = $event->user;
        $origin = $event->origin;
        $login_ip = $this->getIp();
        $uid = $user->getAuthIdentifier();

        Users::query()->where('uid', $uid)->update([
            'last_ip' => $login_ip, // 最后登录ip TODO 大流量优化，目前没压力
            'logined' => date('Y-m-d H:i:s'),
        ]);

        //记录登录日志
        $this->loginLog($uid, $login_ip, $user->site_id, $origin, date('Y-m-d H:i:s'));

        // Log::info("test event:".$user->toJson());
    }

    //todo 增加scopes
    public function loginLog($uid, $login_ip, $site_id, $origin, $date)
    {
        return UserLoginLog::create([
            'uid'        => $uid,
            'ip'         => $login_ip,
            'site_id'    => $site_id,
            'origin'     => $origin,
            'created_at' => $date,
        ]);
    }
}
