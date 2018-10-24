<?php

namespace App\Http\Controllers;


use App\Facades\SiteSer;
use App\Http\Middleware\ThrottleRoutes;
use App\Mail\PwdReset;
use App\Mail\SafeMailVerify;
use App\Models\Users;
use App\Services\Site\SiteService;
use App\Services\User\UserService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\MessageBag;
use Mews\Captcha\Facades\Captcha;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class PasswordController
 * @package Video\ProjectBundle\Controller
 * @author  D.C
 */
class PasswordController extends Controller
{
    public function sendVerifyMail(Request $request)
    {
        $user = Auth::user();
        if ($user->safemail) {
            ThrottleRoutes::clear($request);
            return JsonResponse::create(['status' => 0, 'msg' => '你已验证过安全邮箱,不用再次验证！']);
        }
        $email = $request->get('mail');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ThrottleRoutes::clear($request);
            return JsonResponse::create(['status' => 0, 'msg' => '安全邮箱地址格式不正确']);
        }
        if (Users::where('safemail', $email)->exists()) {
            ThrottleRoutes::clear($request);
            return JsonResponse::create(['status' => 0, 'msg' => '此安全邮件已被使用']);
        }
        try {
            //todo
            $this->sendMail($user, $email, $this->request()->server('REQUEST_SCHEME') . '://' . $this->request()->server('HTTP_HOST'));

            //$mail = (new SafeMailVerify($user, $email, $this->request()->server('REQUEST_SCHEME') . '://' . $this->request()->server('HTTP_HOST')));
            //Mail::send($mail);
        } catch (Exception $e) {
            Log::error($e->getTraceAsString());
            return JsonResponse::create(['status' => 0, 'msg' => '发送失败！' . $e->getMessage()]);
        }

