<?php

namespace AMovil\Reports\RepRecargas\Domain;

class TipoReporte
{
    private $id;
    private $name;
    private $tracName;

    public function __construct($id, $name, $tracName)
    {
        $this->id = $id;
        $this->name = $name;
        $this->tracName = $tracName;
    }

    public static function detalle(){
        return new self("01", "DETALLE_RECARGAS", "rep-recargas.detalle");
    }

    public static function extras(){
        return new self("02", "DETALLE_RECARGAS_EXTRAS", "rep-recargas.extras");
    }

    public function getId(){
        return $this->id;
    }

    public function getName(){
        return $this->name;
    }

    public function getTracName(){
        return $this->tracName;
    }

    public static function isValid($tipo){
        if(in_array($tipo, ["01", "02"])){
            return true;
        }
        return false;
    }
}
