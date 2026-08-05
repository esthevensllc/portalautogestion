<?php

namespace AMovil\Reports\OltCmts\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\OltCmts\Domain\OltCmtsRepository;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class EloquentOltCmtsRepository implements OltCmtsRepository
{
    private $userIdentifier;
    private $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    public function getReportTypes()
    {
        $data = [
            ["id" => "1", "label" => "OLT"],
            ["id" => "2", "label" => "CMTS"],
        ];
        return json_decode(json_encode($data), false);
    }

    public function getOltsValues(DateTime $fecha)
    {
        $stFecha = $fecha->format("Y-m-d");
        $query = "SELECT devicedisplayname id, devicedisplayname label
        FROM fija_analisis.fija_clientes_ftth
        WHERE tecnologia = 'FTTH' and fecha = toDate('{$stFecha}') and devicedisplayname is not null
        group by 1
        order by devicedisplayname";
        $data = DB::connection("ch-dn09")->select(DB::raw($query));
        return json_decode(json_encode($data), false);
    }

    public function getCmtsValues(DateTime $fecha)
    {
        $stFecha = $fecha->format("Y-m-d");
        $query = "SELECT
        devicedisplayname id, devicedisplayname label
        from fija_analisis.fija_clientes_hfc
        where tecnologia='HFC' and fecha = toDate('{$stFecha}') and devicedisplayname is not null
        group by 1
        order by devicedisplayname";
        $data = DB::connection("ch-dn09")->select(DB::raw($query));
        return json_decode(json_encode($data), false);
    }

