<?php

namespace AMovil\Auth\AccessControl\Services;

use AMovil\Auth\AccessControl\Domain\AuthService;

class LogoutUser
{
    private AuthService $service;
    
    public function __construct(AuthService $service)
    {
        $this->service = $service;
    }

    public function __invoke()
    {
        $this->service->logout();
        return $this->service->getConfig('url_logout');
    }
}
