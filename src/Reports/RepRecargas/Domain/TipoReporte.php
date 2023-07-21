<?php

namespace AMovil\Reports\RepRecargas\Domain;

class TipoReporte
{
    private $id;
    private $name;

    public function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public static function detalle(){
        return new self("01", "DETALLE_RECARGAS");
    }

    public static function extras(){
        return new self("02", "DETALLE_RECARGAS_EXTRAS");
    }

    public function getId(){
        return $this->id;
    }

    public function getName(){
        return $this->name;
    }

    public static function isValid($tipo){
        if(in_array($tipo, ["01", "02"])){
            return true;
        }
        return false;
    }
}
