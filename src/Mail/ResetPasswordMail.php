<?php

namespace Modules\Opx\Users\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Opx\MailTemplater\OpxMailTemplater;
use Modules\Opx\Users\OpxUsers;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    protected string $token;

    /**
     * Create a new message instance.
     *
     * @param string $token
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): self
    {
        $from = OpxUsers::config('reset_password_send_from');
        $subject = OpxUsers::trans('reset_password_mail_subject');
        $link = url("/login/reset?token={$this->token}");

        return $this
            ->from($from)
            ->subject($subject)
            ->html(OpxMailTemplater::make([
                OpxMailTemplater::title(OpxUsers::trans('reset_password_mail_title')),
                OpxMailTemplater::paragraph(OpxUsers::trans('reset_password_mail_intro', ['time' => OpxUsers::config('reset_token_ttl')])),
                OpxMailTemplater::anchor(OpxUsers::trans('reset_password_mail_action'), $link, true, 'center bold'),
                OpxMailTemplater::paragraph(OpxUsers::trans('reset_password_mail_outtro')),
                OpxMailTemplater::paragraph(OpxUsers::trans('reset_password_mail_troubles')),
                OpxMailTemplater::paragraph($link, 'bold'),
            ]));
    }
}
