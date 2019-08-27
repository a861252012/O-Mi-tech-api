<?php

namespace App\Http\Controllers;


use App\Facades\SiteSer;
use App\Facades\UserSer;
use App\Http\Middleware\ThrottleRoutes;
use App\Mail\PwdReset;
use App\Mail\SafeMailVerify;
use App\Models\Users;
use App\Services\Service;
use App\Services\I18n\PhoneNumber;
use App\Services\Site\SiteService;
use App\Services\Sms\SmsService;
use App\Services\User\UserService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Mews\Captcha\Facades\Captcha;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Services\Email\HttpClient;
use App\Services\Email\SendCloud;
use App\Services\Email\AttachmentService;
use App\Services\Email\TemplateContentService;
use App\Services\Email\Mimetypes;


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
            $http_host = explode(':',$_SERVER['HTTP_HOST']);
            //$ishttps =   ($this->is_https() == TRUE )? "https":"http";
            $ishttps = $request->get('httpType');
            $url =  $this->sendMail($user, $email,$ishttps.'://'.$http_host[0]);

            //$mail = (new SafeMailVerify($user, $email, $this->request()->server('REQUEST_SCHEME') . '://' . $this->request()->server('HTTP_HOST')));
            //Mail::send($mail);
            $sendclound= SiteSer::config('sendclound');
            $sendfrom= SiteSer::config('sendfrom');
            $sendclound = json_decode($sendclound,true);

            $sendcloud=new SendCloud($sendclound['name'], $sendclound['pass'],'v2');
            $mail = resolve(AttachmentService::class);
            $mail->setFrom($sendfrom);
            $name = $user['nickname'] ?: $user['username'];
            $date =  date("Y-m-d H:i:s");
            $redis = $this->make('redis');
            $content = $redis->hGet("hsite_config:".SiteSer::siteId(), "email");
            $content = str_replace("{{name}}",$name,$content);
            $content = str_replace("{{url}}",$url,$content);
            $content = str_replace("{{date}}",$date,$content);

            $mail->setXsmtpApi(json_encode(array(
                'to'=>array($email),
                'sub'=>array(
                    '%title%'=>array('验证安全邮箱'),
                    '%content%'=>array($content),
                )


            )));
            // $mail->setSubject("MOBANCESHI");
            $mail->setRespEmailId(true);
            $templateContent=resolve(TemplateContentService::class);
            $templateContent->setTemplateInvokeName("test_template");

            $mail->setTemplateContent($templateContent);
            $sendcloud->sendTemplate($mail);

        } catch (Exception $e) {
            Log::error($e->getTraceAsString());
            return JsonResponse::create(['status' => 0, 'msg' => '发送失败！' . $e->getMessage()]);
        }

        return JsonResponse::create(['status' => 1, 'msg' => '发送成功！']);
    }
    /*
     * ISHTTPS
     */
    function is_https()
    {
        if ( ! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        {
            return TRUE;
        }
        elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        {
            return TRUE;
        }
        elseif ( ! empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off')
        {
            return TRUE;
        }

        return FALSE;
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
        return $url;

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


        $redis = $this->make('redis');
        $siteName = $redis->hGet("hsite_config:".SiteSer::siteId(), "name");
        $content = file_get_contents('../resources/views/emails/pwdreset.blade.php');

        $sendclound= SiteSer::config('sendclound');
        $sendfrom= SiteSer::config('sendfrom');
        $sendclound = json_decode($sendclound,true);

        $sendcloud=new SendCloud($sendclound['name'], $sendclound['pass'],'v2');
        $mail = resolve(AttachmentService::class);
        $mail->setFrom($sendfrom);
        $nickname = $user->nickname ?: $user->username;

        $date =  date("Y-m-d H:i:s");
        $redis = $this->make('redis');

        $content = str_replace('{{$siteName}}',$siteName,$content);
        $content = str_replace('{{$url}}',$url,$content);
        $content = str_replace('{{$date}}',$date,$content);
        $content = str_replace('{{$username}}',$nickname,$content);


        $mail->setXsmtpApi(json_encode(array(
            'to'=>array($user->safemail),
            'sub'=>array(
                '%title%'=>array('邮箱找回密码'),
                '%content%'=>array($content),
            )


        )));
        // $mail->setSubject("MOBANCESHI");
        $mail->setRespEmailId(true);
        $templateContent=resolve(TemplateContentService::class);
        $templateContent->setTemplateInvokeName("test_template");

        $mail->setTemplateContent($templateContent);
        $sendcloud->sendTemplate($mail);
        return JsonResponse::create(['status' => 1, 'msg' => '邮件发送成功']);exit;
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

    // 用手機號重置密碼
    public function pwdResetByMobile(Request $request)
    {
        $cc = $request->post('cc', '');
        $mobile = $request->post('mobile', '');
        $code = $request->post('code', '');
        if (empty($cc) || empty($mobile) || empty($code)) {
            return $this->msg('Invalid request');
        }
        $mobile = PhoneNumber::formatMobile($cc, $mobile);

        $result = SmsService::verify(SmsService::ACT_PWD_RESET, $cc, $mobile, $code);
        if ($result !== true) {
            return $this->msg($result);
        }
        ThrottleRoutes::clear($request);

        $cc_mobile = $cc.$mobile;
        $uid = UserSer::getUidByCCMobile($cc_mobile);

        $pwd = strtolower(Str::random(8));  // 長度不能隨意改變， SMS 模板是需要先審核過的
        $hash = md5($pwd);
        Users::where('uid', $uid)->update(['password' => $hash]);
        Redis::hset('huser_info:' . $uid, 'password', $hash);

        $result = SmsService::resetPwd($cc, $mobile, $pwd);
        if ($result !== true) {
            return $this->msg($result);
        }

        return JsonResponse::create(['status' => 1]);
    }

    // 手機發送重置密碼請求 - Step1
    public function pwdResetSendFromMobile(Request $request)
    {
        $sCode = $this->make('request')->get('captcha');
        if (!Captcha::check($sCode)) {
            return $this->msg('验证码错误');
        }

        $mail = $request->get('email');
        if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            ThrottleRoutes::clear($request);
            return $this->msg('邮箱格式不正确');
        }

        $user = Users::where('safemail', $mail)->first();
        if (!$user) {
            ThrottleRoutes::clear($request);
            return $this->msg('该邮箱没有通过安全邮箱验证, 验证安全邮箱才能使用此功能。');
        }

        // email code
        $code = strtolower(Str::random(4));
        Redis::setex('pwdreset.mtoken:' . md5($mail), 30 * 60, $code);

        // email
        $content = file_get_contents('../resources/views/emails/pwdreset-m-send.blade.php');
        $sendclound = SiteSer::config('sendclound');
        $sendfrom = SiteSer::config('sendfrom');
        $sendclound = json_decode($sendclound, true);

        $sendcloud = new SendCloud($sendclound['name'], $sendclound['pass'], 'v2');
        $mail = resolve(AttachmentService::class);
        $mail->setFrom($sendfrom);

        $content = str_replace('{{$siteName}}', $siteName, $content);
        $content = str_replace('{{$code}}', $code, $content);
        $content = str_replace('{{$date}}', date("Y-m-d H:i:s"), $content);
        $content = str_replace('{{$username}}', $user->nickname, $content);
        $mail->setXsmtpApi(json_encode(array(
            'to' => array($user->safemail),
            'sub' => array(
                '%title%' => array('邮箱找回密码'),
                '%content%' => array($content),
            )
        )));
        $mail->setRespEmailId(true);
        $templateContent=resolve(TemplateContentService::class);
        $templateContent->setTemplateInvokeName("test_template");

        $mail->setTemplateContent($templateContent);
        $sendcloud->sendTemplate($mail);
        return JsonResponse::create(['status' => 1, 'msg' => '邮箱验证码发送成功']);
    }

    // 手機發送重置密碼請求 - Step2
    public function pwdResetConfirmFromMobile(Request $request)
    {
        $mail = $request->get('email');
        if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            ThrottleRoutes::clear($request);
            return $this->msg('邮箱格式不正确');
        }

        $userCode = $request->post('code');
        $code = Redis::get('pwdreset.mtoken:' . md5($mail));
        if (empty($userCode) || $userCode != $code) {
            return $this->msg('邮箱验证码错误');
        }

        $user = Users::where('safemail', $mail)->first();
        if (!$user) {
            ThrottleRoutes::clear($request);
            return $this->msg('该邮箱没有通过安全邮箱验证, 验证安全邮箱才能使用此功能。');
        }

        // update password
        $uid = $user->uid;
        $pwd = strtolower(Str::random(8)); // 8~16 chars
        $hash = md5($pwd);
        Users::where('uid', $uid)->update(['password' => $hash]);
        Redis::hset('huser_info:' . $uid, 'password', $hash);

        // email
        $content = file_get_contents('../resources/views/emails/pwdreset-m-confirm.blade.php');
        $sendclound = SiteSer::config('sendclound');
        $sendfrom = SiteSer::config('sendfrom');
        $sendclound = json_decode($sendclound, true);

        $sendcloud = new SendCloud($sendclound['name'], $sendclound['pass'], 'v2');
        $mail = resolve(AttachmentService::class);
        $mail->setFrom($sendfrom);

        $content = str_replace('{{$siteName}}', $siteName, $content);
        $content = str_replace('{{$password}}', $pwd, $content);
        $content = str_replace('{{$date}}', date("Y-m-d H:i:s"), $content);
        $content = str_replace('{{$username}}', $user->nickname, $content);
        $mail->setXsmtpApi(json_encode(array(
            'to' => array($user->safemail),
            'sub' => array(
                '%title%' => array('邮箱找回密码'),
                '%content%' => array($content),
            )
        )));
        $mail->setRespEmailId(true);
        $templateContent=resolve(TemplateContentService::class);
        $templateContent->setTemplateInvokeName("test_template");

        $mail->setTemplateContent($templateContent);
        $sendcloud->sendTemplate($mail);
        return JsonResponse::create(['status' => 1, 'msg' => '邮件发送成功']);
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

    public function pwdResetTest()
    {
        echo 'not implement yet!';
    }

}
