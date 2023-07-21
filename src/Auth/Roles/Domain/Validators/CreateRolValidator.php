<?php

namespace AMovil\Auth\Roles\Domain\Validators;

use AMovil\Auth\Roles\Infrastructure\Repository\RolModel;
use AMovil\Shared\Domain\DomainValidator;
use Validator;

class CreateRolValidator extends DomainValidator
{
    public function validate($data){
        $validator = Validator::make($data,[
            'name' => ['required', 'max:250', 'unique:'.RolModel::class],
            'modules_id' => ['required', 'array'],
        ], [
            'name.unique' => 'El rol ya existe',
            'modules_id.required' => 'Se debe asignar como mínimo un módulo',
        ]);
        if($validator->fails()){
            $this->setErrors($validator->errors()->messages());
        }
    }
}
