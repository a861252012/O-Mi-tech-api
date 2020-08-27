<?php

namespace App\Services\User;

use App\Facades\SiteSer;
use App\Models\UserSignin;
use App\Models\UserSigninHistory;
use App\Services\Service;
use App\Services\User\UserService;
use Illuminate\Redis\RedisManager;

class SigninService extends Service
{
    protected $redis;
    public $enabled = false;
    public $checkDate = true;
    public $info = [];

    public function __construct(RedisManager $redis)
    {
        $this->redis = $redis;
        $this->info = json_decode(SiteSer::config('signin_info'), true);
        $this->enabled = isset($this->info['enabled']) ? $this->info['enabled'] : false;

        $skipCheck = SiteSer::config('skip_signin_check');
        if ($skipCheck === '1') {
            $this->checkDate = false;
        }
    }

    public function get($uid, $clientDate)
    {
        if (!$this->enabled) {
            $resp = [
                'status' => -1,
                'msg' => __('messages.Member.signin.close'),
            ];
            return $resp;
        }
        $userSignin = UserSignin::find($uid);
        $rtn = [
            'status' => 1,
            'data' => [
                'days' => 0,
                'today' => 0,
            ],
        ];
        if ($userSignin) {
            $userSignin->toarray();
            $todaySigned = $clientDate <= $userSignin['last_date'] ? 1 : 0;
            $yesterday = date('Y-m-d', strtotime($clientDate) - 86400);
            $continues = ($userSignin->last_date == $yesterday || $userSignin->last_date == $clientDate);
            $days = $continues ? $userSignin['days'] : 0;
            if ($days >= 7 && $userSignin->last_date == $yesterday) {
                $days = 0;
            }
            $rtn = [
                'status' => 1,
                'data' => [
                    'days' => $days,
                    'today' => $todaySigned,
                    '_ld' => $userSignin['last_date'],
                ],
            ];
        }
        return $rtn;
    }

    public function sign($uid, $clientDate)
    {
        if (!$this->enabled) {
            $resp = [
                'status' => 0,
                'msg' => __('messages.Member.signin.close'),
            ];
            return $resp;
        }

        $userSignin = UserSignin::find($uid);
        if (!$userSignin) {
            $userSignin = new UserSignin([
                'uid' => $uid,
            ]);
        }
        if ($userSignin->last_date >= $clientDate) {
            $resp = [
                'status' => 0,
                'msg' => __('messages.Member.signin.already_sign'),
            ];
            return $resp;
        }

        if ($this->checkDate) {
            $minDate = date('Y-m-d', time() - 43200);
            $maxDate = date('Y-m-d', time() + 43200);
            if ($clientDate < $minDate || $clientDate > $maxDate) {
                $resp = [
                    'status' => 0,
                    'msg' => __('messages.Member.signin.check_date'),
                ];
                return $resp;
            }
        }

        // success
        $yesterday = date('Y-m-d', strtotime($clientDate) - 86400);
        $continues = $userSignin->last_date == $yesterday;
        if ($continues) {
            $userSignin->days++;
            if ($userSignin->days >= 8) { // just in case
                $userSignin->days = 1;
            }
        } else {
            $userSignin->days = 1;
        }

        // give rewards
        $rewards = $this->info['rewards'][$userSignin->days - 1] ?: 0;
        $userService = resolve(UserService::class);
        $userPoints = $userService->getUserInfo($uid, 'points');
        $data = ['points' => $userPoints + $rewards];
        $userService->updateUserInfo($uid, $data);

        $userSignin->integration += $rewards;
        $userSignin->last_date = $clientDate;
        $userSignin->save();

        $history = new UserSigninHistory();
        $history->uid = $uid;
        $history->signin_date = $clientDate;
        $history->points = $rewards;
        $history->save();

        $rtn = [
            'status' => 1,
            'data' => [
                'points' => $rewards,
            ],
        ];
        return $rtn;
    }
}
