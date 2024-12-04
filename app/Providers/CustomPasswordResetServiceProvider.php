<?php

namespace App\Providers;

use Illuminate\Auth\Passwords\PasswordBrokerManager;
use App\Services\CustomPasswordBroker;
use Illuminate\Support\ServiceProvider;

class CustomPasswordResetServiceProvider extends ServiceProvider
{
    public function register()
    {

    }
}
