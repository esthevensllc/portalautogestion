<?php

namespace AMovil\Auth\Roles\Domain;

use Exception;

class RolStatus
{
    const ENABLED = 1;
    const DISABLED = 0;

    public static function isValid(?int $status): bool
    {
        if(in_array($status, [self::ENABLED, self::DISABLED])){
            return true;
        }
        return false;
    }

    public static function guard($status){
        if(!self::isValid($status)){
            throw new Exception("El status es invalido");
        }
    }
}
