<?php

namespace AMovil\Auth\User\Domain\Validators;

use AMovil\Auth\User\Infrastructure\Repository\User;
use AMovil\Shared\Domain\DomainValidator;
use Validator;

class CreateUserValidator extends DomainValidator
{
    public function validate($data)
    {
        $validator = Validator::make($data, [
            'username' => ['required', 'unique:'.User::class.',username'],
            'name' => ['required', 'max:250'],
            'last_name' => ['required', 'max:250'],
            'roles_id' => ['required', 'array'],
            'direccion' => ['nullable', 'max:50'],
            'area' => ['nullable', 'max:50'],
        ]);

        if($validator->fails()){
            $this->setErrors($validator->errors()->messages());
        }
    }
}
