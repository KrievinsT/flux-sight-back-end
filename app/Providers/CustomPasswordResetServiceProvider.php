<?php

namespace App\Providers;

use Illuminate\Auth\Passwords\PasswordBrokerManager;
use App\Services\CustomPasswordBroker;
use Illuminate\Support\ServiceProvider;

class CustomPasswordResetServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->extend('auth.password.broker', function ($service, $app) {
            return new PasswordBrokerManager($app);
        });
        
        $this->app->bind('auth.password.broker', function ($app) {
            return new CustomPasswordBroker(
                $app['auth.password.tokens'],
                $app['auth.password.broker']
            );
        });
    }
}
