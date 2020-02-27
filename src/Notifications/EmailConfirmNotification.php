<?php

namespace Modules\Opx\Users\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Modules\Opx\MailTemplater\OpxMailTemplater;

class EmailConfirmNotification extends Mailable
{
    use Queueable;

    protected $token;

    /**
     * CategoryUpdateNotification constructor.
     * @param string $to
     * @param string $token
     *
     * @return  void
     */
    public function __construct(string $to, string $token)
    {
        $this->to($to);
        $this->token = $token;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): self
    {
        $this->subject(trans('opx_users::notifications.email_confirm_subject'));
        $link = route('opx_users::confirm_email', ['token' => $this->token], true);

        return $this->html(OpxMailTemplater::make([
            OpxMailTemplater::title($this->subject),
            OpxMailTemplater::paragraph(trans('opx_users::notifications.email_confirm_intro')),
            OpxMailTemplater::anchor(trans('opx_users::notifications.email_confirm_link'), $link),
            OpxMailTemplater::paragraph(trans('opx_users::notifications.email_confirm_text', ['link' => $link])),
            OpxMailTemplater::paragraph(trans('opx_users::notifications.no_reply_notice')),
        ]));
    }
}