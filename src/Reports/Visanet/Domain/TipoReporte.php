<?php

namespace AMovil\Reports\Visanet\Domain;

use AMovil\Shared\Domain\SimpleDomain;
use Exception;

class TipoReporte extends SimpleDomain
{
    public static function facturaDetallada(){
        return new self("1", "Factuta_detallada_Visanet");
    }

    public static function consumoDatos(){
        return new self("2", "Consumo_datos_visanet");
    }

    public static function getById($id): self {
        switch ($id) {
            case '1':
                return self::facturaDetallada();
                break;
            case '2':
                return self::consumoDatos();
                break;
            default:
                throw new Exception("El id del tipo reporte no existe");
                break;
        }
    }
}
