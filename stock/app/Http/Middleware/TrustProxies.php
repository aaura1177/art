<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class TrustProxies extends Middleware
{
    protected $proxies;
    protected $headers;

    public function __construct()
    {
        if (App::environment('local')) {
            // Don't trust any proxy headers in local
            $this->proxies = null;
            $this->headers = 0;
        } else {
            // Trust all proxy headers in production
            $this->proxies = '*';
            $this->headers =
                SymfonyRequest::HEADER_X_FORWARDED_FOR |
                SymfonyRequest::HEADER_X_FORWARDED_HOST |
                SymfonyRequest::HEADER_X_FORWARDED_PORT |
                SymfonyRequest::HEADER_X_FORWARDED_PROTO |
                SymfonyRequest::HEADER_X_FORWARDED_AWS_ELB;
        }
    }
}
