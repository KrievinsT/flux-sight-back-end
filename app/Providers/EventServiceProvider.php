<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Your event listeners
    ];

    public function boot()
    {
        parent::boot();

        // Custom boot logic
    }
}
