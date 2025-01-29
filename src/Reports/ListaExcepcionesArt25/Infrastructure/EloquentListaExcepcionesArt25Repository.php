<?php

namespace AMovil\Reports\ListaExcepcionesArt25\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\ListaExcepcionesArt25\Domain\ListaExcepcionesArt25Repository;
use Illuminate\Support\Facades\DB;

class EloquentListaExcepcionesArt25Repository implements ListaExcepcionesArt25Repository
{
    private $authService;
    private $userIdentifier;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function findAll()
    {
        return DB::select("SELECT TRANSACT_DATE, SUBSTR(IMEI,0,14) IMEI, OPERACION, MSISDN, IMSI from DWS.SA_RENTESEG_LISTA_EXCEPCION");
    }

    public function findLast()
    {
        return DB::select("SELECT TRANSACT_DATE, SUBSTR(IMEI,0,14) IMEI, OPERACION, MSISDN, IMSI from DWS.SA_RENTESEG_LISTA_EXCEPCION
        where trunc(TRANSACT_DATE, 'dd') = trunc((select max(TRANSACT_DATE) from DWS.SA_RENTESEG_LISTA_EXCEPCION), 'dd')
        ");
    }

    public function getByImei(array $imeis)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        DB::statement("BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.LISTA_EXCEP_IMEI_INPUT_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;");
        DB::statement("CREATE TABLE USRAES.LISTA_EXCEP_IMEI_INPUT_{$this->userIdentifier}(imei varchar2(100))");

        foreach($imeis as $value){
            DB::table("USRAES.LISTA_EXCEP_IMEI_INPUT_{$this->userIdentifier}")->insert(["imei" => $value]);
        }

        $query = "SELECT TRANSACT_DATE, SUBSTR(IMEI,0,14) IMEI, OPERACION, MSISDN, IMSI FROM DWS.SA_RENTESEG_LISTA_EXCEPCION
        WHERE SUBSTR(IMEI,0,14) in (
            select imei from USRAES.LISTA_EXCEP_IMEI_INPUT_{$this->userIdentifier}
        )";

        $data = DB::select($query);

        DB::statement("BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.LISTA_EXCEP_IMEI_INPUT_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;");

        return $data;
    }

}
