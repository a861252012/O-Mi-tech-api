<?php

namespace App\Http\Controllers;


use App\Mail\PwdReset;
use App\Mail\SafeMailVerify;
use App\Models\Users;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\MessageBag;
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
            return JsonResponse::create(['status' => 0, 'msg' => '你已验证过安全邮箱！',]);
        }
        $email = $request->get('mail');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return JsonResponse::create(['status' => 0, 'msg' => '安全邮箱地址格式不正确',]);
        }
        if (Users::where('safemail', $email)->exists()) {
            return JsonResponse::create(['status' => 0, 'msg' => '安全邮件已被使用',]);
        }
        $mail = (new SafeMailVerify($user, $email));
        Mail::send($mail);

        return JsonResponse::create(['status' => 1, 'msg' => '发送成功！']);
    }


    /**
     * 验证安全邮箱连接
     */
    public function VerifySafeMail($token)
    {
        $token = Crypt::decrypt($token);
        $errors = new MessageBag();

        if ($token && time() > $token['time'] + 86400) {
            $errors->add('mail', '验证链接已过期！');
            return RedirectResponse::create('/member/mailverify/mailFail?' . http_build_query(['errors' => $errors->toJson()]));
        }

        $user = resolve(UserService::class)->getUserByUid($token['uid']);
        if ($user->safemail) {
            $errors->add('mail', '你已验证过安全邮箱！');
            return RedirectResponse::create('/member/mailverify/mailFail?' . http_build_query(['errors' => $errors->toJson()]));
        }

        //$getMailStatus =  $this->getDoctrine()->getManager()->getRepository('Video\ProjectBundle\Entity\VideoUser')->findOneBy(array('safemail' => $email));
        $getMailStatus = Users::where('safemail', $token['email'])->toJson();
        if ($getMailStatus) {
            $errors->add('mail', '对不起！该邮箱已绑定其他帐号！');
            return RedirectResponse::create('/member/mailverify/mailFail?' . http_build_query(['errors' => $errors->toJson()]));
        }
        if (!Users::where('uid', $token['uid'])->update(['safemail' => $token['email'], 'safemail_at' => date('Y-m-d H:i:s')])) {
            $errors->add('mail', '更新安全邮箱失败！');
            return RedirectResponse::create('/member/mailverify/mailFail?' . http_build_query(['errors' => $errors->toJson()]));
        }

        resolve(UserService::class)->getUserReset($token['uid']);
        //赠送砖石奖励
        //$this->addUserPoints($uid,500, array('date'=>date('Y-m-d H:i:s'),'pay_type'=>5 ,'nickname'=>$user['nickname']?:$user['username']), array('mailcontent'=>'你通过“安全邮箱验证”获得500钻石奖励！','date'=>date('Y-m-d H:i:s')), $dm);
        $errors->add('mail', '更新安全邮箱成功！');
        return RedirectResponse::create('/member/mailverify/mailSuccess?' . http_build_query(['errors' => $errors->toJson()]));
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
            return JsonResponse::create(['status' => 0, 'msg' => '邮箱格式不正确']);
        }

        $user = Users::where('safemail', $mail)->first();
        if (!$user) {
            return JsonResponse::create(['status' => 0, 'msg' => '该邮箱没有通过安全邮箱验证, 验证安全邮箱才能使用此功能。']);
        }
        $mail = new PwdReset($user);
        Mail::send($mail);
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
        $hash = md5($pwd);
        $reset = Users::where('uid', $uid)->update(['password' => $hash]);
        Redis::hset('huser_info:' . $uid, 'password', $hash);
        Redis::del('pwdreset.token:' . $token);
        return JsonResponse::create(['status' => 1, 'msg' => '密码修改成功']);
    }

}
