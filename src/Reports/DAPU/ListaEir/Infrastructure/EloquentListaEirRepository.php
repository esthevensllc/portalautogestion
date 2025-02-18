<?php

namespace AMovil\Reports\DAPU\ListaEir\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\DAPU\ListaEir\Domain\ListaEirRepository;
use Illuminate\Support\Facades\DB;

class EloquentListaEirRepository implements ListaEirRepository
{
    private $authService;
    private $userIdentifier;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    
    public function getByImeis($imeis)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        DB::statement("BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.DAPU_IMEI_INPUT_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;");
        DB::statement("CREATE TABLE USRAES.DAPU_IMEI_INPUT_{$this->userIdentifier}(imei varchar2(100))");

        foreach($imeis as $value){
            DB::table("USRAES.DAPU_IMEI_INPUT_{$this->userIdentifier}")->insert(["imei" => $value]);
        }

        $query = "SELECT /*+ PARALLEL(20) */
        L.TRANSACT_DATE,
        L.TASK_ID,
        L.HLRSN,
        L.OPERATOR,
        L.DATE_TIME,
        L.COMMAND,
        L.INTERNAL_PARAMETER_5 COD_CMD,
        CASE L.INTERNAL_PARAMETER_5
            WHEN '1004' THEN 'Invalid parameter value'
            WHEN '100000001' THEN 'Operation is successful'
            WHEN '3007' THEN 'Record not defined'
            WHEN '3006' THEN 'Record already exist'
            WHEN '3257' THEN 'Number of IMSIs associated with the IMEI exceeds the maximum'
        END CMD_RESULT,
        SUBSTR(L.COMMAND, 20, 14) IMEI
        FROM DWS.SA_EIR_LOG L
        WHERE --SUBSTR(L.COMMAND, 0, 3) = 'MOD'
        SUBSTR(L.COMMAND, 20, 14) 
        IN (
            select SUBSTR(REGEXP_REPLACE(imei, '^0+', ''),1,14) imei 
            from USRAES.DAPU_IMEI_INPUT_{$this->userIdentifier}
        ) --INPUT 14 PRIMEROS DIGITOS
        ORDER BY L.DATE_TIME ASC";
        
        $data = DB::select($query);

        DB::statement("BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.DAPU_IMEI_INPUT_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;");

        return $data;
    }
}
