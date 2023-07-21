<?php

namespace AMovil\Auth\User\Controllers;

use AMovil\Auth\Roles\Services\GetRoles;
use AMovil\Auth\User\Services\FindUser;
use AMovil\Auth\User\Services\UpdateUser;
use Illuminate\Http\Request;

class EditUserController
{
    private $service;
    private $getRoles;
    private $updateUser;
    
    public function __construct(FindUser $service, GetRoles $getRoles, UpdateUser $updateUser)
    {
        $this->service = $service;
        $this->getRoles = $getRoles;
        $this->updateUser = $updateUser;
    }

    public function view($id, Request $request)
    {
        $roles = $this->getRoles->__invoke()['data'];
        $user = $this->service->__invoke($id);
        $config = [
            'mode' => 'update',
            'title' => 'Editar usuario',
        ];
        return view('admin.usuarios.edit_user', compact('roles', 'user', 'config'));
    }

    public function udpate($id, Request $request)
    {
        $response = $this->updateUser->__invoke(array_merge(['id' => $id], $request->all()));
        return response()->json($response->toArray());
    }
}
