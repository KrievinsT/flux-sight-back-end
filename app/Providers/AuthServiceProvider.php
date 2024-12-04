<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Services\CustomPasswordBrokerManager;
use Illuminate\Support\Facades\Log;

class AuthServiceProvider extends ServiceProvider
{
    public function register()
    {

    }
}
