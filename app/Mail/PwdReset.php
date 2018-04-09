<?php

namespace App\Mail;

use App\Models\Users;
use App\Services\Site\SiteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Redis;

class PwdReset extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    protected $requestHost;
    /**
     * @var Users $user
     */
    protected $user;
    protected $siteName;
    /**
     * @var SiteService
     */
    private $siteService;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user)
    {
        $this->queue = 'mail:pwdreset';
        $this->to($user->safemail);
        $this->user = $user;
        $this->requestHost = request()->getSchemeAndHttpHost();

        $siteConfig = app(SiteService::class)->config();
        $this->siteName = $siteName = $siteConfig->get('name');
        $mail_from = $siteConfig->get('mail_from', config('mail.from.address'));

        $subject = $siteName . '密码重置';
        $mail_reply_to = $siteConfig->get('$mail_reply_to');
        $mail_reply_to && $this->replyTo($mail_reply_to, $siteName);
        $this->subject($subject)->from($mail_from, $siteName);

    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $token = Crypt::encrypt([
            'uid' => $this->user->uid,
            't'=>time(),
        ]);
        Redis::setex('pwdreset.token:'.$token,30*60,1);
        $url = $this->requestHost . '/pwdreset/verify?token=' . urlencode($token);
        return $this->view('emails.pwdreset', [
            'username' => $this->user->username,
            'siteName' => $this->siteName,
            'url' => $url,
            'date' => date('Y-m-d H:i:s'),
        ]);
    }
}