    public function getOltFinalReport(array $tickets)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        return DB::connection("oracle")
        ->select(DB::raw("SELECT CUSTOMER_ID_CODCLI
        ,FUENTE
        ,SERIALNUMBER
        ,NOMBRE
        ,ID_CARD_TYPE_VALUE
        ,TELEFONO_CLARO
        ,NUMERO_ADICIONAL
        ,SERVICIO_PRODUCTO
        ,CORREO
        ,DISTRITO
        ,PROVINCIA
        ,DEPARTAMENTO
        ,DESCRIPCION_PRODUCTO
        ,AGREEMENT_STATUS
        ,AGREEMENT_STATUS_DATE
        ,AGREEMENT_START_DATE
        ,AGREEMENT_END_DATE
        ,INSTALLATION_MAP
        ,NUMERO
        ,DNI_RUC
        FROM
        (
            SELECT DISTINCT
            X.CUSTOMER_ID_CODCLI,
            X.FUENTE,
            X.SERIALNUMBER,
            X.NOMBRE,
            X.ID_CARD_TYPE_VALUE,
            X.TELEFONO_CLARO,
            X.NUMERO_ADICIONAL,
            X.SERVICIO_PRODUCTO,
            X.CORREO,
            X.DISTRITO,
            X.PROVINCIA,
            X.DEPARTAMENTO,
            X.DESCRIPCION_PRODUCTO,
            X.AGREEMENT_STATUS,
            X.AGREEMENT_STATUS_DATE,
            X.AGREEMENT_START_DATE,
            X.AGREEMENT_END_DATE,
            X.INSTALLATION_MAP,
            X.NUMERO,
            CASE
            WHEN LENGTH(DNI_RUC) <= 8 AND REGEXP_REPLACE(DNI_RUC, '[0-9]*') IS NOT NULL THEN LPAD(DNI_RUC, 12, '0')
            WHEN LENGTH(DNI_RUC) < 8 THEN LPAD(DNI_RUC, 8, '0')
            WHEN 8 < LENGTH(DNI_RUC) AND LENGTH(DNI_RUC) < 11 THEN LPAD(DNI_RUC, 12, '0')
            ELSE DNI_RUC
            END DNI_RUC,
            ROW_NUMBER() OVER (PARTITION BY DNI_RUC ORDER BY X.NUMERO DESC) ORDEN
            FROM USRAES.OLT_MAC_FINAL_{$this->userIdentifier} x
            JOIN (SELECT CODCLI, CUSTOMER_ID FROM DWS.SA_SOLOT GROUP BY CODCLI, CUSTOMER_ID) Y
            ON X.CUSTOMER_ID_CODCLI=Y.CUSTOMER_ID
            JOIN
            (
                SELECT * FROM USRAES.DWH_DEVOLUCION_MASIV_DETALLE_HIST
                WHERE TICKET IN (:p1, :p2, :p3)
                AND FAMILIA='Telefonia Fija'
            ) Z
            ON Y.CODCLI=Z.CODCLI AND to_number(regexp_replace(X.DNI_RUC, '[^0-9]+', ''))=to_number(regexp_replace(Z.NRO_DOC, '[^0-9]+', ''))
            WHERE SERVICIO_PRODUCTO IN ('PLAN 2 PLAY INTERNET -TELEFONO','PLAN FTTH CN 2PLAY INT - TLF','PLAN FTTH CN 3PLAY','PLAN HFC 3PLAY','PLAN 1 PLAY TELEFONIA')
            AND DESCRIPCION_PRODUCTO LIKE '%TELEFONIA%'
            
            UNION ALL
            
            SELECT DISTINCT
            X.CUSTOMER_ID_CODCLI,
            X.FUENTE,
            X.SERIALNUMBER,
            X.NOMBRE,
            X.ID_CARD_TYPE_VALUE,
            X.TELEFONO_CLARO,
            X.NUMERO_ADICIONAL,
            X.SERVICIO_PRODUCTO,
            X.CORREO,
            X.DISTRITO,
            X.PROVINCIA,
            X.DEPARTAMENTO,
            X.DESCRIPCION_PRODUCTO,
            X.AGREEMENT_STATUS,
            X.AGREEMENT_STATUS_DATE,
            X.AGREEMENT_START_DATE,
            X.AGREEMENT_END_DATE,
            X.INSTALLATION_MAP,
            X.NUMERO,
            CASE
            WHEN LENGTH(DNI_RUC) <= 8 AND REGEXP_REPLACE(DNI_RUC, '[0-9]*') IS NOT NULL THEN LPAD(DNI_RUC, 12, '0')
            WHEN LENGTH(DNI_RUC) < 8 THEN LPAD(DNI_RUC, 8, '0')
            WHEN 8 < LENGTH(DNI_RUC) AND LENGTH(DNI_RUC) < 11 THEN LPAD(DNI_RUC, 12, '0')
            ELSE DNI_RUC
            END DNI_RUC,
            ROW_NUMBER() OVER (PARTITION BY DNI_RUC ORDER BY X.NUMERO DESC) ORDEN
            FROM USRAES.OLT_MAC_FINAL_{$this->userIdentifier} x
            JOIN (SELECT CODCLI, CUSTOMER_ID FROM DWS.SA_SOLOT GROUP BY CODCLI, CUSTOMER_ID) Y
            ON X.CUSTOMER_ID_CODCLI=Y.CUSTOMER_ID
            JOIN
            (
                SELECT * FROM USRAES.DWH_DEVOLUCION_MASIV_DETALLE_HIST
                WHERE TICKET IN (:p1, :p2, :p3)
                AND FAMILIA='Acceso Dedicado a Internet'
            ) Z
            ON Y.CODCLI=Z.CODCLI AND to_number(regexp_replace(X.DNI_RUC, '[^0-9]+', ''))=to_number(regexp_replace(Z.NRO_DOC, '[^0-9]+', ''))
            WHERE SERVICIO_PRODUCTO IN ('PLAN FTTH CN 3PLAY','PLAN HFC 3PLAY','PLAN 1 PLAY INTERNET','PLAN 2 PLAY CABLE - INTERNET','PLAN 2 PLAY INTERNET -TELEFONO','PLAN FTTH CN 2PLAY INT - TLF','PLAN FTTH CN 2PLAY TV - INT','PLAN HFC CN 1PLAY INT')
            AND DESCRIPCION_PRODUCTO LIKE '%MBPS%'
            
            UNION ALL
            
            SELECT DISTINCT
            X.CUSTOMER_ID_CODCLI,
            X.FUENTE,
            X.SERIALNUMBER,
            X.NOMBRE,
            X.ID_CARD_TYPE_VALUE,
            X.TELEFONO_CLARO,
            X.NUMERO_ADICIONAL,
            X.SERVICIO_PRODUCTO,
            X.CORREO,
            X.DISTRITO,
            X.PROVINCIA,
            X.DEPARTAMENTO,
            X.DESCRIPCION_PRODUCTO,
            X.AGREEMENT_STATUS,
            X.AGREEMENT_STATUS_DATE,
            X.AGREEMENT_START_DATE,
            X.AGREEMENT_END_DATE,
            X.INSTALLATION_MAP,
            X.NUMERO,
            CASE
            WHEN LENGTH(DNI_RUC) <= 8 AND REGEXP_REPLACE(DNI_RUC, '[0-9]*') IS NOT NULL THEN LPAD(DNI_RUC, 12, '0')
            WHEN LENGTH(DNI_RUC) < 8 THEN LPAD(DNI_RUC, 8, '0')
            WHEN 8 < LENGTH(DNI_RUC) AND LENGTH(DNI_RUC) < 11 THEN LPAD(DNI_RUC, 12, '0')
            ELSE DNI_RUC
            END DNI_RUC,
            ROW_NUMBER() OVER (PARTITION BY DNI_RUC ORDER BY X.NUMERO DESC) ORDEN
            FROM USRAES.OLT_MAC_FINAL_{$this->userIdentifier} x
            JOIN (SELECT CODCLI, CUSTOMER_ID FROM DWS.SA_SOLOT GROUP BY CODCLI, CUSTOMER_ID) Y
            ON X.CUSTOMER_ID_CODCLI=Y.CUSTOMER_ID
            JOIN
            (
                SELECT * FROM USRAES.DWH_DEVOLUCION_MASIV_DETALLE_HIST
                WHERE TICKET IN (:p1, :p2, :p3)
                AND TRIM(FAMILIA)='Cable'
            ) Z
            ON Y.CODCLI=Z.CODCLI AND to_number(regexp_replace(X.DNI_RUC, '[^0-9]+', ''))=to_number(regexp_replace(Z.NRO_DOC, '[^0-9]+', ''))
            WHERE SERVICIO_PRODUCTO IN ('PLAN 2 PLAY CABLE - INTERNET','PLAN FTTH CN 2PLAY TV - INT','PLAN FTTH CN 3PLAY','PLAN HFC 3PLAY')
            AND DESCRIPCION_PRODUCTO LIKE '%TV%'
        )
        WHERE ORDEN=1"), ["p1" => $tickets[0], "p2" => $tickets[1], "p3" => $tickets[2]]);
    }

    public function getCmtsFinalReport(array $tickets)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        return DB::connection("oracle")
        ->select(DB::raw("SELECT CUSTOMER_ID_CODCLI
        ,FUENTE
        ,SERIALNUMBER
        ,NOMBRE
        ,ID_CARD_TYPE_VALUE
        ,TELEFONO_CLARO
        ,NUMERO_ADICIONAL
        ,SERVICIO_PRODUCTO
        ,CORREO
        ,DISTRITO
        ,PROVINCIA
        ,DEPARTAMENTO
        ,DESCRIPCION_PRODUCTO
        ,AGREEMENT_STATUS
        ,AGREEMENT_STATUS_DATE
        ,AGREEMENT_START_DATE
        ,AGREEMENT_END_DATE
        ,INSTALLATION_MAP
        ,NUMERO
        ,DNI_RUC
        FROM
        (
            SELECT DISTINCT
            X.CUSTOMER_ID_CODCLI,
            X.FUENTE,
            X.SERIALNUMBER,
            X.NOMBRE,
            X.ID_CARD_TYPE_VALUE,
            X.TELEFONO_CLARO,
            X.NUMERO_ADICIONAL,
            X.SERVICIO_PRODUCTO,
            X.CORREO,
            X.DISTRITO,
            X.PROVINCIA,
            X.DEPARTAMENTO,
            X.DESCRIPCION_PRODUCTO,
            X.AGREEMENT_STATUS,
            X.AGREEMENT_STATUS_DATE,
            X.AGREEMENT_START_DATE,
            X.AGREEMENT_END_DATE,
            X.INSTALLATION_MAP,
            X.NUMERO,
            CASE
            WHEN LENGTH(DNI_RUC) <= 8 AND REGEXP_REPLACE(DNI_RUC, '[0-9]*') IS NOT NULL THEN LPAD(DNI_RUC, 12, '0')
            WHEN LENGTH(DNI_RUC) < 8 THEN LPAD(DNI_RUC, 8, '0')
            WHEN 8 < LENGTH(DNI_RUC) AND LENGTH(DNI_RUC) < 11 THEN LPAD(DNI_RUC, 12, '0')
            ELSE DNI_RUC
            END DNI_RUC,
            ROW_NUMBER() OVER (PARTITION BY DNI_RUC ORDER BY X.NUMERO DESC) ORDEN
            FROM USRAES.cmts_table_final_{$this->userIdentifier} X
            JOIN (SELECT CODCLI, CUSTOMER_ID FROM DWS.SA_SOLOT GROUP BY CODCLI, CUSTOMER_ID) Y
            ON X.CUSTOMER_ID_CODCLI=Y.CUSTOMER_ID
            JOIN
            (
                SELECT * FROM USRAES.DWH_DEVOLUCION_MASIV_DETALLE_HIST
                WHERE TICKET IN (:p1, :p2, :p3)
                AND FAMILIA='Telefonia Fija'
            ) Z
            ON Y.CODCLI=Z.CODCLI AND to_number(regexp_replace(X.DNI_RUC, '[^0-9]+', ''))=to_number(regexp_replace(Z.NRO_DOC, '[^0-9]+', ''))
            WHERE SERVICIO_PRODUCTO IN ('PLAN 2 PLAY INTERNET -TELEFONO','PLAN FTTH CN 2PLAY INT - TLF','PLAN FTTH CN 3PLAY','PLAN HFC 3PLAY','PLAN 1 PLAY TELEFONIA')
            AND TO_DATE(X.AGREEMENT_START_DATE,'YYYY-MM-DD')<=FEC_INI_INCIDENCIA AND DESCRIPCION_PRODUCTO LIKE '%TELEFONIA%'
            
            UNION ALL
            
            SELECT DISTINCT
            X.CUSTOMER_ID_CODCLI,
            X.FUENTE,
            X.SERIALNUMBER,
            X.NOMBRE,
            X.ID_CARD_TYPE_VALUE,
            X.TELEFONO_CLARO,
            X.NUMERO_ADICIONAL,
            X.SERVICIO_PRODUCTO,
            X.CORREO,
            X.DISTRITO,
            X.PROVINCIA,
            X.DEPARTAMENTO,
            X.DESCRIPCION_PRODUCTO,
            X.AGREEMENT_STATUS,
            X.AGREEMENT_STATUS_DATE,
            X.AGREEMENT_START_DATE,
            X.AGREEMENT_END_DATE,
            X.INSTALLATION_MAP,
            X.NUMERO,
            CASE
            WHEN LENGTH(DNI_RUC) <= 8 AND REGEXP_REPLACE(DNI_RUC, '[0-9]*') IS NOT NULL THEN LPAD(DNI_RUC, 12, '0')
            WHEN LENGTH(DNI_RUC) < 8 THEN LPAD(DNI_RUC, 8, '0')
            WHEN 8 < LENGTH(DNI_RUC) AND LENGTH(DNI_RUC) < 11 THEN LPAD(DNI_RUC, 12, '0')
            ELSE DNI_RUC
            END DNI_RUC,
            ROW_NUMBER() OVER (PARTITION BY DNI_RUC ORDER BY X.NUMERO DESC) ORDEN
            FROM USRAES.cmts_table_final_{$this->userIdentifier} X
            JOIN (SELECT CODCLI, CUSTOMER_ID FROM DWS.SA_SOLOT GROUP BY CODCLI, CUSTOMER_ID) Y
            ON X.CUSTOMER_ID_CODCLI=Y.CUSTOMER_ID
            JOIN
            (
                SELECT * FROM USRAES.DWH_DEVOLUCION_MASIV_DETALLE_HIST
                WHERE TICKET IN (:p1, :p2, :p3)
                AND FAMILIA='Acceso Dedicado a Internet'
            ) Z
            ON Y.CODCLI=Z.CODCLI AND to_number(regexp_replace(X.DNI_RUC, '[^0-9]+', ''))=to_number(regexp_replace(Z.NRO_DOC, '[^0-9]+', ''))
            WHERE SERVICIO_PRODUCTO IN ('PLAN FTTH CN 3PLAY','PLAN HFC 3PLAY','PLAN 1 PLAY INTERNET','PLAN 2 PLAY CABLE - INTERNET','PLAN 2 PLAY INTERNET -TELEFONO','PLAN FTTH CN 2PLAY INT - TLF','PLAN FTTH CN 2PLAY TV - INT','PLAN HFC CN 1PLAY INT')
            AND TO_DATE(X.AGREEMENT_START_DATE,'YYYY-MM-DD')<=FEC_INI_INCIDENCIA AND DESCRIPCION_PRODUCTO LIKE '%MBPS%'
            
            UNION ALL
            
            SELECT DISTINCT
            X.CUSTOMER_ID_CODCLI,
            X.FUENTE,
            X.SERIALNUMBER,
            X.NOMBRE,
            X.ID_CARD_TYPE_VALUE,
            X.TELEFONO_CLARO,
            X.NUMERO_ADICIONAL,
            X.SERVICIO_PRODUCTO,
            X.CORREO,
            X.DISTRITO,
            X.PROVINCIA,
            X.DEPARTAMENTO,
            X.DESCRIPCION_PRODUCTO,
            X.AGREEMENT_STATUS,
            X.AGREEMENT_STATUS_DATE,
            X.AGREEMENT_START_DATE,
            X.AGREEMENT_END_DATE,
            X.INSTALLATION_MAP,
            X.NUMERO,
            CASE
            WHEN LENGTH(DNI_RUC) <= 8 AND REGEXP_REPLACE(DNI_RUC, '[0-9]*') IS NOT NULL THEN LPAD(DNI_RUC, 12, '0')
            WHEN LENGTH(DNI_RUC) < 8 THEN LPAD(DNI_RUC, 8, '0')
            WHEN 8 < LENGTH(DNI_RUC) AND LENGTH(DNI_RUC) < 11 THEN LPAD(DNI_RUC, 12, '0')
            ELSE DNI_RUC
            END DNI_RUC,
            ROW_NUMBER() OVER (PARTITION BY X.DNI_RUC ORDER BY X.NUMERO DESC) ORDEN
            FROM USRAES.cmts_table_final_{$this->userIdentifier} X
            JOIN (SELECT CODCLI, CUSTOMER_ID FROM DWS.SA_SOLOT GROUP BY CODCLI, CUSTOMER_ID) Y
            ON X.CUSTOMER_ID_CODCLI=Y.CUSTOMER_ID
            JOIN
            (
                SELECT * FROM USRAES.DWH_DEVOLUCION_MASIV_DETALLE_HIST
                WHERE TICKET IN  (:p1, :p2, :p3)
                AND TRIM(FAMILIA)='Cable'
            ) Z
            ON Y.CODCLI=Z.CODCLI AND to_number(regexp_replace(X.DNI_RUC, '[^0-9]+', ''))=to_number(regexp_replace(Z.NRO_DOC, '[^0-9]+', ''))
            WHERE SERVICIO_PRODUCTO IN ('PLAN 2 PLAY CABLE - INTERNET','PLAN FTTH CN 2PLAY TV - INT','PLAN FTTH CN 3PLAY','PLAN HFC 3PLAY')
            AND TO_DATE(X.AGREEMENT_START_DATE,'YYYY-MM-DD')<=FEC_INI_INCIDENCIA AND DESCRIPCION_PRODUCTO LIKE '%TV%'
        )
        WHERE ORDEN=1"), ["p1" => $tickets[0], "p2" => $tickets[1], "p3" => $tickets[2]]);
    }

    public function getOltReport(DateTime $fecha, array $olts)
    {
        $this->generateReport(1, $fecha, $olts);
        return DB::connection("oracle")->select(DB::raw("SELECT 
        CUSTOMER_ID_CODCLI,
        FUENTE,
        SERIALNUMBER,
        NOMBRE,
        ID_CARD_TYPE_VALUE,
        TELEFONO_CLARO,
        NUMERO_ADICIONAL,
        SERVICIO_PRODUCTO,
        CORREO,
        DISTRITO,
        PROVINCIA,
        DEPARTAMENTO,
        DESCRIPCION_PRODUCTO,
        AGREEMENT_STATUS,
        AGREEMENT_STATUS_DATE,
        AGREEMENT_START_DATE,
        AGREEMENT_END_DATE,
        INSTALLATION_MAP,
        NUMERO, 
        CASE
        WHEN LENGTH(DNI_RUC) <= 8 AND REGEXP_REPLACE(DNI_RUC, '[0-9]*') IS NOT NULL THEN LPAD(DNI_RUC, 12, '0')
        WHEN LENGTH(DNI_RUC) < 8 THEN LPAD(DNI_RUC, 8, '0')
        WHEN 8 < LENGTH(DNI_RUC) AND LENGTH(DNI_RUC) < 11 THEN LPAD(DNI_RUC, 12, '0')
        ELSE DNI_RUC
        END DNI_RUC FROM USRAES.OLT_MAC_FINAL_{$this->userIdentifier}"));
    }

    public function getCmtsReport(DateTime $fecha, array $cmts)
    {
        $this->generateReport(2, $fecha, $cmts);
        return DB::connection("oracle")->select(DB::raw("SELECT 
        CUSTOMER_ID_CODCLI,
        FUENTE,
        SERIALNUMBER,
        NOMBRE,
        ID_CARD_TYPE_VALUE,
        TELEFONO_CLARO,
        NUMERO_ADICIONAL,
        SERVICIO_PRODUCTO,
        CORREO,
        DISTRITO,
        PROVINCIA,
        DEPARTAMENTO,
        DESCRIPCION_PRODUCTO,
        AGREEMENT_STATUS,
        AGREEMENT_STATUS_DATE,
        AGREEMENT_START_DATE,
        AGREEMENT_END_DATE,
        INSTALLATION_MAP,
        NUMERO, 
        CASE
        WHEN LENGTH(DNI_RUC) <= 8 AND REGEXP_REPLACE(DNI_RUC, '[0-9]*') IS NOT NULL THEN LPAD(DNI_RUC, 12, '0')
        WHEN LENGTH(DNI_RUC) < 8 THEN LPAD(DNI_RUC, 8, '0')
        WHEN 8 < LENGTH(DNI_RUC) AND LENGTH(DNI_RUC) < 11 THEN LPAD(DNI_RUC, 12, '0')
        ELSE DNI_RUC
        END DNI_RUC FROM USRAES.cmts_table_final_{$this->userIdentifier}"));
    }

    private function generateReport(int $type_id, DateTime $fecha, array $values)
    {
        $now = new DateTime();
        $diff = $fecha->diff($now);
        // $strValues = implode("','", $olts);
        $this->userIdentifier = $this->authService->getUserIdentifier();
        

        $request = [
            "type_id" => $type_id,
            "days" => "{$diff->days}",
            "values" => $values
        ];
        $http_response = Http::withHeaders([
            "x-user-identifier" => $this->userIdentifier,
            // "Content-Type" => "application/json"
        ])
        ->timeout(-1)
        ->post(env("APP_API")."/olt-cmts/process", $request);

        if($http_response->getStatusCode() !== 200){
            $error_message = $http_response->body();
            throw new Exception(json_encode($error_message));
        }
    }

    public function getOltListSummary()
    {
        $query = "SELECT min(fecha) fecha_min, max(fecha) fecha_max FROM portal_autogestion.list_olt_1day";
        $result = DB::connection("ch-dn05")->select($query);
        return json_decode(json_encode($result[0]), false);
    }
}
