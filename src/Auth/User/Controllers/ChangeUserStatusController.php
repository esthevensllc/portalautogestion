<?php

namespace AMovil\Auth\User\Controllers;

use AMovil\Auth\User\Services\ChangeUserStatus;

class ChangeUserStatusController
{
    private $service;

    public function __construct(ChangeUserStatus $service)
    {
        $this->service = $service;
    }

    public function __invoke($id, $status)
    {
        $this->service->__invoke($id, $status);
        return response()->json([]);
    }
}
