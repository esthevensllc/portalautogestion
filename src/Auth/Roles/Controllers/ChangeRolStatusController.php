<?php

namespace AMovil\Auth\Roles\Controllers;

use AMovil\Auth\Roles\Services\ChangeRolStatus;

class ChangeRolStatusController
{
    private $service;

    public function __construct(ChangeRolStatus $service)
    {
        $this->service = $service;
    }

    public function __invoke($id, $status)
    {
        $this->service->__invoke($id, $status);
        return response()->json([]);
    }
}
