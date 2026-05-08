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

        $query = "SELECT /*+ PARALLEL(8) */ HISTD_FECHA_MENSAJE AS FECHA,
                          HISTV_ACCION_REALIZAR ACCION,
                          HISTV_TIPO_TIPIFICACION MODALIDAD,
                          HISTN_NUM_SERVICI LINEA,
                          HISTN_IMSI,
                          HISTV_IMEI,
                          HISTN_TIPO_DOCUMENTO AS TIPO_DOCUMENTO,
                          HISTV_NUMERO_DOCUMENTO AS NRO_DOCUMENTO,
                          HISTV_NOMBRE_USUARIO AS NOMBRE,
                          HISTV_APELLIDO_PATERNO || ' ' || HISTV_APELLIDO_MATERNO AS APELLIDOS
	FROM DWS.SA_RENTT_HISTORICO_RENTESEG A
	WHERE HISTV_IMEI IN (SELECT IMEI FROM USRAES.DAPU_BLOQUEO_IMEI_INPUT_{$this->userIdentifier})
	  AND HISTN_CODIGO_MENSAJE NOT IN ('301')
	ORDER BY HISTV_IMEI,
         HISTD_FECHA_MENSAJE DESC";

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
