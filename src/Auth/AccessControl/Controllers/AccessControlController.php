<?php

namespace AMovil\Auth\AccessControl\Controllers;

use AMovil\Auth\AccessControl\Services\LogoutUser;

class AccessControlController
{
    private $logoutService;
    
    public function __construct(LogoutUser $logoutService)
    {
        $this->logoutService = $logoutService;
    }

    public function logout()
    {
        $redirect_url = $this->logoutService->__invoke();
        return redirect($redirect_url);
    }
}
