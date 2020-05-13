<?php

namespace Modules\Opx\User\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Opx\MailTemplater\OpxMailTemplater;
use Modules\Opx\User\OpxUser;
use Modules\Opx\Value\OpxValue;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $token;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($token)
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
        $from = OpxUser::config('reset_password_send_from');
        $subject = OpxUser::trans('reset_password_mail_subject');
        $link = url("/login/reset?token={$this->token}");

        return $this
            ->from($from, strtolower(OpxValue::get('company_name')))
            ->subject($subject)
            ->html(OpxMailTemplater::make([
                OpxMailTemplater::title(OpxUser::trans('reset_password_mail_title')),
                OpxMailTemplater::paragraph(OpxUser::trans('reset_password_mail_intro', ['time' => OpxUser::config('reset_token_ttl')])),
                OpxMailTemplater::anchor(OpxUser::trans('reset_password_mail_action'), $link, true, 'center bold'),
                OpxMailTemplater::paragraph(OpxUser::trans('reset_password_mail_outtro')),
                OpxMailTemplater::paragraph(OpxUser::trans('reset_password_mail_troubles')),
                OpxMailTemplater::paragraph($link, 'bold'),
            ]));
    }
}
