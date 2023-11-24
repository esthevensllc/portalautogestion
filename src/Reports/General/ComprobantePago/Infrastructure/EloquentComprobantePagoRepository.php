<?php

namespace AMovil\Reports\General\ComprobantePago\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\General\ComprobantePago\Domain\ComprobantePagoRepository;
use Illuminate\Support\Facades\DB;

class EloquentComprobantePagoRepository implements ComprobantePagoRepository
{
    private $authService;
    private $userIdentifier;
    private $connection = "oracle";

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function getReporte(array $values)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        $queries = [];
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_COMPROBANTES_INPUT_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $queries[] = [
            "sql" => "CREATE TABLE USRAES.TMP_COMPROBANTES_INPUT_{$this->userIdentifier} (
                plataforma VARCHAR2(100),
                nro_comprobante VARCHAR2(50),
                fecemi DATE
            )"
        ];
        $this->exec_sql($queries);
        foreach($values as $value){
            DB::connection($this->connection)
            ->table("USRAES.TMP_COMPROBANTES_INPUT_{$this->userIdentifier}")
            ->insert($value);
        }

        // $queries = [];
        // $queries[] = ["sql" => ""];
        // $this->exec_sql($queries);

        return DB::connection($this->connection)
        ->select(DB::raw("WITH TMP_SGA_{$this->userIdentifier} AS (
            SELECT CODCLI,
                IDFAC,
                SERSUT || '-' || NUMSUT NRO_COMPROBANTE,
                FECEMI,
                TIPDOC,
                IDCON IDCON
            FROM DWS.SA_CXCTABFAC A
            WHERE EXISTS (
                SELECT 1
                FROM TMP_COMPROBANTES_INPUT_{$this->userIdentifier} X
                WHERE X.PLATAFORMA = 'SGA'
                    AND SUBSTR(NRO_COMPROBANTE, 1, 4) = A.SERSUT
                    AND INSTR(A.NUMSUT, SUBSTR(NRO_COMPROBANTE, 6)) >= 1
                    AND FECEMI = A.FECEMI
            )
        ) SELECT
            /*+ PARALLEL (8)*/
            'BSCS' PLATAFORMA,
            SUBSTR(O.OHREFNUM, 1, 4) || '-' || SUBSTR(O.OHREFNUM, 5, 30) NRO_COMPROBANTE,
            O.OHREFDATE FECHA_EMISION,
            C.CCNAME RAZON_SOCIAL,
            TO_CHAR(O.CUSTOMER_ID) CUSTOMER_ID,
            O.OHSTATUS TIPO_COMPROBANTE,
            'N.A.' IDCON,
            'N.A.' IDCON_ASOCIADO_NC_ND
        FROM DWS.SA_BSCSIX_ORDERHDR_ALL O
            JOIN DWS.SA_BSCSIX_CCONTACT_ALL C ON C.CUSTOMER_ID = O.CUSTOMER_ID
            AND C.CCBILL = 'X'
        WHERE O.OHSTATUS IN ('IN', 'CM') -- FACTURAS Y N/C, N/D -- TODOS SON DE LONGITUD 16
            AND EXISTS (
                SELECT 1
                FROM TMP_COMPROBANTES_INPUT_{$this->userIdentifier} X
                WHERE X.PLATAFORMA = 'BSCS'
                    AND X.NRO_COMPROBANTE = SUBSTR(O.OHREFNUM, 1, 4) || '-' || SUBSTR(O.OHREFNUM, 5, 30)
                    AND X.FECEMI = O.OHREFDATE
            )
        UNION ALL
        -- INFORMACION DE BSCS07:
        SELECT
            /*+ PARALLEL (8)*/
            'BSCS' PLATAFORMA,
            CASE
                WHEN O.OHENTDATE < TO_DATE('01/10/2020', 'DD/MM/YYYY') THEN 'T001'
                ELSE 'SB01'
            END || '-' || SUBSTR(O.OHREFNUM, 1, 10) NRO_COMPROBANTE,
            O.OHREFDATE FECHA_EMISION,
            C.CCNAME RAZON_SOCIAL,
            TO_CHAR(O.CUSTOMER_ID) CUSTOMER_ID,
            O.OHSTATUS TIPO_COMPROBANTE,
            'N.A.' IDCON,
            'N.A.' IDCON_ASOCIADO_NC_ND
        FROM DMRED.ORDERHDR_ALL O
            JOIN DMRED.CCONTACT_ALL C ON C.CUSTOMER_ID = O.CUSTOMER_ID
            AND C.CCBILL = 'X'
        WHERE O.OHSTATUS IN ('IN', 'CM') -- FACTURAS Y N/C, N/D 
            AND EXISTS (
                SELECT 1
                FROM TMP_COMPROBANTES_INPUT_{$this->userIdentifier} X
                WHERE X.PLATAFORMA = 'BSCS' --AND SUBSTR(X.NRO_COMPROBANTE,5,10) = SUBSTR(O.OHREFNUM,1,10) 
                    AND X.NRO_COMPROBANTE = (
                        CASE
                            WHEN O.OHENTDATE < TO_DATE('01/10/2020', 'DD/MM/YYYY') THEN 'T001'
                            ELSE 'SB01'
                        END || '-' || SUBSTR(O.OHREFNUM, 1, 10)
                    )
                    AND X.FECEMI = O.OHREFDATE
            )
        UNION ALL
        -- INFORMACION DE SGA:
        SELECT 'SGA' PLATAFORMA,
            A.NRO_COMPROBANTE,
            A.FECEMI FECHA_EMISION,
            D.NOMCLI RAZON_SOCIAL,
            'N.A.' CUSTOMER_ID,
            TO_CHAR(A.TIPDOC) TIPO_COMPROBANTE,
            TO_CHAR(A.IDCON) IDCON,
            NVL(TO_CHAR(C.IDCON), 'N.A.') IDCON_ASOCIADO_NC_ND
        FROM TMP_SGA_{$this->userIdentifier} A
            LEFT JOIN DWS.SA_CXCFAC_ORI B ON B.IDFAC = A.IDFAC
            LEFT JOIN DWS.SA_CXCTABFAC C ON C.IDFAC = B.IDFAC_ORI
            LEFT JOIN DWS.SA_VTATABCLI D ON D.CODCLI = A.CODCLI"));
    }

    private function exec_sql(array $plsql)
    {
        foreach($plsql as $row){
            if(array_key_exists('params', $row)){
                DB::connection($this->connection)->statement(DB::Raw($row['sql']), $row['params']);
            }else{
                DB::connection($this->connection)->statement(DB::Raw($row['sql']));
            }
        }
    }
}
