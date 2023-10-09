<?php

namespace AMovil\Reports\BloqueoControlReg\Domain;

class TipoDocumento
{
    public static function idIsSIBMED(int $id): bool
    {
        return $id === 1;
    }

    public static function idIsDAPU(int $id): bool
    {
        return $id === 2;
    }
}
