<?php

namespace AMovil\Shared\Exports\Domain;

use Exception;

class WriterType
{
    const CSV = 'Csv';
    const XLSX = 'Xlsx';
    const XLS = 'Xls';

    public static function isValid($type): bool
    {
        if(in_array($type, [self::CSV, self::XLSX, self::XLS])){
            return true;
        }
        return false;
    }

    public static function guard($type){
        if(!self::isValid($type)){
            throw new Exception("El tipo de writer es invalido");
        }
    }
}
