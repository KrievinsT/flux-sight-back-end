<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPassword extends Notification implements ShouldQueue
{
    use Queueable;

    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        \Log::info('CustomResetPassword notification triggered.');
    
        return (new MailMessage)
            ->subject('Your Custom Password Reset Subject')
            ->greeting('Hello!')
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset Password', url(route('password.reset', $this->token, false)))
            ->line('If you did not request a password reset, no further action is required.')
            ->salutation('Regards, Your Custom Application Team');
    }
    
}
