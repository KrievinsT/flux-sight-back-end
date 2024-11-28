<?php

namespace App\Services;

use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use App\Notifications\CustomResetPassword;

class CustomPasswordBroker extends PasswordBroker
{
    public function sendResetLink(array $credentials)
    {
        $user = $this->getUser($credentials);

        if ($user instanceof CanResetPasswordContract) {
            $token = $this->tokens->create($user);

            $user->notify(new CustomResetPassword($token));

            return static::RESET_LINK_SENT;
        }

        return static::INVALID_USER;
    }
}
