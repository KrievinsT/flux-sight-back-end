<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPasswordNotification extends ResetPasswordNotification
{
    public function toMail($notifiable)
    {
        $resetUrl = env('REACT_APP_URL') . '/forgot_password/' . $this->token . '?email=' . urlencode($notifiable->email);

        return (new MailMessage)
            ->from('toms.ricards@vtdt.edu.lv', 'FLUXSIGHT TEAM')
            ->subject('Your Password Reset Link')
            ->markdown('emails.reset_password', ['resetUrl' => $resetUrl]);
    }
}
