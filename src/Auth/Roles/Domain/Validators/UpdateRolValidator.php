<?php

namespace AMovil\Auth\Roles\Domain\Validators;

use AMovil\Auth\Roles\Infrastructure\Repository\RolModel;
use AMovil\Shared\Domain\DomainValidator;
use Validator;

class UpdateRolValidator extends DomainValidator
{
    public function validate($data){
        $validator = Validator::make($data,[
            'id' => ['required', 'exists:'.RolModel::class.',id'],
            'name' => ['required', 'max:250', 'unique:'.RolModel::class.',name,'.$data['id']],
            'modules_id' => ['required', 'array'],
        ]);
        if($validator->fails()){
            $this->setErrors($validator->errors()->messages());
        }
    }
}
