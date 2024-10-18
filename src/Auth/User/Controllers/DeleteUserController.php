<?php

namespace AMovil\Auth\User\Controllers;

use AMovil\Auth\User\Services\DeleteUser;

class DeleteUserController
{
    private $service;

    public function __construct(DeleteUser $service)
    {
        $this->service = $service;
    }

    public function __invoke($id)
    {
        $this->service->__invoke($id);
        return response()->json([]);
    }
}
