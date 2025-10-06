<?php

namespace AMovil\Reports\DAPU\BusquedaSimCard\Infrastructure;

use AMovil\Reports\DAPU\BusquedaSimCard\Domain\BusquedaSimCardRepository;
use Illuminate\Support\Facades\DB;

class EloquentBusquedaSimCardRepository implements BusquedaSimCardRepository
{
    public function getByIccid(string $iccid){
        $query = "SELECT /*+ PARALLEL(8) */ 
        A.FECHA_VENTA,
        A.IMEI IMEI,
        A.ICCID CODIGO_CHIP,
        A.SIMCARD CODIGO_EQUIPO,
        A.DESC_SIMCARD DESCRIPCION_EQUIPO,
        A.DES_EQUIPO EQUIPO,
        A.NRO_TELEFONO LINEA,
        A.DESC_TIPO_VENTA MODALIDAD,
        A.DESC_CANAL CANAL,
        A.DESC_OFIC_VENTA DESC_OFICINA_VENTA,
        A.DESC_LISTA_PRECIO TIPO,
        A.DESC_TIPO_DOC_CLIENTE TIPO_DOC_CLIENTE,
        A.CLIENTE DOCUMENTO_CLIENTE,
        A.DESC_CLASE_VENTA TRANSACCION,
        UPPER(A.NOMBRES) NOMBRES_CLIENTE,
        UPPER(A.AP_PATERNO) || UPPER(A.AP_MATERNO) APELLIDOS_CLIENTE,
        A.VENDEDOR COD_VENDEDOR,
        A.NOMBRE_VENDEDOR,
        A.NRO_CONTRATO
        FROM DM.DW_SELLOUT A
        WHERE A.ICCID = :iccid
        ";
        return DB::select($query, ["iccid" => $iccid]);
    }
}
