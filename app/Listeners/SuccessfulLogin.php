<?php

namespace App\Listeners;

use App\Events\Active;
use App\Events\Login;
use App\Facades\UserSer;
use App\Models\UserLoginLog;
use App\Models\Users;
use App\Traits\Commons;
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
            'origin'  => $origin,
        ]);

        //標記當天登入過 (之後手機用戶進入直播間就不會再更新)
        UserSer::markTodayLogin($uid);

        //记录登录日志
        $this->loginLog($uid, $login_ip, $user->site_id, $origin, date('Y-m-d H:i:s'));
    }

    //todo 增加scopes
    public function loginLog($uid, $login_ip, $site_id, $origin, $date)
    {
        $data = [
            'uid'        => (int)$uid,
            'ip'         => $login_ip,
            'site_id'    => (int)$site_id,
            'origin'     => (int)$origin,
            'created_at' => $date,
        ];
        UserLoginLog::create($data);

        unset($data['created_at']);
        $data['type'] = 'login';
        $data['dt'] = date('Y-m-d');
        $data['ts'] = time();
        $data['ua'] = $this->request->server('HTTP_USER_AGENT');
        Log::channel('login')->info(null, $data);
    }
}
