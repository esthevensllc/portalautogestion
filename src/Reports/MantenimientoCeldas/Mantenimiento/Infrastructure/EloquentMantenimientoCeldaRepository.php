<?php

namespace AMovil\Reports\MantenimientoCeldas\Mantenimiento\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\MantenimientoCeldas\Mantenimiento\Domain\MantenimientoCeldaRepository;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;

class EloquentMantenimientoCeldaRepository implements MantenimientoCeldaRepository
{
    private $connection = "oracle_reptdm";
    private $userIdentifier;
    private $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function process(array $celdas, array $provincias, DateTime $fechaIni, DateTime $fechaFin, $ticketOsiptel, DateTime $fechaInteres, DateTime $corteFechaIni, Datetime $corteFechaFin)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        $fechaActual = new DateTime();
        $fechaActual->modify("-1 day");

        $strFechaIniF1 = $fechaIni->format("Ymd");
        $strFechaIniF3 = $fechaIni->format("YmdHis");
        // $strPeriodo = $fechaActual->format("Ym");
        $strFechaFinF3 = $fechaFin->format("YmdHis");

        //$corteDiffMinutos = $corteFechaIni->format("YmdHis") - $corteFechaFin->format("YmdHis");
        $corteDiffMinutos = $corteFechaFin->getTimestamp() - $corteFechaIni->getTimestamp();
        $corteDiffMinutos = floor($corteDiffMinutos/60);

        // $strCorteFechaIniF1 = $corteFechaIni->format("YmdHis");
        // $strCorteFechaFinF2 = $corteFechaFin->modify("+3 minute")->format("YmdHis");

