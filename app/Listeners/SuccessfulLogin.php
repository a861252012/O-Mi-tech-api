<?php

namespace App\Listeners;

use App\Events\Active;
use App\Models\UserLoginLog;
use App\Models\Users;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SuccessfulLogin
{
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
     * @param  Active  $event
     * @return void
     */
    public function handle(Login $event)
    {
        //
        $user = $event->user;
        $login_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        $uid = $user->getAuthIdentifier();

        $user = Users::query()->where('uid',$uid)->update([
            'last_ip'=>$login_ip, // 最后登录ip TODO 大流量优化，目前没压力
            'logined'=>date('Y-m-d H:i:s'),
        ]);
        //记录登录日志
        $this->loginLog($uid, $login_ip, date('Y-m-d H:i:s'));

       // Log::info("test event:".$user->toJson());

    }

    //todo 增加scopes
    public function loginLog($uid, $login_ip, $date)
    {
        return UserLoginLog::create([
            'uid' => $uid,
            'ip' => $login_ip,
            'created_at' => $date,
        ]);
    }
}
