<?php

namespace App\Http\Controllers;


use App\Models\Conf;
use App\Models\Users;
use App\Services\RedisQueue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PasswordController
 * @package Video\ProjectBundle\Controller
 * @author  D.C
 */
class PasswordController extends Controller
{
    /** 验证邮频率限制 */
    const THROTTLE_MAIL_REG_KEY = 'throttle:mail.reg:';
    const THROTTLE_MAIL_REG_FREQ = 60;

    public function Forgot()
    {

        return $this->render('Password/forgot');
    }


    /**
     * 获取验证码输出图形
     * @return Response
     * @author D.C
     */
    public function CaptchaAction()
    {
        $captcha = new \Video\ProjectBundle\Service\Captcha\Captcha();
        $captcha->width = 90;
        $captcha->height = 28;
        $image = $captcha->Generate();
        $headers = [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="' . $image . '"'];
        $this->get('session')->set('CAPTCHA_KEY', $captcha->phrase);
        return new Response($captcha->phrase . '.png', 200, $headers);
    }

    /**
     * 邮箱验证
     * @return Render
     * @author Young, D.C
     */
    public function mailVerific()
    {
        $user = $this->userInfo;

        if ($user['safemail']) {
            return $this->render('Password/mailfail', ['error' => '你已验证过安全邮箱！']);
        }

        return $this->render('Password/mailverific', ['step' => 1]);
    }

    public function sendVerifyMail()
    {
        $user = Auth::user();
        if ($user->safemail) {
            return JsonResponse::create(['status' => 0, 'msg' => '你已验证过安全邮箱！',]);
        }
        $email = Request::get('mail');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return JsonResponse::create(['status' => 0, 'msg' => '安全邮箱地址格式不正确',]);
        }
        if (Users::where('safemail', $email)->exists()) {
            return JsonResponse::create(['status' => 0, 'msg' => '安全邮件已被使用',]);
        }

        $token = Crypt::encrypt([
            'email' => $email,
            'uid' => $user->uid,
            'time' => time(),
        ]);
        $tokenUrl =  Request::getSchemeAndHttpHost().'/api'.route('mail_verify_confirm', ['token' => $token],false);
        $name = $user['nickname'] ?: $user['username'];
        $date = date('Y-m-d H:i:s');
        $url = $tokenUrl;
        //$email = $this->getDoctrine()->getManager()->getRepository('Video\ProjectBundle\Entity\VideoConf')->findOneBy(array('name' => 'email'));
        $emailtemplate = Conf::where('name', 'email')->first(['value']);
        $content_t = $emailtemplate ? $emailtemplate['value'] : '';
        $content = '<div style="font-size:14px; margin:0 auto; border:1px solid #666; width:650px; padding: 40px 50px; line-height: 19px;">' . $content_t . '</div>';
        $url_html = '<a href="' . $url . '" target="_blank"  style="word-wrap: break-word;cursor:pointer;text-decoration:none;color:#0082cb">' . $url . '</a>';
        $template = str_replace(['{{name}}', '{{url}}', '{{date}}'], [$name, $url_html, $date], $content);
        //$template = $this->_getSafeEmailTemplate($name, $tokenUrl);
        //$mailer = $this->make('mail')->post($mail, $this->container->config['config.VERIFY_FROM_MAIL'], '蜜桃儿安全邮箱验证', $template);

        $body = $template;
        $subject = '蜜桃儿安全邮箱验证';

        //queue:mail:reg TODO
        $queue = RedisQueue::create(resolve('redis'), 'queue:mail:reg');

        $body_real = preg_replace([
            '/{{\s*name\s*}}/i',
            '/{{\s*url\s*}}/i',
            '/{{\s*date\s*}}/i',
        ], [
            $user['nickname'],
            $url_html,
            date('Y年m月d日 H:i:s'),
        ], $body);
        $queue->push([
            'id' => str_random(32),
            'nickname' => $user['nickname'],
            'email' => $email,
            'body' => $body_real,
            'subject' => $subject,
            'tries' => 2,//最大尝试次数
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return JsonResponse::create(['status' => 1, 'msg' => '发送成功！']);
    }


    /**
     * 验证安全邮箱连接
     * @param $token
     * @return Response
     * @author D.C
     *
     */
    public function VerifySafeMail($token)
    {
       $token = Crypt::decrypt($token);
        if (time() > $token['time'] + 86400) {
            return JsonResponse::create(['status'=>0,'msg' => '验证链接已过期！']);
        }

        $user = resolve('userService')->getUserByUid($token['uid']);
        if ($user->safemail) {
            return JsonResponse::create(['status'=>0,'msg' => '该用户已验证过安全邮箱！']);
        }

        //$getMailStatus =  $this->getDoctrine()->getManager()->getRepository('Video\ProjectBundle\Entity\VideoUser')->findOneBy(array('safemail' => $email));
        $getMailStatus = Users::where('safemail', $token['email'])->first();
        if ($getMailStatus) {
            return JsonResponse::create(['status'=>0,'msg' => '对不起！该邮箱已绑定其他帐号！']);
        }
        if (!Users::where('uid', $token['uid'])->update(['safemail' => $token['email'], 'safemail_at' => date('Y-m-d H:i:s')])) {
            return JsonResponse::create(['status'=>0,'msg'=>'更新安全邮箱失败！']);
        }

        $this->make('userServer')->getUserReset($token['uid']);
        //赠送砖石奖励
        //$this->addUserPoints($uid,500, array('date'=>date('Y-m-d H:i:s'),'pay_type'=>5 ,'nickname'=>$user['nickname']?:$user['username']), array('mailcontent'=>'你通过“安全邮箱验证”获得500钻石奖励！','date'=>date('Y-m-d H:i:s')), $dm);
        return JsonResponse::create(['status'=>1,'msg'=>'更新安全邮箱成功！']);
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
     * @return Render
     * @author Young,D.C
     */
    public function getPwdSuccess()
    {
        $mail = $this->make('request')->get('mail');
        if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            return $this->render('Password/mailfail', ['error' => '安全邮箱地址格式不正确']);
        }

//        $user = $this->getDoctrine()->getManager()->getRepository('Video\ProjectBundle\Entity\VideoUser')->findOneBy(array('safemail'=>$mail));
        $user = Users::where('safemail', $mail)->first();
        if (!$user) {
            return $this->render('Password/getpwdfail', ["error" => "该邮箱没有通过安全邮箱验证, 验证安全邮箱才能使用此功能。"]);
        }

        $time = time();
        $token = $this->make('Core\Des')->Encode($user->uid . '$' . $time . '$' . md5(md5($user->uid) . $time . '1room'), 'key@1ROOM');
        echo $tokenUrl = 'http://' . $this->make('request')->getHost() . '/resetpassword/' . $token;

        $mailer = $this->make('mail')->post($mail, $this->container->config['config.REPASS_FROM_MAIL'], '蜜桃儿重置用户密码', $this->_getPasswordEmailTemplate($user->username, $tokenUrl));

        if (!$mailer) {
            return $this->render('Password/mailfail', ['error' => '邮件发送失败请与客服联系！']);
        }

        return $this->render('Password/getpwdsuccess', ['msg' => '<h2>密码找回邮件已经发送到您的安全邮箱，请查收！</h2><p>如未收到邮件请在“垃圾箱”查找或重新发送密码找回邮件。<br/>点击邮件后请及时在“个人中心”中修改密码。</p>']);

    }

    /**
     * 重置密码功能
     * @param $token
     * @return Response
     * @author D.C
     */
    public function resetPassword($token)
    {

        if (!$token) {
            return $this->render('Password/getpwdfail', ['error' => '该链接无效！']);
        }

        list($uid, $time, $token) = explode('$', $this->make('Core\Des')->Decode($token, 'key@1ROOM'));
        if (time() > $time + 21600) {
            return $this->render('Password/getpwdfail', ['error' => '该链接已过期！']);
        }
        if (md5(md5($uid) . $time . '1room') != $token) {
            return $this->render('Password/getpwdfail', ['error' => '该链接无效！']);
        }


        if ($this->make('request')->getMethod() == 'POST') {
            $password1 = $this->make('request')->get('password1');
            $password2 = $this->make('request')->get('password2');

            if (strlen($password1) < 6 || strlen($password2) < 6) {
                return $this->render('Password/getpwdfail', ['error' => '密码格式无效！']);
            }

            if ($password1 != $password2) {
                return $this->render('Password/getpwdfail', ['error' => '两次输入密码不一致！']);
            }
            $reset = Users::where('uid', $uid)->update(['password' => md5($password1)]);
            if (!$reset) {
                return $this->render('Password/getpwdfail', ['error' => '找回密码失败更新错误！' . $reset]);
            }

            return $this->render('Password/getpwdsuccess', ['msg' => '<h2>密码修改成功,请返回首页登录！</h2>']);
        }
        return $this->render('Password/resetpwd', ['uid' => $uid]);
    }


    /**
     * @param $name
     * @param $url
     * @return string
     * @author      D.C
     * @update      2015-03-26
     * @description 生成安全邮箱验证邮件模板
     */
    private function _getSafeEmailTemplate($name, $url)
    {
        $date = date('Y-m-d H:i:s');
        $template = <<<EOF
 <table background="" width="700px" height="570px" style="font-size:14px;margin:0 auto;border:1px solid">
     <tr><td style="padding:10px 64px 10px;font-size:14px">你好，$name</td></tr>
     <tr><td style="padding:0px 64px;font-size:14px"> 您在蜜桃儿申请了验证安全邮箱，可以正常使用找回密码功能，保证您的账号安全。请点击以下链接完成您的邮箱验证：<br><a href="$url" target="_blank" style="cursor:pointer;text-decoration:none;color:#0082cb">$url</a><br>（如果点击链接没反应，请复制激活链接，粘贴到浏览器地址栏后访问）</td></tr>
     <tr><td style="padding:30px 64px 10px;color:#959393;font-size:14px">激活邮件24小时内有效，超过24小时请重新验证。<br>激活邮件将在您激活一次后失效。</td></tr>
     <tr><td style="padding:30px 0px 10px 400px;font-size:14px">蜜桃儿 1Room.cc<br>$date</td></tr>
     <tr><td style="padding:10px 60px 80px;border-top:1px solid #ededed;color:#959393;font-size:14px">如您错误的收到了此邮件，请不要点击激活按钮，该帐号将不会被启用。<br/>这是一封系统自动发出的邮件，请不要直接回复，如您有任何疑问，请联系客服</td></tr>
 </table>
EOF;
        return $template;
    }

    /**
     * @param $username
     * @param $url
     * @return string
     * @author      D.C
     * @update      2015-03-26
     * @description 生成找回密码邮件模板
     */
    private function _getPasswordEmailTemplate($username, $url)
    {
        $date = date('Y-m-d H:i:s');
        $template = <<<EOF
    <table background="" width="700px" height="570px" style="font-size:14px;margin:0 auto;border:1px solid">
     <tr><td style="padding:10px 64px 10px;font-size:14px">你好，$username</td></tr>
     <tr><td style="padding:0px 64px;font-size:14px">您在蜜桃儿进行了找回密码的操作，您的账号名为：$username;<br />请点击链接重置密码：<h4 style="color:#ff0000">$url</h4>请登录蜜桃儿官网，并及时在个人中心修改您的新密码。</td></tr>
     <tr><td style="padding:30px 0px 10px 400px;font-size:14px">蜜桃儿 1Room.cc<br>$date</td></tr>
     <tr><td style="padding:10px 60px 80px;border-top:1px solid #ededed;color:#959393;font-size:14px">如您错误的收到了此邮件，请不要点击激活按钮，该帐号将不会被启用。<br/>这是一封系统自动发出的邮件，请不要直接回复，如您有任何疑问，请联系客服</td></tr>
    </table>
EOF;
        return $template;
    }

}
