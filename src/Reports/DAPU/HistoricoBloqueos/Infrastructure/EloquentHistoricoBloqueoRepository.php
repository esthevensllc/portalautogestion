<?php

namespace AMovil\Reports\DAPU\HistoricoBloqueos\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\DAPU\HistoricoBloqueos\Domain\HistoricoBloqueoRepository;
use Illuminate\Support\Facades\DB;

class EloquentHistoricoBloqueoRepository implements HistoricoBloqueoRepository
{
    private $authService;
    private $userIdentifier;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    
    public function getByImeis(array $imeis)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        DB::statement("BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.DAPU_BLOQUEO_IMEI_INPUT_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;");
        DB::statement("CREATE TABLE USRAES.DAPU_BLOQUEO_IMEI_INPUT_{$this->userIdentifier}(imei varchar2(100))");

        foreach($imeis as $value){
            DB::table("USRAES.DAPU_BLOQUEO_IMEI_INPUT_{$this->userIdentifier}")->insert(["imei" => $value]);
        }

        $query = "SELECT
        BB.HISTD_FECHA_MENSAJE,
        AA.IMEI,
        BB.HISTN_NUM_SERVICI,
        BB.HISTV_IMEI,
        BB.HISTD_FECHA_REPORTE,
        BB.NOMBRE_APELLIDOS,
        BB.HISTN_TIPO_DOCUMENTO,
        BB.HISTV_NUMERO_DOCUMENTO,
        BB.HISTV_TIPO_SOLICITUD,
        BB.HISTV_ESTADO,
        BB.HISTV_ACCION_REALIZAR 
        FROM USRAES.DAPU_BLOQUEO_IMEI_INPUT_{$this->userIdentifier} AA 
        LEFT JOIN (
            SELECT HISTD_FECHA_MENSAJE,
            TO_CHAR(HISTN_NUM_SERVICI) HISTN_NUM_SERVICI,
            TO_CHAR(HISTN_IMSI) HISTN_IMSI,
            HISTV_IMEI,
            HISTD_FECHA_REPORTE,
            HISTV_NOMBRE_USUARIO||' '||HISTV_APELLIDO_PATERNO||' '||HISTV_APELLIDO_MATERNO NOMBRE_APELLIDOS,
            HISTN_TIPO_DOCUMENTO,
            HISTV_NUMERO_DOCUMENTO,
            HISTV_TIPO_SOLICITUD,
            HISTV_ESTADO,
            HISTV_ACCION_REALIZAR
            FROM DWS.SA_RENTT_HISTORICO_RENTESEG
            WHERE HISTV_IMEI IN (SELECT IMEI FROM USRAES.DAPU_BLOQUEO_IMEI_INPUT_{$this->userIdentifier})
        ) BB 
        ON AA.IMEI=BB.HISTV_IMEI";

        $data = DB::select(DB::raw($query));

        DB::statement("BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.DAPU_BLOQUEO_IMEI_INPUT_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;");

        return $data;
    }
}
