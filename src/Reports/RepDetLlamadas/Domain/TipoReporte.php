<?php

namespace AMovil\Reports\RepDetLlamadas\Domain;

class TipoReporte
{
    const ENTRANTES = '01';
    const SALIENTES = '02';
    const ENTRANTES_SALIENTES = '03';

    public static function isValid($tipo){
        if(in_array($tipo, [self::ENTRANTES, self::SALIENTES, self::ENTRANTES_SALIENTES])){
            return true;
        }
        return false;
    }

    public static function getLabel($tipo)
    {
        switch ($tipo) {
            case '01':
                return 'ENTRANTES';
            case '02':
                return 'SALIENTES';
            case '03':
                return 'ENTRANTES_SALIENTES';
            default:
                return null;
        }
    }
}