        return JsonResponse::create(['status' => 1, 'msg' => '发送成功！']);
    }

    /**
     * 经clark 决定取消原先正常的发邮件及队列功能 ，重写邮件发送及相关功能！
     * @param $user
     * @param $email
     * @param $basUrl
     */
    public function sendMail($user, $email, $basUrl)
    {
        $data = ['email' => $email];
        $token = Crypt::encrypt([
            'email' => $email,
            'uid' => $user->uid,
            'time' => time(),
        ]);
        $url = $basUrl . '/mailverify/confirm/' . $token;
        $name = $user['nickname'] ?: $user['username'];
        $url_html = '<a href="' . $url . '" target="_blank"  style="word-wrap: break-word;cursor:pointer;text-decoration:none;color:#0082cb">' . $url . '</a>';
        $emailtemplate = SiteSer::config('email');
        $content = $emailtemplate ? $emailtemplate ?? '' : '';
        $content = preg_replace([
            '/{{\s*name\s*}}/i',
            '/{{\s*url\s*}}/i',
            '/{{\s*date\s*}}/i',
        ], [
            $name,
            $url_html,
            date('Y年m月d日 H:i:s'),
        ], $content);

        //去掉邮件内容html标签反斜杠
        $content = stripslashes($content);
        Mail::send('emails.safeMailVerify', ['content' => $content], function ($message) use ($data) {
            $siteConfig = app(SiteService::class)->config();
            $siteName = $siteConfig->get('name');
            $subject = $siteName . '安全邮箱验证';
            $message->to($data['email'])->subject($subject);
        });
    }

    /**
     * 密码悠
     * @return bool|JsonResponse
     */
    public function changePwd()
    {
        $request = $this->make('request');
        $sCode = $this->make('request')->get('captcha');

        if (!Captcha::check($sCode)) {
            return new JsonResponse(array('status' => 0, 'msg' => '验证码错误'));;
        }
        return $this->doChangePwd($request);
    }

    /**
     * 验证安全邮箱连接
     */
    public function VerifySafeMail($token)
    {
        $token = Crypt::decrypt($token);
        $errors = new MessageBag();

        if ($token && time() > $token['time'] + 86400) {
            return JsonResponse::create(['status' => 0, 'msg' => '验证链接已过期！']);
        }

        $user = resolve(UserService::class)->getUserByUid($token['uid']);
        if ($user->safemail) {
            return JsonResponse::create(['status' => 0, 'msg' => '你已验证过安全邮箱！']);
        }

        //$getMailStatus =  $this->getDoctrine()->getManager()->getRepository('Video\ProjectBundle\Entity\VideoUser')->findOneBy(array('safemail' => $email));
        $getMailStatus = Users::query()->where('safemail', $token['email'])->exists();

        if ($getMailStatus) {
            return JsonResponse::create(['status' => 0, 'msg' => '对不起！该邮箱已绑定其他帐号！']);
        }
        if (!Users::where('uid', $token['uid'])->update(['safemail' => $token['email'], 'safemail_at' => date('Y-m-d H:i:s')])) {
            return JsonResponse::create(['status' => 0, 'msg' => '更新安全邮箱失败！']);
        }

        resolve(UserService::class)->getUserReset($token['uid']);
        //赠送砖石奖励
        //$this->addUserPoints($uid,500, array('date'=>date('Y-m-d H:i:s'),'pay_type'=>5 ,'nickname'=>$user['nickname']?:$user['username']), array('mailcontent'=>'你通过“安全邮箱验证”获得500钻石奖励！','date'=>date('Y-m-d H:i:s')), $dm);
        return JsonResponse::create(['status' => 1, 'msg' => '更新安全邮箱成功！']);
    }

    /**
     * 经clark 决定取消原先正常的发邮件及队列功能 ，重写改用普通的邮件发送功能！
     * @param $user
     */
    private function pwdreset($user)
    {
        $requestHost = request()->getSchemeAndHttpHost();
        $token = Crypt::encrypt([
            'uid' => $user->uid,
            't' => time(),
        ]);
        Redis::setex('pwdreset.token:' . $token, 30 * 60, 1);
        $url = $requestHost . '/pwdreset/verify?token=' . urlencode($token);

        $siteName = SiteSer::config('name');
        Mail::send('emails.pwdreset', [
            'username' => $user->username,
            'siteName' => $siteName,
            'url' => $url,
            'date' => date('Y-m-d H:i:s'),
        ], function ($message) use ($user) {
            $siteConfig = app(SiteService::class)->config();

            $mail_from = $siteConfig->get('mail_from', config('mail.from.address'));
            $siteName = $siteConfig->get('name');

            $subject = $siteName . '密码重置';
            $mail_reply_to = $siteConfig->get('$mail_reply_to');
            $message->to($user->safemail);
            $mail_reply_to && $message->replyTo($mail_reply_to, $siteName);
            $message->subject($subject)->from($mail_from, $siteName);
        });
    }

    /**
     * 安全邮箱找回密码
     * @return Render
     * @author Young,D.C
     */
    public function getPwd()
    {
        if (Auth::check()) {
            return new RedirectResponse('/');
        }
        return $this->render('Password/getpwd');
    }

    /**
     * 安全邮箱找回密码成功
     * @author Nicholas
     */
    public function pwdResetSubmit(Request $request)
    {
        $mail = $request->get('email');
        if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            ThrottleRoutes::clear($request);
            return JsonResponse::create(['status' => 0, 'msg' => '邮箱格式不正确']);
        }

        $user = Users::where('safemail', $mail)->first();
        if (!$user) {
            ThrottleRoutes::clear($request);
            return JsonResponse::create(['status' => 0, 'msg' => '该邮箱没有通过安全邮箱验证, 验证安全邮箱才能使用此功能。']);
        }
        try {

            $mail = $this->pwdreset($user);
            //$mail = new PwdReset($user);
            //Mail::send($mail);
        } catch (Exception $e) {
            Log::error($e->getTraceAsString());
            return JsonResponse::create(['status' => 0, 'msg' => '发送失败！' . $e->getMessage()]);
        }
        return JsonResponse::create(['status' => 1]);
    }

    public function pwdResetConfirm(Request $request)
    {
        $token = $request->get('pwdreset_token');
        if (!$token || !Redis::exists('pwdreset.token:' . $token)) {
            return JsonResponse::create(['status' => 0, 'msg' => '链接已过期']);
        }
        $tokenData = Crypt::decrypt($token);
        if (empty($uid = $tokenData['uid'])) {
            return JsonResponse::create(['status' => 0, 'msg' => '链接已过期']);
        }
        $pwd = $request->get('password');
        $pwd_confirm = $request->get('password_confirmation');
        if (strlen($pwd) < 6) {
            return JsonResponse::create(['status' => 0, 'msg' => '密码格式无效！']);
        }
        if ($pwd !== $pwd_confirm) {
            return JsonResponse::create(['status' => 0, 'msg' => '两次输入的密码不一致']);
        }
        $pwd = $this->decode($pwd);
        $hash = md5($pwd);
        Users::where('uid', $uid)->update(['password' => $hash]);
        Redis::hset('huser_info:' . $uid, 'password', $hash);
        Redis::del('pwdreset.token:' . $token);
        return JsonResponse::create(['status' => 1, 'msg' => '密码修改成功']);
    }

}
