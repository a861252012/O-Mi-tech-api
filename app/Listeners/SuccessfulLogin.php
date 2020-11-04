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
use Jenssegers\Agent\Facades\Agent;

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
        info("什麼裝置? " . Agent::device());
        info("什麼作業系統? " . Agent::platform());
        info("瀏覽器名稱? " . Agent::browser());
        info("是否為桌上型裝置? " . (Agent::isDesktop() ? 'Yes' : 'No'));
        info("作業系統是否為 Windows? " . (Agent::is('Windows') ? 'Yes' : 'No'));
        info("是行動裝置? " . (Agent::isPhone() ? 'Yes' : 'No'));
        info("是否為 Android? " . (Agent::isAndroidOS() ? 'Yes' : 'No'));
        info("是否為 iPhone? " . (Agent::is('iPhone') ? 'Yes' : 'No'));

        $isRobot = Agent::isRobot();
        info("是機器人? " . ($isRobot ? 'Yes' : 'No'));
        info("機器人名稱? " . ($isRobot ? Agent::robot() : '無'));

        return UserLoginLog::create([
            'uid'        => $uid,
            'ip'         => $login_ip,
            'site_id'    => $site_id,
            'origin'     => $origin,
            'created_at' => $date,
        ]);
    }
}
