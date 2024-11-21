<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;
use Illuminate\Contracts\Config\Repository as Config;

class TrustProxies extends Middleware
{
    protected $proxies;

    protected $headers = Request::HEADER_X_FORWARDED_ALL;

    public function __construct(Config $config)
    {
        parent::__construct($config);
        $this->proxies = config('trustedproxy.proxies');
        $this->headers = config('trustedproxy.headers', Request::HEADER_X_FORWARDED_ALL);
    }
}
