<?php

namespace Modules\Opx\Users\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Modules\Opx\MailTemplater\OpxMailTemplater;

class PasswordResetNotification extends Mailable
{
    use Queueable;

    /** @var string */
    protected $token;

    /** @var string */
    protected $email;

    /**
     * CategoryUpdateNotification constructor.
     * @param string $email
     * @param string $token
     *
     * @return  void
     */
    public function __construct(string $email, string $token)
    {
        $this->to($email);
        $this->token = $token;
        $this->email = $email;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): self
    {
        $this->subject(trans('opx_users::notifications.reset_token_subject'));
        $link = route('opx_users::reset_form', ['email' => $this->email, 'token' => $this->token], true);

        return $this->html(OpxMailTemplater::make([
            OpxMailTemplater::title($this->subject),
            OpxMailTemplater::paragraph(trans('opx_users::notifications.reset_token_intro')),
            OpxMailTemplater::anchor(trans('opx_users::notifications.reset_token_link'), $link),
            OpxMailTemplater::paragraph(trans('opx_users::notifications.reset_token_text', ['link' => $link])),
            OpxMailTemplater::paragraph(trans('opx_users::notifications.no_reply_notice')),
        ]));
    }
}