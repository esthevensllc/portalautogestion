<?php

namespace AMovil\Reports\General\BloqueoImei\Domain;

class TipoBusqueda
{
    public static function idIsImei($id){
        return (int) $id === 1;
    }

    public static function idIsMsisdn($id){
        return (int) $id === 2;
    }
}
