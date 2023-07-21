<?php

namespace AMovil\Reports\Visanet\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\Visanet\Domain\VisanetRepository;
use DateTime;
use Illuminate\Support\Facades\DB;

class EloquentVisanetRepository implements VisanetRepository
{
    private $authService;
    private $userIdentifier;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function getFacturaDetalladaByNumCuenta_Periodo(array $numCuenta, DateTime $periodo)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        $this->generateReporte($numCuenta, $periodo);
        return DB::connection("oracle_dbtodb")->table("USRAES.tmp_fact_deta_{$this->userIdentifier}")->get();
    }

    public function getConsumoDatosByNumCuenta_Periodo(array $numCuenta, DateTime $periodo)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        $this->generateReporte($numCuenta, $periodo);

        $queries = [];
        $queries[] = [
            "sql" => "BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.tmp_consumo_datos_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                    IF SQLCODE != -942 THEN
                        RAISE;
                    END IF;
            END;"
        ];
        $queries[] = [
            "sql" => "CREATE TABLE USRAES.tmp_consumo_datos_{$this->userIdentifier} as
            SELECT DISTINCT MSISDN NRO_TEL_ORIGEN, B.CLIENTACCTNO, smsdate,                
            SUM(SMSDURATION) CONSUMO_BYTES, round(SUM(SMSDURATION)/1024,3) CONSUMO_KB, round(SUM(SMSDURATION)/1024/1024,3) CONSUMO_MB
            FROM temp_tag_1480 A,  USRAES.Tmp_INVOICENUMBER_{$this->userIdentifier} B
            WHERE  B.INVOICENUMBER =A.INVOICENUMBER
            AND  A.TARIFFZONE ='DAT01'
            GROUP BY MSISDN,smsdate,CLIENTACCTNO"
        ];
        $this->exec_sql($queries);
        return  DB::connection("oracle_dbtodb")->table("USRAES.tmp_consumo_datos_{$this->userIdentifier}")
        ->selectRaw(" NRO_TEL_ORIGEN, CLIENTACCTNO, to_char(SMSDATE, 'MM/DD/YYYY') as SMSDATE, CONSUMO_BYTES, CONSUMO_KB, CONSUMO_MB")
        ->get();
    }

    private function generateReporte(array $numCuenta, DateTime $periodo)
    {
        $str_params = [];
        $bindings = [];
        foreach($numCuenta as $i => $value){
            $str_params[] = "to_char(:pcuenta".($i+1).")";
            $bindings["pcuenta".($i+1)] = $value;
        }
        $str_params = implode(",", $str_params);
        $str_periodo = $periodo->format("Ym");

        $queries = [];
        $queries[] = [
            "sql" => "BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.Tmp_INVOICENUMBER_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                    IF SQLCODE != -942 THEN
                        RAISE;
                    END IF;
            END;"
        ];
        $queries[] = ["sql" => "CREATE TABLE USRAES.Tmp_INVOICENUMBER_{$this->userIdentifier} as
        select
        T.INVOICENUMBER ,T.clientacctno,T.ACCOUNTNAME,T.PHONENUMBER,T.PERIODSTART,T.PERIODEND,T.PERIODO
        ,T.CSCOMPREGNO,T.CCNAME,' ' CSCOMPTAXNO,T.CYCLE,T.CUSTOMERID,substr(T.invoicenumber,1,LENGTH(T.INVOICENUMBER)-6) RECIBO
        from TEMP_TAG_11 T
        where 1=2"];
        $queries[] = ["sql" => "INSERT INTO USRAES.Tmp_INVOICENUMBER_{$this->userIdentifier}
        select
        T.INVOICENUMBER ,T.clientacctno,T.ACCOUNTNAME,T.PHONENUMBER,T.PERIODSTART,T.PERIODEND,T.PERIODO
        ,T.CSCOMPREGNO,T.CCNAME,' ' CSCOMPTAXNO,T.CYCLE,T.CUSTOMERID,substr(T.invoicenumber,1,LENGTH(T.INVOICENUMBER)-6) RECIBO
        from TEMP_TAG_11 T
        where
        T.PERIODO='{$str_periodo}'
        and clientacctno in ($str_params)", "params" => $bindings];

        $queries[] = [
            "sql" => "BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_VISANET_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                    IF SQLCODE != -942 THEN
                        RAISE;
                    END IF;
            END;"
        ];
        $queries[] = ["sql" => "CREATE TABLE USRAES.TMP_VISANET_{$this->userIdentifier} AS
        SELECT
        K.INVOICENUMBER,
        K.MSISDN,
        SUM(K.RPC) RPC,
        SUM(K.INTERNET) INTERNET,
        SUM(K.BLACK) BLACK,
        SUM(K.PAQUETE_SMS)PAQUETE_SMS,
        SUM(K.PAQUETE_ROAMING) PAQUETE_ROAMING,
        SUM(K.Trafico_Datos) Trafico_Datos,
        SUM(K.Mensaje_Texto) Mensaje_Texto,
        SUM(K.OTROS) OTROS
        FROM
        (SELECT
        T.INVOICENUMBER INVOICENUMBER,
        T.MSISDN MSISDN,
        CASE WHEN UPPER(DESCRIPTION) LIKE UPPER('%RPC%') THEN SUM(AMOUNT) ELSE 0 END RPC,
        CASE WHEN (UPPER(DESCRIPTION) LIKE UPPER('%INTERNET%') OR  UPPER(DESCRIPTION) LIKE UPPER('%GPRS%') or
            (UPPER(DESCRIPTION) LIKE UPPER('%Paq.%GB%')) OR (UPPER(DESCRIPTION) LIKE UPPER('%PAQ%MB%')) or 
            (UPPER(DESCRIPTION) LIKE UPPER('MB ILIMITADOS%'))  )  THEN SUM(AMOUNT) ELSE 0 END INTERNET,
        CASE WHEN UPPER(DESCRIPTION) LIKE UPPER('%BLACK%') THEN SUM(AMOUNT) ELSE 0 END BLACK,
        CASE WHEN (UPPER(DESCRIPTION) LIKE UPPER('%SMS%') OR (UPPER(DESCRIPTION) = UPPER('SMS ON NET ILIMITADO')) )  THEN SUM(AMOUNT) ELSE 0 END PAQUETE_SMS,
        CASE WHEN (UPPER(DESCRIPTION) LIKE UPPER('%RRC%') OR UPPER(DESCRIPTION) LIKE UPPER('%MUNDO%')) THEN SUM(AMOUNT) ELSE 0 END PAQUETE_ROAMING,
        CASE WHEN (UPPER(DESCRIPTION) = UPPER('Tráfico de Datos') OR UPPER(DESCRIPTION) = UPPER('Trafico GPRS')) THEN SUM(AMOUNT) ELSE 0 END Trafico_Datos,
        CASE WHEN (UPPER(DESCRIPTION) = UPPER('Mensajes de Texto') OR UPPER(DESCRIPTION) = UPPER('SMS Premium')) THEN SUM(AMOUNT) ELSE 0 END Mensaje_Texto,
        CASE WHEN UPPER(DESCRIPTION) NOT LIKE UPPER('%RPC%') AND (UPPER(DESCRIPTION) NOT LIKE UPPER('%GPRS%')
        AND  UPPER(DESCRIPTION) NOT LIKE UPPER('%INTERNET%') AND (UPPER(DESCRIPTION) NOT LIKE UPPER('%Paq.%GB%'))
        AND (UPPER(DESCRIPTION) NOT LIKE UPPER('%PAQ%MB%')) AND (UPPER(DESCRIPTION) NOT LIKE UPPER('MB ILIMITADOS%'))  )
        AND UPPER(DESCRIPTION) NOT LIKE UPPER('%BLACK%') AND (UPPER(DESCRIPTION) NOT LIKE UPPER('%SMS%')
        AND (UPPER(DESCRIPTION) NOT LIKE '%SMS ON NET ILIMITADO%') ) AND (UPPER(DESCRIPTION) NOT LIKE UPPER('%RRC%')
        AND UPPER(DESCRIPTION) NOT LIKE UPPER('%MUNDO%')) AND UPPER(DESCRIPTION) NOT LIKE UPPER('Tráfico de Datos')
        AND UPPER(DESCRIPTION) NOT LIKE UPPER('Mensajes de Texto') THEN SUM(AMOUNT) ELSE 0 END OTROS
        FROM temp_TAG_1425 T
        WHERE
        T.INVOICENUMBER IN (select invoicenumber from USRAES.Tmp_INVOICENUMBER_{$this->userIdentifier})
        GROUP BY  T.INVOICENUMBER,T.MSISDN,T.DESCRIPTION) K
        GROUP BY K.INVOICENUMBER,K.MSISDN"];

        $queries[] = [
            "sql" => "BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.tmp_fact_deta_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                    IF SQLCODE != -942 THEN
                        RAISE;
                    END IF;
            END;"
        ];
        $queries[] = [
            "sql" => "CREATE TABLE USRAES.tmp_fact_deta_{$this->userIdentifier} as
            SELECT B.CLIENTACCTNO CUENTA_CLIENTE,A.* FROM USRAES.TMP_VISANET_{$this->userIdentifier} A, USRAES.Tmp_INVOICENUMBER_{$this->userIdentifier} B
            WHERE A.INVOICENUMBER = B.INVOICENUMBER"
        ];
        $queries[] = [
            "sql" => "ALTER TABLE USRAES.tmp_fact_deta_{$this->userIdentifier} ADD TRAFICO_LOCAL NUMBER"
        ];
        $queries[] = [
            "sql" => "ALTER TABLE USRAES.tmp_fact_deta_{$this->userIdentifier} ADD SUB_TOTAL NUMBER"
        ];

        $queries[] = [
            "sql" => "BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_TRAFICO_LOCAL_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                    IF SQLCODE != -942 THEN
                        RAISE;
                    END IF;
            END;"
        ];
        $queries[] = ["sql" => "CREATE TABLE USRAES.TMP_TRAFICO_LOCAL_{$this->userIdentifier} AS
        select T.INVOICENUMBER,T.MSISDN,sum(case when TARIFFZONE not like '%LDN%' AND TARIFFZONE not like '%LDI%'
        then CALLTOTAL else 0 end) TRAFICO_LOCAL from temp_tag_1460 T
        WHERE T.INVOICENUMBER IN (select invoicenumber from USRAES.Tmp_INVOICENUMBER_{$this->userIdentifier})
        GROUP BY  T.INVOICENUMBER,T.MSISDN"];
        $queries[] = ["sql" => "DECLARE
            CURSOR Y IS ( SELECT * FROM USRAES.TMP_TRAFICO_LOCAL_{$this->userIdentifier});
        BEGIN
            FOR Y1 IN Y LOOP
                UPDATE USRAES.tmp_fact_deta_{$this->userIdentifier} K
                SET K.TRAFICO_LOCAL = Y1.TRAFICO_LOCAL
                WHERE K.INVOICENUMBER = Y1.INVOICENUMBER
                AND K.MSISDN = Y1.MSISDN;
            END LOOP;
            COMMIT;

            UPDATE USRAES.tmp_fact_deta_{$this->userIdentifier} SET TRAFICO_LOCAL = 0 WHERE TRAFICO_LOCAL IS NULL;
            COMMIT;   
            UPDATE USRAES.tmp_fact_deta_{$this->userIdentifier} SET SUB_TOTAL = RPC + INTERNET + BLACK + PAQUETE_SMS + PAQUETE_ROAMING + TRAFICO_DATOS + MENSAJE_TEXTO + OTROS + TRAFICO_LOCAL;
            COMMIT;
        END;"];

        $this->exec_sql($queries);
    }

    private function exec_sql(array $plsql)
    {

        foreach($plsql as $row){
            if(array_key_exists('params', $row)){
                DB::connection("oracle_dbtodb")->statement(DB::raw($row['sql']), $row['params']);
            }else{
                DB::connection("oracle_dbtodb")->statement(DB::raw($row['sql']));
            }
        }
    }
}
