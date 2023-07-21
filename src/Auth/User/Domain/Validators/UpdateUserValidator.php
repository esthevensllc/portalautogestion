<?php

namespace AMovil\Auth\User\Domain\Validators;

use AMovil\Shared\Domain\DomainValidator;
use \AMovil\Auth\User\Infrastructure\Repository\User;
use Validator;

class UpdateUserValidator extends DomainValidator
{
    public function validate($data)
    {
        $validator = Validator::make($data, [
            // 'exists:usraes.padm_user,id', 
            // 'id' => ['required', Rule::exists('usraes.padm_user', 'id')],
            'id' => ['required', 'exists:'.User::class.',id'],
            'username' => ['required', 'unique:'.User::class.',username,'.$data['id']],
            // 'username' => ['required', Rule::unique(\AMovil\Auth\User\Infrastructure\Repository\User::class)->ignore($data['username'], 'username')],
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
