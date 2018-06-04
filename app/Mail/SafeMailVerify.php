<?php

namespace App\Mail;

use App\Models\Conf;
use App\Models\Users;
use App\Services\Site\SiteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;

class SafeMailVerify extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    protected $content;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 2;

    /**
     * Create a new message instance.
     *
     * @param Users      $user
     * @param            $email
     */
    public function __construct(Users $user, $email)
    {
        $this->queue = 'mail:safeMailVerify';

        $this->to($email);
        $siteConfig = app(SiteService::class)->config();
        $siteName = $siteConfig->get('name');
        $subject = $siteName . '安全邮箱验证';
        $mail_from = $siteConfig->get('mail_from', config('mail.from.address'));
        $mail_reply_to = $siteConfig->get('$mail_reply_to');
        $mail_reply_to && $this->replyTo($mail_reply_to, $siteName);
        $this->subject($subject)->from($mail_from, $siteName);
        $token = Crypt::encrypt([
            'email' => $email,
            'uid' => $user->uid,
            'time' => time(),
        ]);
        $tokenUrl = route('mail_verify_confirm', ['token' => $token]);
        $name = $user['nickname'] ?: $user['username'];
        $url = $tokenUrl;
        $url_html = '<a href="' . $url . '" target="_blank"  style="word-wrap: break-word;cursor:pointer;text-decoration:none;color:#0082cb">' . $url . '</a>';
        $emailtemplate =  $siteConfig->get('email');
        $content = $emailtemplate ? $emailtemplate ?? '' : '';
        $this->content = preg_replace([
            '/{{\s*name\s*}}/i',
            '/{{\s*url\s*}}/i',
            '/{{\s*date\s*}}/i',
        ], [
            $name,
            $url_html,
            date('Y年m月d日 H:i:s'),
        ], $content);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.safeMailVerify', ['content' => $this->content]);
    }

    public function failed()
    {

    }

}
