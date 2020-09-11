<?php
/**
 * @apiDefine User 使用者相關功能
 */
namespace App\Http\Controllers;

use App\Http\Requests\User\UserSetLocale;
use App\Models\Users;
use App\Models\UserExtends;
use App\Services\Message\MessageService;
use App\Services\User\UserService;
use App\Services\UserAttrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class UserController extends Controller
{
    protected $userAttrService;

    public function __construct(UserAttrService $userAttrService)
    {
        $this->userAttrService = $userAttrService;
    }

    /**
     * @api {get} /user/check?rid={rid} 檢查用戶是否可停留
     * @apiGroup User
     * @apiName check
     * @apiVersion 1.0.0
     *
     * @apiParam {Int} rid 帶入主播房間號
     *
     * @apiSuccessExample 主播不需要跳轉
     * {
     *     "status": 1,
     *     "data": {
     *         "redirectUrl": "",
     *     }
     * }
     * @apiSuccessExample 用戶未登入
     * {
     *     "status": 1,
     *     "data": {
     *         "redirectUrl": "/?type=signup&rid=9123456",
     *     }
     * }
     * @apiSuccessExample 主播使用了加密線路
     * {
     *     "status": 1,
     *     "data": {
     *         "redirectUrl": "/download",
     *     }
     * }
     *
     */
    public function check()
    {
        $user = Auth::user();
        $rid = intval(Input::get('rid', 0));

        // 未登入，導向首頁登入
        if (!$user) {
            return JsonResponse::create([
                'status' => 1,
                'data' => [
                    'redirectUrl' => '/?type=signup&rid='. $rid,
                ],
            ]);
        }


        // 一般用戶，且主播使用了加密線路，導到下載頁
        if ($rid != $user->uid) {
            $rtmpServer = Redis::hget('hvediosKtv:'. $rid, 'rtmp_server');
            $isObfuscate = Redis::get('rtmp_obfuscate:'. $rtmpServer);
            if ($isObfuscate == '1') {
                return JsonResponse::create([
                    'status' => 1,
                    'data' => [
                        'redirectUrl' => '/download',
                    ],
                ]);
            }
        }

        // 其他情況可留在直播間瀏覽或上播
        return JsonResponse::create([
            'status' => 1,
            'data' => [
                'redirectUrl' => '',
            ],
        ]);
    }

    public function getCurrentUser()
    {
        if (Auth::check()) {
            // 通过用户服务去获取
            /** @var Users $user */
            $user = Auth::user();
            // 格式化用户信息 过滤掉用户的密码之类的敏感信息
            $userInfo = collect($this->getOutputUser($user));
            $userInfo->put('points', $user->points);
            if (resolve(UserService::class)->getUserHiddenPermission($userInfo)) {
                $userInfo['hidden'] = $user['hidden'];
            }
            // 获取用户等级提升还需要的级别
            $levelInfo = $this->getLevelByRole($user);
            $userInfo = $userInfo->union($levelInfo);

            $userInfo['mails'] = resolve(MessageService::class)->getMessageNotReadCount($user['uid'], $user['lv_rich']);

            // 是贵族才验证 下掉贵族状态
            if ($user['vip'] && ($user['vip_end'] < date('Y-m-d H:i:s'))) {
                resolve(UserService::class)->cancelVip(Auth::id());
                $userInfo['vip'] = 0;
                $userInfo['vip_end'] = '';
            }

            //20190218 UserExtends
            $uid = Auth::id();
            $userex = UserExtends::find($uid);
            if(!empty($userex['phone'])){
                $userInfo['phone']=$userex['phone'];
            }
            if(!empty($userex['qq'])){
                $userInfo['qq']=$userex['qq'];
            }

        }
        return JsonResponse::create(['status' => 1, 'data' => $userInfo??'']);
    }

    public function following()
    {
        return JsonResponse::create(Redis::zrevrange('zuser_attens:' . Auth::id(), 0, -1));
    }

    /**
     * @api {get} /user/set_hidden/:status 設定隱身
     * @apiGroup User
     * @apiName set_hidden
     * @apiVersion 1.0.0
     *
     * @apiParam {Int} [Status] 是否隱身(0:否/1:是)
     *
     * @apiError (Error Status) 999 API執行錯誤
     */
    public function setHidden($status = false)
    {
        try {
            $user = Auth::user();
            $user->hidden = $status;
            $user->save();

            Redis::del('huser_info:' . $user->uid);

            $this->setStatus(1, __('messages.success'));
            return $this->jsonOutput();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $this->setStatus(999, __('messages.apiError'));
            return $this->jsonOutput();
        }
    }

    /**
     * @api {post} /user/locale 設定用戶語系
     * @apiGroup User
     * @apiName set_locale
     * @apiVersion 1.0.0
     *
     * @apiParam {String} [loc] 語系
     *
     * @apiError (Error Status) 999 API執行錯誤
     */
    public function setLocale(UserSetLocale $request)
    {
        try {
            $locale = $request->loc ?? 'zh';
            $this->userAttrService->set(Auth::id(), 'locale', $locale);
            $this->setStatus(1, __('messages.success'));
            return $this->jsonOutput();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $this->setStatus('999', __('messages.apiError'));
            return $this->jsonOutput();
        }
    }
}
