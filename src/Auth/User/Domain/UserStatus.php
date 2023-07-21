<?php

namespace AMovil\Auth\User\Domain;

use Exception;

class UserStatus
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
