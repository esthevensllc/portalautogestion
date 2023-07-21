<?php

namespace AMovil\Reports\MINEDU\RepConsumo\Domain;

use Exception;

class TipoReporte
{
    private $id;
    private $label;

    public function __construct($id, $label)
    {
        $this->id = $id;
        $this->label = $label;
    }

    public function getId(){
        return $this->id;
    }

    public function getLabel(){
        return $this->label;
    }

    public static function freeZone(){
        return new self("1", "FREEZONE");
    }

    public static function periodoFacturado(){
        return new self("2", "PERIODO_FACTURADO");
    }

    public static function freeZone_periodoFacturado(){
        return new self("3", "FREEZONE-PERIODO_FACTURADO");
    }

    public static function getById($id): self {
        switch ($id) {
            case '1':
                return self::freeZone();
                break;
            case '2':
                return self::periodoFacturado();
                break;
            case '3':
                return self::freeZone_periodoFacturado();
                break;
            default:
                throw new Exception("El id del tipo reporte no existe");
                break;
        }
    }
}
