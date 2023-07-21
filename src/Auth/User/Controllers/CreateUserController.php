<?php

namespace AMovil\Auth\User\Controllers;

use AMovil\Auth\Roles\Services\GetRoles;
use AMovil\Auth\User\Services\CreateUser;
use Illuminate\Http\Request;

class CreateUserController
{
    private $getRoles;
    private $createUser;
    
    public function __construct(GetRoles $getRoles, CreateUser $createUser)
    {
        $this->getRoles = $getRoles;
        $this->createUser = $createUser;
    }

    public function view()
    {
        $roles = $this->getRoles->__invoke()['data'];
        $user = null;
        $config = [
            'mode' => 'create',
            'title' => 'Registrar usuario',
        ];
        return view('admin.usuarios.edit_user', compact('roles', 'user', 'config'));
    }

    public function create(Request $request)
    {
        $response = $this->createUser->__invoke($request->all());
        return response()->json($response->toArray());
    }
}
