<?php

namespace AMovil\Reports\BloqueoControlReg\Domain;

class TipoOperacion
{
    public static function idIsBloqueo(int $id): bool
    {
        return $id === 1;
    }

    public static function idIsDesbloqueo(int $id): bool
    {
        return $id === 2;
    }
}
