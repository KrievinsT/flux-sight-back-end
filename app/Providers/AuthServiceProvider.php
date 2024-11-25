<?php

namespace App\Providers;

use Illuminate\Support\Facades\Password;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Notifications\CustomResetPassword;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Password::broker()->setResetEmail(function ($user, $token) {
            return new CustomResetPassword($token);
        });
    }
}
