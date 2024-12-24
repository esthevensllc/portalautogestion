<?php

namespace AMovil\Reports\DAPU\FONO\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\DAPU\FONO\Domain\FonoRepository;
use DateTime;
use Illuminate\Support\Facades\DB;

class EloquentFonoRepository implements FonoRepository
{
    private $authService;
    private $userIdentifier;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    
    public function getByFono($values)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        $now = new DateTime();
        $strDay = $now->modify("-1 day")->format("Ymd");
        
        DB::statement("BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.DAPU_LINEA_ACTUAL_INPUT_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;");
        DB::statement("CREATE TABLE USRAES.DAPU_LINEA_ACTUAL_INPUT_{$this->userIdentifier}(MSISDN varchar2(100))");

        foreach($values as $value){
            DB::table("USRAES.DAPU_LINEA_ACTUAL_INPUT_{$this->userIdentifier}")->insert(["msisdn" => $value]);
        }

        $data = DB::select(DB::raw("SELECT AA.MSISDN,
        CASE WHEN BB.IMSI IS NOT NULL THEN BB.IMSI 
            WHEN BB.IMSI IS NULL THEN 'NINGÚN DATO DISPONIBLE EN ESTA TABLA' END IMSI,
        BB.TECNOLOGIA_RED_CHIP,
        BB.TPLNAME,
        BB.PROVI_VOLTE,
        BB.SIMCARD_3G_SINVOLTE,
        BB.SIMCARD_3G_CONVOLTE 
        FROM USRAES.DAPU_LINEA_ACTUAL_INPUT_{$this->userIdentifier} AA 
        LEFT JOIN (
            SELECT MSISDN,
                IMSI,
                DECODE(CARD_TYPE, '1', '4G', '0', '2G/3G') TECNOLOGIA_RED_CHIP,
                CASE
                    WHEN ROUTECATEGORY IN ('6','46') AND CHARGE_GLOBA IN ('HOT') THEN 'PREPAGO'
                    ELSE 'POSTPAGO'
                END TPLNAME,
                CASE
                    WHEN APNTPLID LIKE '40'
                        OR APNTPLID LIKE '%'||CHR(38)||'40'
                        OR APNTPLID LIKE '40'||CHR(38)||'%'
                        OR APNTPLID LIKE '%'||CHR(38)||'40'||CHR(38)||'%' THEN 'CON VoLTE'
                    ELSE 'SIN VoLTE'
                END PROVI_VOLTE,
                CASE
                    WHEN CARD_TYPE LIKE '0'
                        AND APNTPLID NOT LIKE '40'
                        AND APNTPLID NOT LIKE '%'||CHR(38)||'40'
                        AND APNTPLID NOT LIKE '40'||CHR(38)||'%'
                        AND APNTPLID NOT LIKE '%'||CHR(38)||'40'||CHR(38)||'%' THEN 1
                    ELSE 0
                END SIMCARD_3G_SINVOLTE,
                CASE
                    WHEN CARD_TYPE LIKE '0'
                        AND (APNTPLID LIKE '40'
                            OR APNTPLID LIKE '%'||CHR(38)||'40'
                            OR APNTPLID LIKE '40'||CHR(38)||'%'
                            OR APNTPLID LIKE '%'||CHR(38)||'40'||CHR(38)||'%') THEN 1
                    ELSE 0
                END SIMCARD_3G_CONVOLTE
            FROM DWS.SA_HLR_UDB PARTITION (P_{$strDay}) X
            WHERE HLR_INDEX = 1
            AND MSISDN IN (SELECT MSISDN FROM USRAES.DAPU_LINEA_ACTUAL_INPUT_{$this->userIdentifier})
            ORDER BY 4, 5, 3
        ) BB 
        ON AA.MSISDN=BB.MSISDN"));

        DB::statement("BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.DAPU_LINEA_ACTUAL_INPUT_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;");

        return $data;
    }
}
