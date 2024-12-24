<?php

namespace AMovil\Reports\DAPU\ConsultaImei\Infrastructure;

use AMovil\Reports\DAPU\ConsultaImei\Domain\ConsultaImeiRepository;
use DateTime;
use Illuminate\Support\Facades\DB;

class EloquentConsultaImeiRepository implements ConsultaImeiRepository
{
    public function getByMsisdnAndFecha($msisdn, DateTime $fecha)
    {
        $query = "SELECT 
        MSISDN,
        NOMBRES,
        APE_PATERNO,
        APE_MATERNO,
        NRO_DOCUMENTO,
        RAZON_SOCIAL,
        IMSI,
        IMEI,
        FECHA_ACTUALIZACION
        FROM DWA.F_D_BASE_RA_HIST R  
        WHERE R.MSISDN = :msisdn --INPUT SIN 51
        --NRO_DOCUMENTO = '' INPUT 
        AND FECHA_ACTUALIZACION > = :fecha --INPUT 
        ORDER BY FECHA_ACTUALIZACION";
        
        return DB::select(DB::raw($query), ["msisdn" => $msisdn, "fecha" => $fecha->format("Ymd")]);
    }
}
