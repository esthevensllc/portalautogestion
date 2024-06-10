<?php

namespace AMovil\Reports\DAPU\ListaExcepcionesImeiImsi\Infrastructure;

use AMovil\Reports\DAPU\ListaExcepcionesImeiImsi\Domain\ListaExcepcionesImeiImsiRepository;
use DateTime;
use Illuminate\Support\Facades\DB;

class EloquentListaExcepcionesImeiImsiRepository implements ListaExcepcionesImeiImsiRepository
{
    public function getByImei($imei)
    {
        $now = (new DateTime())->format('Ymd');
        $query = "SELECT L.TRANSACT_DATE, L.TASK_ID, L.HLRSN, L.OPERATOR, L.DATE_TIME, L.COMMAND, L.INTERNAL_PARAMETER_5 COD_CMD,
        CASE L.INTERNAL_PARAMETER_5
              WHEN '1004' THEN 'Invalid parameter value'
              WHEN '100000001' THEN 'Operation is successful'
              WHEN '3007' THEN 'Record not defined'
              WHEN '3006' THEN 'Record already exist'
              WHEN '3257' THEN 'Number of IMSIs associated with the IMEI exceeds the maximum' 
        END CMD_RESULT,
        SUBSTR(L.COMMAND,20,14) IMEI
    FROM DWS.SA_EIR_LOG L
    WHERE TO_CHAR(L.TRANSACT_DATE,'YYYYMMDD') >= '20231001'
        AND L.OPERATOR IN ('ILPREP','ILPOST')
        AND SUBSTR(L.COMMAND,20,14) = :imei
    ORDER BY L.DATE_TIME ASC";
        return DB::select($query, ["imei" => $imei]);
    }
}