        $allQueries = [];
        $queries = [];
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.M_TABLE_CELDAS_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.M_DEP_PRO_DIS_TMP_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.M_TABLE_CELDAS_{$this->userIdentifier} (CELDA VARCHAR2(25)) tablespace WORKAREA"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.M_DEP_PRO_DIS_TMP_{$this->userIdentifier} (
            DEPARTAMENTO VARCHAR2(100),
            PROVINCIA VARCHAR2(100),
            DISTRITO VARCHAR2(100),
            CCPP VARCHAR2(100),
            UBIGEO VARCHAR2(100)
        ) tablespace WORKAREA"];

        $this->exec_sql($queries);

        foreach ($celdas as $value) {
            DB::connection($this->connection)->table("USRAES.M_TABLE_CELDAS_{$this->userIdentifier}")->insert(["celda" => $value]);
        }
        foreach ($provincias as $row) {
            DB::connection($this->connection)->table("USRAES.M_DEP_PRO_DIS_TMP_{$this->userIdentifier}")->insert([
                "departamento" => $row[0],
                "provincia" => $row[1],
                "distrito" => $row[2],
                // "ccpp" => $row[3],
                // "ubigeo" => $row[4],
            ]);
        }
        $allQueries = array_merge($allQueries, $queries);
        $queries = [];

        $dwh_validation = DB::connection($this->connection)
        ->select(DB::raw("SELECT
        count(*) as counter
        FROM all_tab_partitions
        WHERE table_name = 'CDR_DWH'
        and segment_created = 'YES'
        AND NUM_ROWS IS NOT NULL
        AND NUM_ROWS<>0
        AND replace(PARTITION_name, 'CDR_DWH_') = :p_fecha_ini"), ["p_fecha_ini" => $strFechaIniF1])[0];

        if($dwh_validation->counter > 0){
            $queries[] = ["sql" => "BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.M_T_USER_VOZ_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                    IF SQLCODE != -942 THEN
                        RAISE;
                    END IF;
            END;"];
            $queries[] = ["sql" => "BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.M_T_U_VOZ_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                    IF SQLCODE != -942 THEN
                        RAISE;
                    END IF;
            END;"];
            $queries[] = ["sql" => "CREATE TABLE USRAES.M_T_U_VOZ_{$this->userIdentifier} tablespace WORKAREA AS
            SELECT /*+ PARALLEL(16)*/
            'VOZ' SERVICIO,
            cdr.tim_number MSISDN
            ,cdr.tim_number_subs_firts_ci CELDA
            ,cdr.charging_start_time FECHA
            ,cdr.record_type TIPO
            ,cdr.number_b NUMBER_B
            FROM dm.cdr_dwh PARTITION(CDR_DWH_{$strFechaIniF1}) cdr -- <--FECHA_INI O FECHA_FIN
            WHERE
            cdr.tim_number_subs_firts_ci in (SELECT CELDA FROM USRAES.M_TABLE_CELDAS_{$this->userIdentifier} GROUP BY CELDA)
            AND cdr.charging_start_time BETWEEN '{$strFechaIniF3}' -- < COLOCAR CONCAT FECHA_INI + (HORA_INI - 00:10:00) 
            AND '{$strFechaFinF3}' -- < COLOCAR CONCAT FECHA_FIN + (HORA_FIN + 00:03:00)
            AND cdr.record_type IN ('01','03','08')"];
            $queries[] = ["sql" => "CREATE TABLE USRAES.M_T_USER_VOZ_{$this->userIdentifier} tablespace WORKAREA AS
            SELECT SERVICIO,MSISDN,CELDA,FECHA FROM USRAES.M_T_U_VOZ_{$this->userIdentifier}
            WHERE
            SUBSTR(MSISDN,1,3)='519'
            AND LENGTH(MSISDN)>10
            AND LENGTH(number_b)>=10"];
        }else{
            throw new Exception("No existe o no hay datos para la partición 'CDR_DWH_{$strFechaIniF1}' de la tabla dm.cdr_dwh en REPTDM");
        }
        
        $table_validation = DB::connection($this->connection)
        ->select(DB::raw("SELECT
        count(*) as counter
        FROM all_tab_partitions
        WHERE table_name = 'CDR_GPRS'
        and segment_created = 'YES'
        AND NUM_ROWS IS NOT NULL
        AND NUM_ROWS<>0
        AND replace(PARTITION_name, 'P_') = :p_fecha_ini"), ["p_fecha_ini" => $strFechaIniF1])[0];

        if($table_validation->counter > 0){
            $queries[] = ["sql" => "BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.M_TMP_USER_DATOS_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                    IF SQLCODE != -942 THEN
                        RAISE;
                    END IF;
            END;"];
            $queries[] = ["sql" => "CREATE TABLE USRAES.M_TMP_USER_DATOS_{$this->userIdentifier} tablespace WORKAREA AS
            SELECT SERVICIO,MSISDN,CELDA,FECHA FROM (
            SELECT 
            'DATOS' SERVICIO,
            gp.served_msisdn MSISDN
            ,gp.cell_identity CELDA
            ,gp.s_uplink UPLINK
            ,gp.s_downlink DOWNLINK
            ,TO_CHAR(gp.s_rec_opening_time,'YYYYMMDDHH24MISS') FECHA
            FROM dm.cdr_gprs PARTITION(P_{$strFechaIniF1}) gp
            WHERE
            gp.cell_identity in (SELECT CELDA FROM USRAES.M_TABLE_CELDAS_{$this->userIdentifier} GROUP BY CELDA)
            AND TO_CHAR(gp.s_rec_opening_time,'YYYYMMDDHH24MISS') BETWEEN '{$strFechaIniF3}' -- < COLOCAR CONCAT FECHA_INI + (HORA_INI - 00:10:00) 
            AND '{$strFechaFinF3}' -- < COLOCAR CONCAT FECHA_FIN + (HORA_FIN + 00:03:00)
            and (gp.s_uplink + gp.s_downlink)>0)"];
        }else{
            throw new Exception("No existe o no hay datos para la partición 'P_{$strFechaIniF1}' de la tabla dm.cdr_gprs en REPTDM");
        }

        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.M_TMP_USER_BASE_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.M_TMP_USER_BASE_{$this->userIdentifier} tablespace WORKAREA AS
        SELECT SERVICIO,MSISDN,TO_CHAR(CELDA) CELDA,FECHA FROM USRAES.M_TMP_USER_DATOS_{$this->userIdentifier}
        UNION ALL
        SELECT * FROM USRAES.M_T_USER_VOZ_{$this->userIdentifier}"];

        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.M_TMP_USER_UNICOS_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.M_TMP_USER_UNICOS_{$this->userIdentifier} tablespace WORKAREA AS
        SELECT '{$ticketOsiptel}' TICKET, -- <---- Ticket_osiptel
        MSISDN,SYSDATE FECHA_CARGA,MAX(CELDA) CELDA 
        FROM USRAES.M_TMP_USER_BASE_{$this->userIdentifier} group BY MSISDN"];

        $this->exec_sql($queries);

        $result = DB::connection($this->connection)
        ->select(DB::raw("select count(distinct MSISDN) counter from USRAES.M_TMP_USER_UNICOS_{$this->userIdentifier}"));

        // reporte parte 2
        $this->userIdentifier = $this->authService->getUserIdentifier();

        $strProvincias = [];
        foreach($provincias as $row){
            $strProvincias[] = implode(",", $row);
        }
        $strProvincias = implode("\n", $strProvincias);

        DB::connection($this->connection)
        ->table("USRAES.M_BASE_PREV_BASEDEV_INPUT")
        ->where("ticket", $ticketOsiptel)
        ->where("departamento", $provincias[0][0])
        ->delete();

        DB::connection($this->connection)
        ->table("USRAES.M_BASE_PREV_BASEDEV_INPUT")
        ->insert([
            "departamento" => $provincias[0][0],
            //"celda" => implode(",", $celdas),
            "provincia" => $strProvincias,
            "ticket" => $ticketOsiptel,
            "fecha_ini" => $fechaIni->format("Y-m-d H:i:s"),
            "fecha_fin" => $fechaFin->format("Y-m-d H:i:s"),
            "corte_fecha_ini" => $corteFechaIni->format("Y-m-d H:i:s"),
            "corte_fecha_fin" => $corteFechaFin->format("Y-m-d H:i:s"),
            "fecha_interes" => $fechaInteres->format("Y-m-d H:i:s"),
        ]);

        DB::connection($this->connection)
        ->table("USRAES.M_BASE_PREV_BASEDEV_INPUT_CELDA")
        ->where("ticket", $ticketOsiptel)
        ->where("departamento", $provincias[0][0])
        ->delete();

        DB::connection($this->connection)
        ->statement("BEGIN
            INSERT INTO USRAES.M_BASE_PREV_BASEDEV_INPUT_CELDA(TICKET, DEPARTAMENTO, CELDA)
            SELECT :p_ticket AS TICKET, :p_departamento AS DEPARTAMENTO, CELDA FROM USRAES.M_TABLE_CELDAS_{$this->userIdentifier};
            COMMIT;
        END;", ["p_ticket" => $ticketOsiptel, "p_departamento" => $provincias[0][0]]);
        
        $fechaActual = new DateTime();
        $fechaActual->modify("-1 day");

        // $strFechaIniF1 = $fechaIni->format("Ymd");
        // $strFechaIniF3 = $fechaIni->format("YmdHis");
        $strPeriodo = $fechaActual->format("Ym");
        $strFechaFinF3 = $fechaFin->format("YmdHis");
        // $strFechaIniF2 = $fechaIni->modify("-10 minute")->format("YmdHis");
        // $strFechaFinF2 = $fechaFin->modify("+3 minute")->format("YmdHis");

        //$corteDiffMinutos = $corteFechaIni->format("YmdHis") - $corteFechaFin->format("YmdHis");
        $corteDiffMinutos = $corteFechaFin->getTimestamp() - $corteFechaIni->getTimestamp();
        $corteDiffMinutos = floor($corteDiffMinutos/60);

        $strCorteFechaIniF1 = $corteFechaIni->format("YmdHis");
        $strCorteFechaFinF2 = $corteFechaFin->modify("+3 minute")->format("YmdHis");
        // $strFechaFinF4 = $corteFechaFin->modify("+3 minute")->format("YmdHis");

        $allQueries = [];
        $queries = [];
        
        // parte 2

        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.M_TMP_USER_DEP_PRO_DIS_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.M_TMP_USER_DEP_PRO_DIS_{$this->userIdentifier} tablespace WORKAREA AS
        SELECT DISTINCT 
               TK.*,
               TO_DATE('{$strFechaFinF3}','YYYYMMDDHH24MISS') FECHA_CORTE, -- (FECHA_FIN + HORA_FIN)
               cr.departamento,
               cr.provincia,
               cr.distrito
        FROM USRAES.M_TMP_USER_UNICOS_{$this->userIdentifier} TK
        ,dm.cdr_celdas_red cr
        WHERE TK.celda=cr.cell_id
        AND translate(UPPER(cr.departamento), 'áéíóúÁÉÍÓÚ', 'aeiouAEIOU') IN 
        (SELECT DEPARTAMENTO FROM USRAES.M_DEP_PRO_DIS_TMP_{$this->userIdentifier} GROUP BY DEPARTAMENTO)"];

        $this->exec_sql($queries);

        $allQueries = array_merge($allQueries, $queries);

        $this->connection = "oracle";
        $queries = [];
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.M_TMP_USER_DEP_PRO_DIS_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.M_TMP_USER_DEP_PRO_DIS_{$this->userIdentifier}(
            TICKET VARCHAR2(20),
            MSISDN VARCHAR2(25),
            FECHA_CARGA DATE,
            CELDA VARCHAR2(40),
            FECHA_CORTE DATE,
            DEPARTAMENTO VARCHAR2(100),
            PROVINCIA VARCHAR2(100),
            DISTRITO VARCHAR2(100)
        )"];
        $this->exec_sql($queries);

        $data = DB::connection("oracle_reptdm")->table("USRAES.M_TMP_USER_DEP_PRO_DIS_{$this->userIdentifier}")->get();
        foreach($data as $row){
            DB::table("USRAES.M_TMP_USER_DEP_PRO_DIS_{$this->userIdentifier}")->insert([
                "ticket" => $row->ticket,
                "msisdn" => $row->msisdn,
                "fecha_carga" => $row->fecha_carga,
                "celda" => $row->celda,
                "fecha_corte" => $row->fecha_corte,
                "departamento" => $row->departamento,
                "provincia" => $row->provincia,
                "distrito" => $row->distrito,
            ]);
        }

        $queries = [];

        $queries[] = ["sql" => "BEGIN
        EXECUTE IMMEDIATE 'DROP TABLE USRAES.M_TMP_DEV_USER_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
        IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.M_TMP_DEV_USER_{$this->userIdentifier} AS
        SELECT
        TICKET TICKET,
        MSISDN MSISDN,
        CELDA CELDA,
        CLIENTE_ID ID_CLIENTE,
        NOMBRES NOMBRES,
        APELLIDOS APELLIDOS,
        ID_CARD_TYPE_VALUE TIPO_DOCUMENTO,
        ID_CARD_VALUE NRO_DOCUMENTO,
        TIPO_CLIENTE TIPO_CLIENTE,
        AGREEMENT_SOURCE_SYSTEM_DESC SISTEMA_ORIGEN,
        AGREEMENT_STATUS ESTADO_ACTUAL,
        CO_ID CONTRATO,
        DEPARTAMENTO DEPARTAMENTO,
        PROVINCIA PROVINCIA,
        DISTRITO DISTRITO,
        FECHA_CORTE FECHA_CORTE,
        FECHA_CARGA FECHA_CARGA
        FROM (
        SELECT /*+ PARALLEL(20)*/
        TK.*,
        SBS.CUSTOMER_ACCOUNT_SC CLIENTE_ID,
        CASE WHEN ID_CARD_TYPE_VALUE='RUC' THEN SBS.CUSTOMER_FULL_NAME ELSE SBS.CUSTOMER_FIRST_NAME END NOMBRES,
        CASE WHEN ID_CARD_TYPE_VALUE='RUC' THEN SBS.CUSTOMER_FULL_NAME ELSE SBS.CUSTOMER_LAST_NAME END APELLIDOS,
        SBS.ID_CARD_VALUE,
        SBS.ID_CARD_TYPE_VALUE,
        UPPER(DECODE(SBS.AGREEMENT_MODE,'PREPAGO','Consumer',SBS.CUSTOMER_ACCOUNT_CATEGORY_DESC)) TIPO_CLIENTE,
        SBS.AGREEMENT_SOURCE_SYSTEM_DESC,
        SBS.AGREEMENT_STATUS,
        SBS.AGREEMENT_SOURCE_CODE CO_ID,
        ROW_NUMBER() OVER(PARTITION BY TK.MSISDN ORDER BY SBS.CUSTOMER_ACCOUNT_ID DESC) R
        FROM USRAES.M_TMP_USER_DEP_PRO_DIS_{$this->userIdentifier} TK
        LEFT JOIN DWA.DW_T_SUBSCRIPTION_NUMBER SN ON SN.ACCESS_NUMBER=TK.msisdn  AND TK.FECHA_CORTE BETWEEN SN.START_DATE AND SN.END_DATE
        LEFT JOIN DWA.DW_M_AGREEMENT_HIST PARTITION(P_{$strPeriodo}) SBS ON SN.AGREEMENT_ID=SBS.AGREEMENT_ID
        WHERE SBS.ID_CARD_VALUE IS NOT NULL)
        WHERE R=1"];
        $this->exec_sql($queries);

        $this->connection = "oracle_reptdm";
        $queries = [];
        $queries[] = ["sql" => "BEGIN
        EXECUTE IMMEDIATE 'DROP TABLE USRAES.M_TMP_DEV_USER_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
        IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.M_TMP_DEV_USER_{$this->userIdentifier}(
            TICKET                                             VARCHAR2(20),
            MSISDN                                             VARCHAR2(25),
            CELDA                                              VARCHAR2(40),
            ID_CLIENTE                                         VARCHAR2(50 CHAR),
            NOMBRES                                            VARCHAR2(255 CHAR),
            APELLIDOS                                          VARCHAR2(255 CHAR),
            TIPO_DOCUMENTO                                     VARCHAR2(500 CHAR),
            NRO_DOCUMENTO                                      VARCHAR2(50 CHAR),
            TIPO_CLIENTE                                       VARCHAR2(500),
            SISTEMA_ORIGEN                                     VARCHAR2(50),
            ESTADO_ACTUAL                                      VARCHAR2(20),
            CONTRATO                                           VARCHAR2(50),
            DEPARTAMENTO                                       VARCHAR2(100),
            PROVINCIA                                          VARCHAR2(100),
            DISTRITO                                           VARCHAR2(100),
            FECHA_CORTE                                        DATE,
            FECHA_CARGA                                        DATE
        ) tablespace WORKAREA"];
        $this->exec_sql($queries);

        $data = DB::table("USRAES.M_TMP_DEV_USER_{$this->userIdentifier}")->get();
        foreach($data as $row){
            DB::connection($this->connection)->table("USRAES.M_TMP_DEV_USER_{$this->userIdentifier}")->insert([
                "ticket" => $row->ticket,
                "msisdn" => $row->msisdn,
                "celda" => $row->celda,
                "id_cliente" => $row->id_cliente,
                "nombres" => $row->nombres,
                "apellidos" => $row->apellidos,
                "tipo_documento" => $row->tipo_documento,
                "nro_documento" => $row->nro_documento,
                "tipo_cliente" => $row->tipo_cliente,
                "sistema_origen" => $row->sistema_origen,
                "estado_actual" => $row->estado_actual,
                "contrato" => $row->contrato,
                "departamento" => $row->departamento,
                "provincia" => $row->provincia,
                "distrito" => $row->distrito,
                "fecha_corte" => $row->fecha_corte,
                "fecha_carga" => $row->fecha_carga,
            ]);
        }

        // oracle=dwo
        $this->connection = "oracle";
        $queries = [];

        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.M_TMP_DEV_USER_BASE_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.M_TMP_DEV_USER_BASE_{$this->userIdentifier} AS 
        SELECT TICKET,
            MSISDN,
            CELDA,
            DEPARTAMENTO,
            PROVINCIA,
            DISTRITO,
            TO_DATE('{$strFechaFinF3}', 'YYYYMMDDHH24MISS') FECHA_CORTE -- <--- FECHA DE CORTE LA Q SE INGRESA EN EL PORTAL.
        FROM USRAES.M_TMP_DEV_USER_{$this->userIdentifier}"];

        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.M_USER_BASE_PREV_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.M_USER_BASE_PREV_{$this->userIdentifier} AS
        SELECT 
        TICKET                       TICKET, 
        MSISDN                       MSISDN,
        LINEA_ACTUAL                 MSISDN_ACTUAL,
        CELDA                        CELDA, 
        CLIENTE_ID                   ID_CLIENTE, 
        UPPER(NOMBRES)               NOMBRES, 
        UPPER(APELLIDOS)             APELLIDOS, 
        ID_CARD_TYPE_VALUE           TIPO_DOCUMENTO, 
        ID_CARD_VALUE                NRO_DOCUMENTO,
        CUSTOMER_FULL_NAME,
        TIPO_CLIENTE                 TIPO_CLIENTE, 
        SUBSCRIPTION_SOURCE_SYSTEM_DESC SISTEMA_ORIGEN,
        AGREEMENT_PRODUCT_OFFERING_SC TMCODE,
        AGREEMENT_PRODUCT_OFFERING_DESC PLAN_TARIFARIO,
        AGREEMENT_STATUS             ESTADO_ACTUAL,
        CUSTOMER_ID                  CUSTOMER_ID,
        CUSTCODE                     CUSTCODE,
        CO_ID                        CONTRATO,
        AGREEMENT_MODE               MODO_CONTRATACION,
        AGREEMENT_START_DATE         FECHA_ACTIVACION,
        AGREEMENT_END_DATE           FECHA_BAJA,
        CICLO                        CICLOFACTURACION,
        DEPARTAMENTO                 DEPARTAMENTO, 
        PROVINCIA                    PROVINCIA, 
        DISTRITO                     DISTRITO,
        FECHA_CORTE                  FECHA_CORTE,
        SYSDATE                      FECHA_CARGA
        FROM (
        SELECT /*+ PARALLEL(20)*/
               TK.*,
               SBS.CUSTOMER_ACCOUNT_SC CLIENTE_ID,
               CASE WHEN ID_CARD_TYPE_VALUE='RUC' THEN SBS.CUSTOMER_FULL_NAME ELSE SBS.CUSTOMER_FIRST_NAME END NOMBRES,
               CASE WHEN ID_CARD_TYPE_VALUE='RUC' THEN SBS.CUSTOMER_FULL_NAME ELSE SBS.CUSTOMER_LAST_NAME END APELLIDOS,
               SBS.CUSTOMER_FULL_NAME,
               SBS.ID_CARD_VALUE,
               SBS.ID_CARD_TYPE_VALUE,
               UPPER(DECODE(SBS.AGREEMENT_MODE,'PREPAGO','CONSUMER',SBS.CUSTOMER_ACCOUNT_CATEGORY_DESC)) TIPO_CLIENTE,  
               SBS.SUBSCRIPTION_SOURCE_SYSTEM_DESC,
               SBS.AGREEMENT_PRODUCT_OFFERING_SC,
               SBS.AGREEMENT_PRODUCT_OFFERING_DESC,
               SBS.AGREEMENT_STATUS,
               DECODE(AGREEMENT_MODE,'POSTPAGO',SBS.CUSTOMER_ACCOUNT_SC)   CUSTOMER_ID,
               DECODE(AGREEMENT_MODE,'POSTPAGO',SBS.CUSTOMER_ACCOUNT_DESC) CUSTCODE,
               DECODE(AGREEMENT_MODE,'POSTPAGO',SBS.AGREEMENT_SOURCE_CODE) CO_ID,
               SBS.AGREEMENT_MODE,
               SBS.AGREEMENT_START_DATE,
               SBS.AGREEMENT_END_DATE,
               SBS.CUSTOMER_ACCOUNT_BILLING_CYCLE_SC CICLO,
               SBS.SUBSCRIPTION_ACCESS_NUMBER LINEA_ACTUAL,
               ROW_NUMBER() OVER(PARTITION BY TK.MSISDN ORDER BY SBS.CUSTOMER_ACCOUNT_ID DESC) R
        FROM USRAES.M_TMP_DEV_USER_BASE_{$this->userIdentifier} TK
        LEFT JOIN DWA.DW_T_SUBSCRIPTION_NUMBER SN ON SN.ACCESS_NUMBER=TK.MSISDN  AND TK.FECHA_CORTE BETWEEN SN.START_DATE AND SN.END_DATE
        LEFT JOIN DWA.DW_M_SUBSCRIPTION_HIST PARTITION(P_{$strPeriodo}) SBS -- INGRESAR EL AÑO Y MES ACTUAL
        ON SN.AGREEMENT_ID=SBS.AGREEMENT_ID AND SBS.AGREEMENT_SERVICE_GROUP='MOVIL'
        WHERE SBS.ID_CARD_VALUE IS NOT NULL
        )
        WHERE R=1"];

        $queries[] = ["sql" => "DECLARE
            V_TICKET VARCHAR2(100) := :p_ticket;
            V_DEPARTAMENTO VARCHAR2(100) := :p_departamento;
        BEGIN
            DELETE FROM USRAES.M_REPORTES_ALL WHERE TICKET = V_TICKET and DEPARTAMENTO = V_DEPARTAMENTO;
            COMMIT;

            INSERT INTO USRAES.M_REPORTES_ALL(
                TICKET, MSISDN, MSISDN_ACTUAL, CELDA, ID_CLIENTE, NOMBRES, APELLIDOS,
                TIPO_DOCUMENTO, NRO_DOCUMENTO, CUSTOMER_FULL_NAME, TIPO_CLIENTE,
                SISTEMA_ORIGEN, TMCODE, PLAN_TARIFARIO, ESTADO_ACTUAL, CUSTOMER_ID,
                CUSTCODE, CONTRATO, MODO_CONTRATACION, FECHA_ACTIVACION, FECHA_BAJA,
                CICLOFACTURACION, DEPARTAMENTO, PROVINCIA, DISTRITO, FECHA_CORTE, FECHA_CARGA
            )
            SELECT
            TICKET, MSISDN, MSISDN_ACTUAL, CELDA, ID_CLIENTE, NOMBRES, APELLIDOS,
            TIPO_DOCUMENTO, NRO_DOCUMENTO, CUSTOMER_FULL_NAME, TIPO_CLIENTE,
            SISTEMA_ORIGEN, TMCODE, PLAN_TARIFARIO, ESTADO_ACTUAL, CUSTOMER_ID,
            CUSTCODE, CONTRATO, MODO_CONTRATACION, FECHA_ACTIVACION, FECHA_BAJA,
            CICLOFACTURACION, DEPARTAMENTO, PROVINCIA, DISTRITO, FECHA_CORTE, FECHA_CARGA
            FROM USRAES.M_USER_BASE_PREV_{$this->userIdentifier}
            WHERE ESTADO_ACTUAL <>'D';
            COMMIT;


            DELETE FROM USRAES.M_REPORTES_HIST WHERE TICKET = V_TICKET and DEPARTAMENTO = V_DEPARTAMENTO;
            COMMIT;

            INSERT INTO USRAES.M_REPORTES_HIST(TICKET, DEPARTAMENTO, FECHA_CARGA, NUMERO_AFECTADOS)
            SELECT
            TICKET,DEPARTAMENTO,FECHA_CARGA,COUNT(MSISDN_ACTUAL) NUMERO_AFECTADOS 
            FROM USRAES.M_USER_BASE_PREV_{$this->userIdentifier}
            WHERE ESTADO_ACTUAL <>'D'
            GROUP BY TICKET,DEPARTAMENTO,FECHA_CARGA;
            COMMIT;
        END;", "params" => ["p_ticket" => $ticketOsiptel, "p_departamento" => $provincias[0][0]]];
        $this->exec_sql($queries);
    }

    public function getReporte($ticket, $departamento)
    {
        $query = "SELECT
        TICKET,ID_CLIENTE,TIPO_DOCUMENTO TIPO_DOC,NRO_DOCUMENTO,
        CUSTOMER_FULL_NAME NOMBRES_APELLIDOS,MSISDN_ACTUAL MSISDN,DEPARTAMENTO
        FROM USRAES.M_REPORTES_ALL
        where TICKET = :ticket and DEPARTAMENTO = :departamento";

        return DB::select($query, ["ticket" => $ticket, "departamento" => $departamento]);
    }

    public function delete($ticket, $departamento)
    {
        DB::table("USRAES.M_REPORTES_ALL")
        ->where("TICKET", $ticket)
        ->where("DEPARTAMENTO", $departamento)
        ->delete();
    }

    public function getInputs($ticket, $departamento)
    {
        $input = DB::connection("oracle_reptdm")
        ->table("USRAES.M_BASE_PREV_BASEDEV_INPUT")
        ->where("ticket", $ticket)        
        ->where("departamento", $departamento)
        ->first();

        if($input !== null){
            // M_BASE_PREV_BASEDEV_INPUT_CELDA
            $input->celdas =  DB::connection("oracle_reptdm")
            ->table("USRAES.M_BASE_PREV_BASEDEV_INPUT_CELDA")
            ->select("celda")
            ->where("ticket", $ticket)        
            ->where("departamento", $departamento)
            ->get();
        }
        return $input;
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
