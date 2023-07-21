<?php

namespace AMovil\Auth\User\Controllers;

use AMovil\Auth\User\Infrastructure\Repository\User;
use Backpack\CRUD\app\Http\Controllers\CrudController;

class BackpackListUserController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;

    public function setup()
    {
        $this->crud->setModel(User::class);
        $this->crud->setRoute('admin/usuarios');
        $this->crud->setEntityNameStrings('usuario', 'usuarios');
    }

    protected function setupListOperation()
    {
        $this->crud->addColumn([
            'name' => 'id',
            'type' => 'number',
        ]);
        $this->crud->addColumn([
            'name' => 'id',
            'type' => 'number',
        ]);
        // $this->crud->setFromDb();
    }
}
