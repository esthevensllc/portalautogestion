<?php

namespace AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\ExtraccionDevolucion\CargaInformeFalla\Domain\ExtraccionRepository;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;

class EloquentExtraccionRepository implements ExtraccionRepository
{
    private $connection = "oracle_reptdm";
    private $userIdentifier;
    private $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function getReporte(array $celdas, array $provincias, DateTime $fechaIni, DateTime $fechaFin, $ticketOsiptel, DateTime $fechaInteres, DateTime $corteFechaIni, Datetime $corteFechaFin)
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
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.TABLE_CELDAS_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.DEP_PRO_DIS_TMP_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.TABLE_CELDAS_{$this->userIdentifier} (CELDA VARCHAR2(25)) tablespace WORKAREA"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.DEP_PRO_DIS_TMP_{$this->userIdentifier} (
            DEPARTAMENTO VARCHAR2(25),
            PROVINCIA VARCHAR2(25),
            DISTRITO VARCHAR2(25)
        ) tablespace WORKAREA"];

        $this->exec_sql($queries);

        foreach ($celdas as $value) {
            DB::connection($this->connection)->table("USRAES.TABLE_CELDAS_{$this->userIdentifier}")->insert(["celda" => $value]);
        }
        foreach ($provincias as $row) {
            DB::connection($this->connection)->table("USRAES.DEP_PRO_DIS_TMP_{$this->userIdentifier}")->insert([
                "departamento" => $row[0],
                "provincia" => $row[1],
                "distrito" => $row[2],
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
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.T_USER_VOZ_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                    IF SQLCODE != -942 THEN
                        RAISE;
                    END IF;
            END;"];
            $queries[] = ["sql" => "BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.T_U_VOZ_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                    IF SQLCODE != -942 THEN
                        RAISE;
                    END IF;
            END;"];
            $queries[] = ["sql" => "CREATE TABLE USRAES.T_U_VOZ_{$this->userIdentifier} tablespace WORKAREA AS
            SELECT /*+ PARALLEL(16)*/
            'VOZ' SERVICIO,
            cdr.tim_number MSISDN
            ,cdr.tim_number_subs_firts_ci CELDA
            ,cdr.charging_start_time FECHA
            ,cdr.record_type TIPO
            ,cdr.number_b NUMBER_B
            FROM dm.cdr_dwh PARTITION(CDR_DWH_{$strFechaIniF1}) cdr -- <--FECHA_INI O FECHA_FIN
            WHERE
            cdr.tim_number_subs_firts_ci in (SELECT CELDA FROM USRAES.TABLE_CELDAS_{$this->userIdentifier} GROUP BY CELDA)
            AND cdr.charging_start_time BETWEEN '{$strFechaIniF3}' -- < COLOCAR CONCAT FECHA_INI + (HORA_INI - 00:10:00) 
            AND '{$strFechaFinF3}' -- < COLOCAR CONCAT FECHA_FIN + (HORA_FIN + 00:03:00)
            AND cdr.record_type IN ('01','03','08')"];
            $queries[] = ["sql" => "CREATE TABLE USRAES.T_USER_VOZ_{$this->userIdentifier} tablespace WORKAREA AS
            SELECT SERVICIO,MSISDN,CELDA,FECHA FROM USRAES.T_U_VOZ_{$this->userIdentifier}
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
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_USER_DATOS_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                    IF SQLCODE != -942 THEN
                        RAISE;
                    END IF;
            END;"];
            $queries[] = ["sql" => "CREATE TABLE USRAES.TMP_USER_DATOS_{$this->userIdentifier} tablespace WORKAREA AS
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
            gp.cell_identity in (SELECT CELDA FROM USRAES.TABLE_CELDAS_{$this->userIdentifier} GROUP BY CELDA)
            AND TO_CHAR(gp.s_rec_opening_time,'YYYYMMDDHH24MISS') BETWEEN '{$strFechaIniF3}' -- < COLOCAR CONCAT FECHA_INI + (HORA_INI - 00:10:00) 
            AND '{$strFechaFinF3}' -- < COLOCAR CONCAT FECHA_FIN + (HORA_FIN + 00:03:00)
            and (gp.s_uplink + gp.s_downlink)>0)"];
        }else{
            throw new Exception("No existe o no hay datos para la partición 'P_{$strFechaIniF1}' de la tabla dm.cdr_gprs en REPTDM");
        }

        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_USER_BASE_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.TMP_USER_BASE_{$this->userIdentifier} tablespace WORKAREA AS
        SELECT SERVICIO,MSISDN,TO_CHAR(CELDA) CELDA,FECHA FROM USRAES.TMP_USER_DATOS_{$this->userIdentifier}
        UNION ALL
        SELECT * FROM USRAES.T_USER_VOZ_{$this->userIdentifier}"];

        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_USER_UNICOS_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.TMP_USER_UNICOS_{$this->userIdentifier} tablespace WORKAREA AS
        SELECT '{$ticketOsiptel}' TICKET, -- <---- Ticket_osiptel
        MSISDN,SYSDATE FECHA_CARGA,MAX(CELDA) CELDA 
        FROM USRAES.TMP_USER_BASE_{$this->userIdentifier} group BY MSISDN"];

        $this->exec_sql($queries);

        $result = DB::connection($this->connection)
        ->select(DB::raw("select count(distinct MSISDN) counter from USRAES.TMP_USER_UNICOS_{$this->userIdentifier}"));
        return $result[0]->counter;
    }

    public function getReporte2(array $celdas, array $provincias, DateTime $fechaIni, DateTime $fechaFin, $ticketOsiptel, DateTime $fechaInteres, DateTime $corteFechaIni, Datetime $corteFechaFin)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();

        $strProvincias = [];
        foreach($provincias as $row){
            $strProvincias[] = implode(",", $row);
        }
        $strProvincias = implode("\n", $strProvincias);

        DB::connection($this->connection)
        ->table("USRAES.BASE_PREV_BASEDEV_INPUT")
        ->where("ticket", $ticketOsiptel)
        ->where("departamento", $provincias[0][0])
        ->delete();

        DB::connection($this->connection)
        ->table("USRAES.BASE_PREV_BASEDEV_INPUT")
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
        ->statement("BEGIN
            INSERT INTO USRAES.BASE_PREV_BASEDEV_INPUT_CELDA(TICKET, DEPARTAMENTO, CELDA)
            SELECT :p_ticket AS TICKET, :p_departamento AS DEPARTAMENTO, CELDA FROM USRAES.TABLE_CELDAS_{$this->userIdentifier};
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
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_USER_DEP_PRO_DIS_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.TMP_USER_DEP_PRO_DIS_{$this->userIdentifier} tablespace WORKAREA AS
        SELECT DISTINCT 
               TK.*,
               TO_DATE('{$strFechaFinF3}','YYYYMMDDHH24MISS') FECHA_CORTE, -- (FECHA_FIN + HORA_FIN)
               cr.departamento,
               cr.provincia,
               cr.distrito
        FROM USRAES.TMP_USER_UNICOS_{$this->userIdentifier} TK
        ,dm.cdr_celdas_red cr
        WHERE TK.celda=cr.cell_id
        AND translate(UPPER(cr.departamento), 'áéíóúÁÉÍÓÚ', 'aeiouAEIOU') IN 
        (SELECT DEPARTAMENTO FROM USRAES.DEP_PRO_DIS_TMP_{$this->userIdentifier} GROUP BY DEPARTAMENTO)"];

        $this->exec_sql($queries);

        $allQueries = array_merge($allQueries, $queries);

        $this->connection = "oracle";
        $queries = [];
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_USER_DEP_PRO_DIS_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.TMP_USER_DEP_PRO_DIS_{$this->userIdentifier}(
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

        $data = DB::connection("oracle_reptdm")->table("USRAES.TMP_USER_DEP_PRO_DIS_{$this->userIdentifier}")->get();
        foreach($data as $row){
            DB::table("USRAES.TMP_USER_DEP_PRO_DIS_{$this->userIdentifier}")->insert([
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
        EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_DEV_USER_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
        IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.TMP_DEV_USER_{$this->userIdentifier} AS
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
        FROM USRAES.TMP_USER_DEP_PRO_DIS_{$this->userIdentifier} TK
        LEFT JOIN DWA.DW_T_SUBSCRIPTION_NUMBER SN ON SN.ACCESS_NUMBER=TK.msisdn  AND TK.FECHA_CORTE BETWEEN SN.START_DATE AND SN.END_DATE
        LEFT JOIN DWA.DW_M_AGREEMENT_HIST PARTITION(P_{$strPeriodo}) SBS ON SN.AGREEMENT_ID=SBS.AGREEMENT_ID
        WHERE SBS.ID_CARD_VALUE IS NOT NULL)
        WHERE R=1"];
        $this->exec_sql($queries);

        $this->connection = "oracle_reptdm";
        $queries = [];
        $queries[] = ["sql" => "BEGIN
        EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_DEV_USER_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
        IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.TMP_DEV_USER_{$this->userIdentifier}(
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

        $data = DB::table("USRAES.TMP_DEV_USER_{$this->userIdentifier}")->get();
        foreach($data as $row){
            DB::connection($this->connection)->table("USRAES.TMP_DEV_USER_{$this->userIdentifier}")->insert([
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
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_DEV_USER_BASE_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.TMP_DEV_USER_BASE_{$this->userIdentifier} AS 
        SELECT TICKET,
            MSISDN,
            CELDA,
            DEPARTAMENTO,
            PROVINCIA,
            DISTRITO,
            TO_DATE('{$strFechaFinF3}', 'YYYYMMDDHH24MISS') FECHA_CORTE -- <--- FECHA DE CORTE LA Q SE INGRESA EN EL PORTAL.
        FROM USRAES.TMP_DEV_USER_{$this->userIdentifier}"];

        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.USER_BASE_PREV_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.USER_BASE_PREV_{$this->userIdentifier} AS
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
        FROM USRAES.TMP_DEV_USER_BASE_{$this->userIdentifier} TK
        LEFT JOIN DWA.DW_T_SUBSCRIPTION_NUMBER SN ON SN.ACCESS_NUMBER=TK.MSISDN  AND TK.FECHA_CORTE BETWEEN SN.START_DATE AND SN.END_DATE
        LEFT JOIN DWA.DW_M_SUBSCRIPTION_HIST PARTITION(P_{$strPeriodo}) SBS -- INGRESAR EL AÑO Y MES ACTUAL
        ON SN.AGREEMENT_ID=SBS.AGREEMENT_ID AND SBS.AGREEMENT_SERVICE_GROUP='MOVIL'
        WHERE SBS.ID_CARD_VALUE IS NOT NULL
        )
        WHERE R=1"];

        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.CARGOS_FIJOS_BSCS_{$this->userIdentifier} PURGE';
        EXCEPTION
            WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.CARGOS_FIJOS_BSCS_{$this->userIdentifier} AS
        SELECT /*+ PARALLEL(20)*/
               AGREEMENT_CONTRACT_NUMBER  CONTRATO,
               AGREEMENT_START_DATE       FECHA_ACTIVACION, 
               AGREEMENT_END_DATE         FECHA_BAJA,      
               AGREEMENT_STATUS           ESTADO_CONTRATO,
               AGREEMENT_SERVICE_PRODUCT_OFFERING_SC      SNCODE,
               AGREEMENT_SERVICE_PRODUCT_OFFERING_DESC   SNCODE_DESC,
               AGREEMENT_SERVICE_START_DATE               SNCODE_FECHA_ACT,
               AGREEMENT_SERVICE_STATUS_SC                SNCODE_ESTADO,
               AGREEMENT_SERVICE_STATUS_DATE             SNCODE_FECHA_ESTADO,
               AGREEMENT_SERVICE_FLAT_CHARGE             CF
        FROM DWA.DW_M_AGREEMENT_SERVICE_HIST SUBPARTITION(P_{$strPeriodo}_BSCS) S1 -- INGRESAR EL AÑO Y MES ACTUAL
        WHERE EXISTS (SELECT 1 FROM USRAES.USER_BASE_PREV_{$this->userIdentifier} S2 WHERE S1.AGREEMENT_CONTRACT_NUMBER=S2.CONTRATO AND S2.SISTEMA_ORIGEN='BSCS')
        AND AGREEMENT_SERVICE_START_DATE<TO_DATE('{$strCorteFechaFinF2}','YYYYMMDDHH24MISS')"];

        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.CARGOS_FIJOS_BSCSIX_{$this->userIdentifier} PURGE';
        EXCEPTION
            WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.CARGOS_FIJOS_BSCSIX_{$this->userIdentifier} AS
        SELECT /*+ PARALLEL(20)*/
               AGREEMENT_CONTRACT_NUMBER  CONTRATO,
               AGREEMENT_START_DATE       FECHA_ACTIVACION, 
               AGREEMENT_END_DATE         FECHA_BAJA,      
               AGREEMENT_STATUS           ESTADO_CONTRATO,
               agreement_service_group_sc                 spcode,
               SP.DES                                     PAQUETE,
               SP.PRODUCTOFFERING_TYPE                    TIPO_PAQUETE,
               AGREEMENT_SERVICE_PRODUCT_OFFERING_SC      SNCODE,
               AGREEMENT_SERVICE_PRODUCT_OFFERING_DESC   SNCODE_DESC,
               AGREEMENT_SERVICE_START_DATE               SNCODE_FECHA_ACT,
               AGREEMENT_SERVICE_STATUS_SC                SNCODE_ESTADO,
               AGREEMENT_SERVICE_STATUS_DATE             SNCODE_FECHA_ESTADO,
               AGREEMENT_SERVICE_FLAT_CHARGE             CF       
        FROM DWA.DW_M_AGREEMENT_SERVICE_HIST SUBPARTITION(P_{$strPeriodo}_BSCSIX) S1 -- INGRESAR EL AÑO Y MES ACTUAL
        JOIN DWS.SA_BSCSIX_MPUSPTAB SP ON S1.AGREEMENT_SERVICE_GROUP_SC=SP.SPCODE
        WHERE EXISTS (SELECT 1 FROM USRAES.USER_BASE_PREV_{$this->userIdentifier} S2 WHERE S1.AGREEMENT_CONTRACT_NUMBER=S2.CONTRATO AND S2.SISTEMA_ORIGEN='BSCSIX')"];

        $this->exec_sql($queries);

        // PASO 3

        $queries = [];
        $queries[] = ["sql" => "ALTER TABLE USRAES.USER_BASE_PREV_{$this->userIdentifier} ADD CARGO_ACCESO_NORMAL NUMBER"];
        $queries[] = ["sql" => "BEGIN
            MERGE INTO USRAES.USER_BASE_PREV_{$this->userIdentifier} MP
            USING (
            SELECT CONTRATO,
                CF
            FROM USRAES.CARGOS_FIJOS_BSCS_{$this->userIdentifier} A
            WHERE EXISTS (SELECT 1
                FROM USRAES.USER_BASE_PREV_{$this->userIdentifier} B
            WHERE B.SISTEMA_ORIGEN = 'BSCS'
                --AND B.ESTADO_ACTUAL IN ('A', 'S')
                AND B.MODO_CONTRATACION = 'POSTPAGO'
                AND A.CONTRATO = B.CONTRATO)
            AND SNCODE=27
            AND CF<>0) TMP
            ON (MP.CONTRATO=TMP.CONTRATO)
            WHEN MATCHED THEN
            UPDATE SET MP.CARGO_ACCESO_NORMAL=TMP.CF
            WHERE MP.SISTEMA_ORIGEN='BSCS';
            COMMIT;
        END;"];
        $queries[] = ["sql" => "BEGIN
            MERGE INTO USRAES.USER_BASE_PREV_{$this->userIdentifier} MP
            USING (
            SELECT CONTRATO,
                PAQUETE,
                CF
            FROM (
            SELECT CONTRATO,
                PAQUETE,
                ROUND(CF/1.18,2) CF,
                ROW_NUMBER() OVER(PARTITION BY CONTRATO ORDER BY DECODE(SNCODE_ESTADO,'A',3,'S',2,'D',1) DESC,SNCODE_FECHA_ACT DESC) R
            FROM USRAES.CARGOS_FIJOS_BSCSIX_{$this->userIdentifier}
            WHERE TIPO_PAQUETE = 'B'
            AND UPPER(SNCODE_DESC) LIKE '%REC%CHARGE%'
            ) WHERE R=1
            AND CF<>0
            ) TMP
            ON (MP.CONTRATO=TMP.CONTRATO)
            WHEN MATCHED THEN
            UPDATE SET MP.CARGO_ACCESO_NORMAL=TMP.CF
            WHERE MP.SISTEMA_ORIGEN='BSCSIX';
            COMMIT;
        END;"];

        $queries[] = ["sql" => "ALTER TABLE USRAES.USER_BASE_PREV_{$this->userIdentifier} ADD CARGO_COMBO NUMBER"];
        $queries[] = ["sql" => "ALTER TABLE USRAES.USER_BASE_PREV_{$this->userIdentifier} ADD CARGO_BOLSA NUMBER"];
        
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.BASE_CF_BOLSA_{$this->userIdentifier} PURGE';
        EXCEPTION
            WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.BASE_CF_BOLSA_{$this->userIdentifier} NOLOGGING AS
        SELECT /* + PARALLEL(8) */
        DISTINCT BC.CUSTOMER_ID
        ,BC.SHBOLSA
        ,BC.FEC_ACTIV
        ,BC.FEC_DEACT
        ,TB.NOMBOLSA
        ,TBR.ESTADO
        ,TBR.MINUTOS
        ,TBR.COSTO_MIN
        ,TBR.COSTO_TOT
        ,SYSDATE FECHA_CARGA
        FROM DWS.SA_TIM_BOLSA_CLIENTES BC
             ,DWS.SA_TIM_BOLSAS TB
             ,DWS.SA_TIM_BOLSA_RANGOS TBR
             ,DWS.SA_PP_DATOS_CONTRATO TP
        WHERE 
        BC.SHBOLSA = TBR.SHBOLSA
        AND BC.RANGO_1 = TBR.RNG_ID
        AND BC.SHBOLSA = TB.SHBOLSA
        AND BC.CUSTOMER_ID = TP.CUSTOMER_ID
        AND EXISTS (SELECT 1 FROM USRAES.USER_BASE_PREV_{$this->userIdentifier} S2 WHERE TP.CUSTOMER_ID=S2.ID_CLIENTE AND S2.SISTEMA_ORIGEN='BSCSIX')
        AND BC.FEC_ACTIV < TO_DATE('{$strCorteFechaIniF1}','YYYYMMDDHH24MISS')--FECHA_CORTE_INICIO QUE SE INGRESA AL PORTAL
        AND (BC.FEC_DEACT IS NULL OR  BC.FEC_DEACT >=TO_DATE('{$strCorteFechaFinF2}','YYYYMMDDHH24MISS'))"];

        $queries[] = ["sql" => "BEGIN
            MERGE INTO USRAES.USER_BASE_PREV_{$this->userIdentifier} MP
            USING (
            SELECT C.CUSTOMER_ID, C.NOMBOLSA, C.COSTO_TOT AS COSTO_BOLSA 
                    FROM (SELECT B.*,DENSE_RANK() OVER (PARTITION BY CUSTOMER_ID ORDER BY FEC_ACTIV DESC, FEC_DEACT DESC, COSTO_TOT DESC) R
                        FROM USRAES.BASE_CF_BOLSA_{$this->userIdentifier} B 
                        WHERE 
                        SHBOLSA NOT IN ('BMMSC', 'BSMSC')) C
                    WHERE
                    C.R=1
            ) TMP
            ON (MP.ID_CLIENTE=TMP.CUSTOMER_ID)
            WHEN MATCHED THEN
            UPDATE SET MP.CARGO_BOLSA=TMP.COSTO_BOLSA
            WHERE MP.SISTEMA_ORIGEN='BSCSIX';
            COMMIT;
        END;"];
        $queries[] = ["sql" => "ALTER TABLE USRAES.USER_BASE_PREV_{$this->userIdentifier} ADD cargo_linea NUMBER"];

        $this->exec_sql($queries);

        // PASO 4
        $data = DB::connection($this->connection)->table("USRAES.USER_BASE_PREV_{$this->userIdentifier}")->get();
        $this->connection = "oracle_reptdm";
        DB::connection($this->connection)->statement("BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.USER_BASE_PREV_{$this->userIdentifier} PURGE';
        EXCEPTION
            WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;");
        DB::connection($this->connection)->statement("CREATE TABLE USRAES.USER_BASE_PREV_{$this->userIdentifier} (
            TICKET VARCHAR2(20), MSISDN VARCHAR2(25), MSISDN_ACTUAL VARCHAR2(20), CELDA VARCHAR2(40), ID_CLIENTE VARCHAR2(50 CHAR), NOMBRES VARCHAR2(255 CHAR), APELLIDOS VARCHAR2(255 CHAR), TIPO_DOCUMENTO VARCHAR2(500 CHAR), NRO_DOCUMENTO VARCHAR2(50 CHAR), CUSTOMER_FULL_NAME VARCHAR2(255 CHAR), TIPO_CLIENTE VARCHAR2(500), SISTEMA_ORIGEN VARCHAR2(50), TMCODE VARCHAR2(50), PLAN_TARIFARIO VARCHAR2(100), ESTADO_ACTUAL VARCHAR2(20), CUSTOMER_ID VARCHAR2(50 CHAR), CUSTCODE VARCHAR2(255 CHAR), CONTRATO VARCHAR2(50), MODO_CONTRATACION VARCHAR2(20), FECHA_ACTIVACION DATE, FECHA_BAJA DATE, CICLOFACTURACION VARCHAR2(10 CHAR), DEPARTAMENTO VARCHAR2(100), PROVINCIA VARCHAR2(100), DISTRITO VARCHAR2(100), FECHA_CORTE DATE, FECHA_CARGA DATE, CARGO_ACCESO_NORMAL NUMBER, CARGO_COMBO NUMBER, CARGO_BOLSA NUMBER, CARGO_LINEA NUMBER
        )");

        foreach ($data as $row) {
            DB::connection($this->connection)
            ->table("USRAES.USER_BASE_PREV_{$this->userIdentifier}")
            ->insert([
                "ticket" => $row->ticket,
                "msisdn" => $row->msisdn,
                "msisdn_actual" => $row->msisdn_actual,
                "celda" => $row->celda,
                "id_cliente" => $row->id_cliente,
                "nombres" => $row->nombres,
                "apellidos" => $row->apellidos,
                "tipo_documento" => $row->tipo_documento,
                "nro_documento" => $row->nro_documento,
                "customer_full_name" => $row->customer_full_name,
                "tipo_cliente" => $row->tipo_cliente,
                "sistema_origen" => $row->sistema_origen,
                "tmcode" => $row->tmcode,
                "plan_tarifario" => $row->plan_tarifario,
                "estado_actual" => $row->estado_actual,
                "customer_id" => $row->customer_id,
                "custcode" => $row->custcode,
                "contrato" => $row->contrato,
                "modo_contratacion" => $row->modo_contratacion,
                "fecha_activacion" => $row->fecha_activacion,
                "fecha_baja" => $row->fecha_baja,
                "ciclofacturacion" => $row->ciclofacturacion,
                "departamento" => $row->departamento,
                "provincia" => $row->provincia,
                "distrito" => $row->distrito,
                "fecha_corte" => $row->fecha_corte,
                "fecha_carga" => $row->fecha_carga,
                "cargo_acceso_normal" => $row->cargo_acceso_normal,
                "cargo_combo" => $row->cargo_combo,
                "cargo_bolsa" => $row->cargo_bolsa,
                "cargo_linea" => $row->cargo_linea,
            ]);
        }
        
        $queries = [];
        $queries[] = ["sql" => "BEGIN
            UPDATE USRAES.USER_BASE_PREV_{$this->userIdentifier} A
            SET CARGO_LINEA=(SELECT arpu 
            FROM 
            (
            SELECT 
            ar.ARPU,
            ar.MES,
            ROW_NUMBER()OVER(PARTITION BY idtipoarpu ORDER BY MES DESC)R
            FROM dm.dwh_devolucion_arpu ar
            WHERE ar.MES<= SUBSTR('{$strCorteFechaFinF2}',1,6)-----FECHA_FIN CON LOS 3 MIN AGREGADOS
            AND ar.idtipoarpu=5--ARPU POSTPAGO
            )
            Where r=1) WHERE UPPER(A.PLAN_TARIFARIO) LIKE '%EXACTO%';
            COMMIT;

            ----LINEAS PREPAGO -- ARPU PREPAGO
            UPDATE USRAES.USER_BASE_PREV_{$this->userIdentifier} A
            SET CARGO_LINEA=(SELECT arpu 
            FROM 
            (
            SELECT 
            ar.ARPU,
            ar.MES,
            ROW_NUMBER()OVER(PARTITION BY idtipoarpu ORDER BY MES DESC)R
            FROM dm.dwh_devolucion_arpu ar
            WHERE ar.MES<= SUBSTR('{$strCorteFechaFinF2}',1,6)-----FECHA_FIN CON LOS 3 MIN AGREGADOS
            AND ar.idtipoarpu=1--ARPU PREPAGO
            )
            Where r=1) WHERE A.MODO_CONTRATACION='PREPAGO';
            COMMIT;

            --------------------------------------CALCULO DEL CARGO LINEA------------------------
            ----PLANES BOLSA----------
            UPDATE USRAES.USER_BASE_PREV_{$this->userIdentifier} A
            SET A.CARGO_LINEA=A.CARGO_BOLSA
            WHERE A.CARGO_BOLSA IS NOT NULL AND UPPER(A.PLAN_TARIFARIO) LIKE '%BOLSA%';
            COMMIT;
            ----CARGOS FIJOS----------
            UPDATE USRAES.USER_BASE_PREV_{$this->userIdentifier} A
            SET A.CARGO_LINEA=A.CARGO_ACCESO_NORMAL
            WHERE A.CARGO_ACCESO_NORMAL IS NOT NULL AND A.CARGO_LINEA IS NULL;
            COMMIT;

            ---OTROS-------
            UPDATE USRAES.USER_BASE_PREV_{$this->userIdentifier} A
            SET A.CARGO_LINEA=0
            WHERE A.CARGO_LINEA IS NULL;
            COMMIT;
        END;"];

        $queries[] = ["sql" => "ALTER TABLE USRAES.USER_BASE_PREV_{$this->userIdentifier} ADD FLG_GOB NUMBER DEFAULT 0"];
        $queries[] = ["sql" => "BEGIN
            UPDATE USRAES.USER_BASE_PREV_{$this->userIdentifier} A 
            SET A.FLG_GOB=1
            WHERE EXISTS (SELECT 1 FROM DWO.base_cartera_gobierno B WHERE A.NRO_DOCUMENTO=B.nro_doc);
            COMMIT;

            -- NO CONSIDERAR AL CLIENTE: GENERAL MOTORS CON RUC 20261126568

            delete from USRAES.USER_BASE_PREV_{$this->userIdentifier} where nro_documento = '20261126568';
            commit;
        END;"];
        $queries[] = ["sql" => "ALTER TABLE USRAES.USER_BASE_PREV_{$this->userIdentifier} ADD cargo_linea_igv NUMBER DEFAULT 0"];
        $queries[] = ["sql" => "ALTER TABLE USRAES.USER_BASE_PREV_{$this->userIdentifier} ADD monto_devolver_igv NUMBER DEFAULT 0"];

        $this->exec_sql($queries);

        // PASO 5

        $queries = [];
        $queries[] = ["sql" => "BEGIN
            UPDATE USRAES.USER_BASE_PREV_{$this->userIdentifier} A
            SET A.CARGO_LINEA_IGV=1.18*A.cargo_linea;
            COMMIT;

            UPDATE USRAES.USER_BASE_PREV_{$this->userIdentifier} A
            SET A.MONTO_DEVOLVER_IGV=(1.18*cargo_linea/(28*24*60))*{$corteDiffMinutos};
            COMMIT;
        END;"];

        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.USER_BASE_BFIN_{$this->userIdentifier} PURGE';
        EXCEPTION
            WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.USER_BASE_BFIN_{$this->userIdentifier} AS
        SELECT * FROM USRAES.USER_BASE_PREV_{$this->userIdentifier} A
        WHERE A.CARGO_LINEA_IGV<>0 -- <-- CAMBIAR POR EL CAMPO cargo_linea
        AND A.FLG_GOB=0
        AND SUBSTR(A.MSISDN,1,3)='519'
        AND A.TIPO_CLIENTE NOT IN ('DEMO','EMPLEADO CLARO')
        AND NOT EXISTS (SELECT 1 FROM dm.dwh_devolucion_carriers C WHERE A.NRO_DOCUMENTO=C.NRO_DOCUMENTO AND C.ESTADO='A')"];

        $this->exec_sql($queries);

        $response = DB::connection($this->connection)
        ->table("USRAES.USER_BASE_BFIN_{$this->userIdentifier}")
        ->selectRaw("rownum item,TICKET,ID_CLIENTE,NRO_DOCUMENTO,CUSTOMER_FULL_NAME NOMBRES_APELLIDOS,'Comunicaciones Personales(PCS)' SERVICIO_AFECTADO,MSISDN,DEPARTAMENTO")
        ->get();

        $dataToInsert = DB::connection($this->connection)->table("USRAES.USER_BASE_BFIN_{$this->userIdentifier}")->get();
        
        $this->connection = "oracle";

        DB::connection($this->connection)->statement("BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.USER_BASE_BFIN_{$this->userIdentifier} PURGE';
        EXCEPTION
            WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;");
        DB::connection($this->connection)->statement("CREATE TABLE USRAES.USER_BASE_BFIN_{$this->userIdentifier}(
            TICKET VARCHAR2(20),
            MSISDN VARCHAR2(25),
            MSISDN_ACTUAL VARCHAR2(20),
            CELDA VARCHAR2(40),
            ID_CLIENTE VARCHAR2(50 CHAR),
            NOMBRES VARCHAR2(255 CHAR),
            APELLIDOS VARCHAR2(255 CHAR),
            TIPO_DOCUMENTO VARCHAR2(500 CHAR),
            NRO_DOCUMENTO VARCHAR2(50 CHAR),
            CUSTOMER_FULL_NAME VARCHAR2(255 CHAR),
            TIPO_CLIENTE VARCHAR2(500),
            SISTEMA_ORIGEN VARCHAR2(50),
            TMCODE VARCHAR2(50),
            PLAN_TARIFARIO VARCHAR2(100),
            ESTADO_ACTUAL VARCHAR2(20),
            CUSTOMER_ID VARCHAR2(50 CHAR),
            CUSTCODE VARCHAR2(255 CHAR),
            CONTRATO VARCHAR2(50),
            MODO_CONTRATACION VARCHAR2(20),
            FECHA_ACTIVACION DATE,
            FECHA_BAJA DATE,
            CICLOFACTURACION VARCHAR2(10 CHAR),
            DEPARTAMENTO VARCHAR2(100),
            PROVINCIA VARCHAR2(100),
            DISTRITO VARCHAR2(100),
            FECHA_CORTE DATE,
            FECHA_CARGA DATE,
            CARGO_ACCESO_NORMAL NUMBER,
            CARGO_COMBO NUMBER,
            CARGO_BOLSA NUMBER,
            CARGO_LINEA NUMBER,
            FLG_GOB NUMBER,
            CARGO_LINEA_IGV NUMBER,
            MONTO_DEVOLVER_IGV NUMBER
        )");

        foreach($dataToInsert as $row){
            DB::connection($this->connection)
            ->table("USRAES.USER_BASE_BFIN_{$this->userIdentifier}")
            ->insert([
                "ticket" => $row->ticket,
                "msisdn" => $row->msisdn,
                "msisdn_actual" => $row->msisdn_actual,
                "celda" => $row->celda,
                "id_cliente" => $row->id_cliente,
                "nombres" => $row->nombres,
                "apellidos" => $row->apellidos,
                "tipo_documento" => $row->tipo_documento,
                "nro_documento" => $row->nro_documento,
                "customer_full_name" => $row->customer_full_name,
                "tipo_cliente" => $row->tipo_cliente,
                "sistema_origen" => $row->sistema_origen,
                "tmcode" => $row->tmcode,
                "plan_tarifario" => $row->plan_tarifario,
                "estado_actual" => $row->estado_actual,
                "customer_id" => $row->customer_id,
                "custcode" => $row->custcode,
                "contrato" => $row->contrato,
                "modo_contratacion" => $row->modo_contratacion,
                "fecha_activacion" => $row->fecha_activacion,
                "fecha_baja" => $row->fecha_baja,
                "ciclofacturacion" => $row->ciclofacturacion,
                "departamento" => $row->departamento,
                "provincia" => $row->provincia,
                "distrito" => $row->distrito,
                "fecha_corte" => $row->fecha_corte,
                "fecha_carga" => $row->fecha_carga,
                "cargo_acceso_normal" => $row->cargo_acceso_normal,
                "cargo_combo" => $row->cargo_combo,
                "cargo_bolsa" => $row->cargo_bolsa,
                "cargo_linea" => $row->cargo_linea,
                "flg_gob" => $row->flg_gob,
                "cargo_linea_igv" => $row->cargo_linea_igv,
                "monto_devolver_igv" => $row->monto_devolver_igv,
            ]);
        }

        return $response;
    }

    public function getReporteMontoDevolver(Datetime $fechaInteres, Datetime $corteFechaIni)
    {
        $strFechaInteres = $fechaInteres->format("Ymd");
        $strCorteFechaIniF1 = $corteFechaIni->format("Ymd");
        // $strCorteFechaFinF1 = $corteFechaFin->format("Ymd");
        $fechaActual = new DateTime();
        $fechaActual->modify("-1 day");
        $strPeriodo = $fechaActual->format("Ym");

        $this->userIdentifier = $this->authService->getUserIdentifier();

        $this->connection = "oracle";
        $queries = [];
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.BASE_PREV_DEVOLVER_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.BASE_PREV_DEVOLVER_{$this->userIdentifier} AS
        SELECT *
        FROM(
        SELECT /*+ PARALLEL(20)*/
               ID_CARD_VALUE                  NRO_DOCUMENTO,
               SUBSCRIPTION_ACCESS_NUMBER     MSISDN,
               CUSTOMER_ACCOUNT_SC            CUSTOMER_ID,
               CUSTOMER_ACCOUNT_DESC          CUSTCODE,
               AGREEMENT_CONTRACT_NUMBER      CO_ID,
               
               AGREEMENT_MODE                 MODO_CONTRATACION,
               UPPER(DECODE(SBS.AGREEMENT_MODE,'PREPAGO','CONSUMER',SBS.CUSTOMER_ACCOUNT_CATEGORY_DESC)) TIPO_CLIENTE,
               AGREEMENT_STATUS               ESTADO,
               AGREEMENT_START_DATE           FECHA_ACTIVACION,       
               CUSTOMER_ACCOUNT_BILLING_CYCLE_SC CICLO_FACTURACION,
               SBS.SUBSCRIPTION_SOURCE_SYSTEM_DESC  SISTEMA_ORIGEN,
               ROW_NUMBER() OVER(PARTITION BY SBS.ID_CARD_VALUE ORDER BY SBS.AGREEMENT_START_DATE DESC) RANK
        FROM DWA.DW_M_SUBSCRIPTION_HIST PARTITION(P_{$strPeriodo}) SBS --< INGRESAR EL PERIODO DE LA FECHA DE INICIO
        WHERE EXISTS (
        SELECT 1 FROM USRAES.USER_BASE_BFIN_{$this->userIdentifier} A WHERE A.ESTADO_ACTUAL in ('D','S') AND SBS.ID_CARD_VALUE=A.NRO_DOCUMENTO)
        AND SBS.AGREEMENT_STATUS IN ('A','G')
        AND SBS.AGREEMENT_SERVICE_GROUP='MOVIL'
        AND SBS.SUBSCRIPTION_ACCESS_NUMBER IS NOT NULL)
        WHERE RANK=1"];

        $queries[] = ["sql" => "BEGIN
        EXECUTE IMMEDIATE 'DROP TABLE USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier} AS
        SELECT S1.*,
               CASE WHEN S1.ESTADO_ACTUAL IN ('A','G') THEN S1.MSISDN_ACTUAL     END MSISDN_DEVOLVER,
               CASE WHEN S1.ESTADO_ACTUAL IN ('A','G') THEN S1.CUSTOMER_ID       END CUSTOMER_ID_DEVOLVER,
               CASE WHEN S1.ESTADO_ACTUAL IN ('A','G') THEN S1.CUSTCODE          END CUSTCODE_DEVOLVER,
               CASE WHEN S1.ESTADO_ACTUAL IN ('A','G') THEN S1.CONTRATO          END CO_ID_DEVOLVER,
               CASE WHEN S1.ESTADO_ACTUAL IN ('A','G') THEN S1.MODO_CONTRATACION END MODO_CONTRATACION_DEV,
               CASE WHEN S1.ESTADO_ACTUAL IN ('A','G') THEN S1.TIPO_CLIENTE      END TIPO_CLIENTE_DEV,
               CASE WHEN S1.ESTADO_ACTUAL IN ('A','G') THEN S1.FECHA_ACTIVACION  END FECHA_ACTIVACION_DEV,
               CASE WHEN S1.ESTADO_ACTUAL IN ('A','G') THEN S1.CICLOFACTURACION  END CICLOFACTURACION_DEV,
               CASE WHEN S1.ESTADO_ACTUAL IN ('A','G') THEN S1.SISTEMA_ORIGEN    END SISTEMA_ORIGEN_DEV,
               CASE WHEN S1.ESTADO_ACTUAL IN ('A','G') THEN 1 ELSE 0             END DEV,
               CAST(CASE WHEN S1.ESTADO_ACTUAL IN ('A','G') THEN DECODE(S1.MODO_CONTRATACION,'PREPAGO','PREPAGO',DECODE(S1.TIPO_CLIENTE,'CONSUMER','POSTPAGO_CONSUMER','POSTPAGO_CORPORATIVO')) END AS VARCHAR2(100))   MODALIDAD_DEV,
               S1.CARGO_LINEA_IGV                                                        CARGO_LINEA_IGV_DEV,
               S1.MONTO_DEVOLVER_IGV                                                     MONTO_DEVOLVER_IGV_DEV
        FROM USRAES.USER_BASE_BFIN_{$this->userIdentifier} S1"];

        $queries[] = ["sql" => "BEGIN
            MERGE INTO USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier} MP
            USING USRAES.BASE_PREV_DEVOLVER_{$this->userIdentifier} TMP
            ON(MP.NRO_DOCUMENTO=TMP.NRO_DOCUMENTO)
            WHEN MATCHED THEN
            UPDATE SET MP.MSISDN_DEVOLVER=TMP.MSISDN,
                        MP.MODO_CONTRATACION_DEV=TMP.MODO_CONTRATACION,
                        MP.TIPO_CLIENTE_DEV=TMP.TIPO_CLIENTE,
                        MP.CUSTOMER_ID_DEVOLVER=TMP.CUSTOMER_ID,
                        MP.CO_ID_DEVOLVER=TMP.CO_ID,
                        MP.CUSTCODE_DEVOLVER=TMP.CUSTCODE,
                        MP.FECHA_ACTIVACION_DEV=TMP.FECHA_ACTIVACION,
                        MP.CICLOFACTURACION_DEV=TMP.CICLO_FACTURACION,
                        MP.SISTEMA_ORIGEN_DEV=TMP.SISTEMA_ORIGEN,
                        MP.DEV=1,
                        MP.MODALIDAD_DEV=DECODE(TMP.MODO_CONTRATACION,'PREPAGO','PREPAGO',DECODE(TMP.TIPO_CLIENTE,'CONSUMER','POSTPAGO_CONSUMER','POSTPAGO_CORPORATIVO'))
            WHERE MP.DEV=0;
            COMMIT;

            --PASO 08.- IDENTIFICA LAS LINEAS NO APLICA - MODALIDAD_DEV
            -----------------------------------------------------------------------------------------
            UPDATE USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier}
            SET MODALIDAD_DEV = 'NO APLICA. SE DEVUELVE A UN NUMERO X PLAN BOLSA'
            WHERE UPPER(PLAN_TARIFARIO) LIKE '%BOLSA%'AND MSISDN_DEVOLVER IS NOT NULL AND DEV<>0;
            COMMIT;

            --PASO 09.- IDENTIFICA LAS LINEAS BOLSA - MODALIDAD_DEV (las lineas que son bolsa y que no se encuentra clientes.)
            -------------------------------------------------------------------------------------------
            DECLARE CURSOR X IS
            (SELECT DISTINCT * FROM (SELECT CUSTOMER_ID,FIRST_VALUE(MSISDN) OVER (PARTITION BY CUSTOMER_ID) NUMERO 
            FROM USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier}
            WHERE CUSTOMER_ID IS NOT NULL AND DEV=0 AND MSISDN_DEVOLVER IS NULL));
            BEGIN
            FOR X1 IN X 
            LOOP
            BEGIN
            UPDATE USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier} B
            SET DEV = 1,MODALIDAD_DEV='WEB BOLSA'
            WHERE B.CUSTOMER_ID= X1.CUSTOMER_ID AND B.MSISDN=X1.NUMERO AND MSISDN_DEVOLVER IS NULL;
            END;
            END LOOP;
            COMMIT;
            END;

            --PASO 10.- IDENTIFICA LAS LINEAS WEB - MODALIDAD_DEV (las lineas que son bolsa y que no se encuentra clientes.)
            -------------------------------------------------------------------------------------------
            UPDATE USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier}
            SET MODALIDAD_DEV = 'NO APLICA.SE DEVUELVE A UN NUMERO X PLAN WEB BOLSA'
            WHERE DEV IS NULL AND UPPER(PLAN_TARIFARIO) LIKE '%BOLSA%'AND MSISDN_DEVOLVER IS NULL;
            COMMIT;   

            UPDATE USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier} D
            SET D.DEV = 1,D.MODALIDAD_DEV='WEB'
            WHERE UPPER(D.PLAN_TARIFARIO) NOT LIKE '%BOLSA%'AND D.MSISDN_DEVOLVER IS NULL AND D.MODALIDAD_DEV IS NULL;
            COMMIT;
        END;"];
        $queries[] = ["sql" => "ALTER TABLE USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier} ADD
        (
        TASA NUMBER,INTERES NUMBER,MTO_TOTAL_DEV_IGV NUMBER,FCH_DEV DATE,INTERES_AL DATE,GLOSA VARCHAR2(250),FAC_ULTI_SBS NUMBER,FAC_NUEV_REFE NUMBER,
        FAC_ACUM_PROY NUMBER,FAC_ACUM_INI NUMBER)"];

        $queries[] = ["sql" => "BEGIN
            UPDATE USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier} S1
            SET S1.MONTO_DEVOLVER_IGV_DEV = 0.04 WHERE S1.MONTO_DEVOLVER_IGV_DEV < 0.04 
            AND MODALIDAD_DEV IN ('CONTROL','POSTPAGO_CONSUMER','POSTPAGO_CORPORATIVO');
            COMMIT;
        END;"];

        $this->exec_sql($queries);

        $dataToInsert = DB::connection($this->connection)
        ->table("USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier}")
        ->get();

        $this->connection = "oracle_reptdm";

        $queries = [];
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier}(
            TICKET VARCHAR2(20), MSISDN VARCHAR2(25), MSISDN_ACTUAL VARCHAR2(20), CELDA VARCHAR2(40), ID_CLIENTE VARCHAR2(50 CHAR), NOMBRES VARCHAR2(255 CHAR), APELLIDOS VARCHAR2(255 CHAR), TIPO_DOCUMENTO VARCHAR2(500 CHAR), NRO_DOCUMENTO VARCHAR2(50 CHAR), CUSTOMER_FULL_NAME VARCHAR2(255 CHAR), TIPO_CLIENTE VARCHAR2(500), SISTEMA_ORIGEN VARCHAR2(50), TMCODE VARCHAR2(50), PLAN_TARIFARIO VARCHAR2(100), ESTADO_ACTUAL VARCHAR2(20), CUSTOMER_ID VARCHAR2(50 CHAR), CUSTCODE VARCHAR2(255 CHAR), CONTRATO VARCHAR2(50), MODO_CONTRATACION VARCHAR2(20), FECHA_ACTIVACION DATE, FECHA_BAJA DATE, CICLOFACTURACION VARCHAR2(10 CHAR), DEPARTAMENTO VARCHAR2(100), PROVINCIA VARCHAR2(100), DISTRITO VARCHAR2(100), FECHA_CORTE DATE, FECHA_CARGA DATE, CARGO_ACCESO_NORMAL NUMBER, CARGO_COMBO NUMBER, CARGO_BOLSA NUMBER, CARGO_LINEA NUMBER, FLG_GOB NUMBER, CARGO_LINEA_IGV NUMBER, MONTO_DEVOLVER_IGV NUMBER, MSISDN_DEVOLVER VARCHAR2(20), CUSTOMER_ID_DEVOLVER VARCHAR2(50 CHAR), CUSTCODE_DEVOLVER VARCHAR2(255 CHAR), CO_ID_DEVOLVER VARCHAR2(50), MODO_CONTRATACION_DEV VARCHAR2(20), TIPO_CLIENTE_DEV VARCHAR2(500), FECHA_ACTIVACION_DEV DATE, CICLOFACTURACION_DEV VARCHAR2(10 CHAR), SISTEMA_ORIGEN_DEV VARCHAR2(50), DEV NUMBER, MODALIDAD_DEV VARCHAR2(100), CARGO_LINEA_IGV_DEV NUMBER, MONTO_DEVOLVER_IGV_DEV NUMBER, TASA NUMBER, INTERES NUMBER, MTO_TOTAL_DEV_IGV NUMBER, FCH_DEV DATE, INTERES_AL DATE, GLOSA VARCHAR2(250), FAC_ULTI_SBS NUMBER, FAC_NUEV_REFE NUMBER, FAC_ACUM_PROY NUMBER, FAC_ACUM_INI NUMBER
        )"];
        $this->exec_sql($queries);

        foreach($dataToInsert as $row){
            DB::connection($this->connection)
            ->table("USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier}")
            ->insert([
                "ticket" => $row->ticket,
                "msisdn" => $row->msisdn,
                "msisdn_actual" => $row->msisdn_actual,
                "celda" => $row->celda,
                "id_cliente" => $row->id_cliente,
                "nombres" => $row->nombres,
                "apellidos" => $row->apellidos,
                "tipo_documento" => $row->tipo_documento,
                "nro_documento" => $row->nro_documento,
                "customer_full_name" => $row->customer_full_name,
                "tipo_cliente" => $row->tipo_cliente,
                "sistema_origen" => $row->sistema_origen,
                "tmcode" => $row->tmcode,
                "plan_tarifario" => $row->plan_tarifario,
                "estado_actual" => $row->estado_actual,
                "customer_id" => $row->customer_id,
                "custcode" => $row->custcode,
                "contrato" => $row->contrato,
                "modo_contratacion" => $row->modo_contratacion,
                "fecha_activacion" => $row->fecha_activacion,
                "fecha_baja" => $row->fecha_baja,
                "ciclofacturacion" => $row->ciclofacturacion,
                "departamento" => $row->departamento,
                "provincia" => $row->provincia,
                "distrito" => $row->distrito,
                "fecha_corte" => $row->fecha_corte,
                "fecha_carga" => $row->fecha_carga,
                "cargo_acceso_normal" => $row->cargo_acceso_normal,
                "cargo_combo" => $row->cargo_combo,
                "cargo_bolsa" => $row->cargo_bolsa,
                "cargo_linea" => $row->cargo_linea,
                "flg_gob" => $row->flg_gob,
                "cargo_linea_igv" => $row->cargo_linea_igv,
                "monto_devolver_igv" => $row->monto_devolver_igv,
                "msisdn_devolver" => $row->msisdn_devolver,
                "customer_id_devolver" => $row->customer_id_devolver,
                "custcode_devolver" => $row->custcode_devolver,
                "co_id_devolver" => $row->co_id_devolver,
                "modo_contratacion_dev" => $row->modo_contratacion_dev,
                "tipo_cliente_dev" => $row->tipo_cliente_dev,
                "fecha_activacion_dev" => $row->fecha_activacion_dev,
                "ciclofacturacion_dev" => $row->ciclofacturacion_dev,
                "sistema_origen_dev" => $row->sistema_origen_dev,
                "dev" => $row->dev,
                "modalidad_dev" => $row->modalidad_dev,
                "cargo_linea_igv_dev" => $row->cargo_linea_igv_dev,
                "monto_devolver_igv_dev" => $row->monto_devolver_igv_dev,
                "tasa" => $row->tasa,
                "interes" => $row->interes,
                "mto_total_dev_igv" => $row->mto_total_dev_igv,
                "fch_dev" => $row->fch_dev,
                "interes_al" => $row->interes_al,
                "glosa" => $row->glosa,
                "fac_ulti_sbs" => $row->fac_ulti_sbs,
                "fac_nuev_refe" => $row->fac_nuev_refe,
                "fac_acum_proy" => $row->fac_acum_proy,
                "fac_acum_ini" => $row->fac_acum_ini,
            ]);
        }

        $queries = [];

        $queries[] = ["sql" => "DECLARE
            V_FECHA_MAX_SBS DATE;
            V_FECHA_INTERES DATE := TO_DATE('{$strFechaInteres}', 'YYYYMMDD');
        BEGIN
            select max(fecha) INTO V_FECHA_MAX_SBS FROM USRAES.DWH_TASAINTERES_SBS;

            IF V_FECHA_INTERES > V_FECHA_MAX_SBS THEN
                UPDATE USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier} T
                SET T.FCH_DEV=TRUNC(SYSDATE),INTERES_AL=TRUNC(SYSDATE-1)
                WHERE MODALIDAD_DEV IN ('PREPAGO','WEB','WEB BOLSA')AND DEV=1;
                COMMIT;
                
                UPDATE USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier} T
                SET T.FCH_DEV =  NULL ,T.INTERES_AL =TRUNC(SYSDATE-1)
                WHERE MODALIDAD_DEV IN ('CONTROL','POSTPAGO_CONSUMER','POSTPAGO_CORPORATIVO')AND DEV=1; --SELECT * FROM TMP_DEV100_TK400709_BASEDEV
                COMMIT;
                
                UPDATE USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier} T
                SET T.FAC_ULTI_SBS =  
                (SELECT FACTORACUMULADO FROM USRAES.DWH_TASAINTERES_SBS WHERE FECHA=TRUNC(T.INTERES_AL))--FECHA DEL ÚLTIMO FA PUBLICADO POR SBS
                WHERE DEV=1;
                COMMIT;

                UPDATE USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier} T
                SET T.FAC_NUEV_REFE =
                    (SELECT FACTORACUMULADO
                        FROM USRAES.DWH_TASAINTERES_SBS
                        WHERE FECHA = TRUNC(T.INTERES_AL) - --FECHA DEL ÚLTIMO FA PUBLICADO POR SBS
                            (TO_DATE('{$strFechaInteres}', 'YYYYMMDD') - --COLOCAR FECHA PROGRAMADA PARA LA DEVOLUCIÓN PREPAGO
                            TRUNC(T.INTERES_AL))) --FECHA DEL ÚLTIMO FA PUBLICADO POR SBS
                WHERE T.MODALIDAD_DEV IN ('PREPAGO', 'WEB', 'WEB BOLSA')
                AND DEV = 1; --APLICA PARA WEB Y PREPAGO
                COMMIT;

                UPDATE USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier} T
                SET T.FAC_NUEV_REFE =(SELECT FACTORACUMULADO FROM USRAES.DWH_TASAINTERES_SBS WHERE FECHA=TRUNC(T.INTERES_AL) - --FECHA DEL ÚLTIMO FA PUBLICADO POR SBS
                (TO_DATE('{$strFechaInteres}','YYYYMMDD') - --COLOCAR FECHA PROGRAMADA PARA LA DEVOLUCIÓN POSTPAGO (YYYYMMDD)
                TRUNC(T.INTERES_AL)))--FECHA DEL ÚLTIMO FA PUBLICADO POR SBS
                WHERE T.MODALIDAD_DEV IN('POSTPAGO_CORPORATIVO','POSTPAGO_CONSUMER','CONTROL') AND DEV=1; --APLICA PARA WEB Y PREPAGO
                COMMIT;

                --PASO 13.- ACTUALIZA EL FACTOR PROYECTADO

                UPDATE USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier} T
                SET T.FAC_ACUM_PROY = (T.FAC_ULTI_SBS  * ( T.FAC_ULTI_SBS  / T.FAC_NUEV_REFE)) WHERE T.MODALIDAD_DEV IN('PREPAGO','WEB','WEB BOLSA') AND DEV=1;
                COMMIT;

                UPDATE USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier} T
                SET T.FAC_ACUM_PROY =(T.FAC_ULTI_SBS  * ( T.FAC_ULTI_SBS  / T.FAC_NUEV_REFE)) WHERE T.MODALIDAD_DEV IN('POSTPAGO_CORPORATIVO','POSTPAGO_CONSUMER','CONTROL') AND DEV=1;
                COMMIT;

                --PASO 14.- ACTUALIZA EL FACTOR ACUMULADO INICIAL 

                UPDATE USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier} T
                SET T.FAC_ACUM_INI =(SELECT FACTORACUMULADO FROM USRAES.DWH_TASAINTERES_SBS WHERE FECHA = TO_DATE('{$strCorteFechaIniF1}','YYYYMMDD'))--COLOCAR FECHA DE INICIO DE INTERRUPCION (YYYYMMDD)
                WHERE DEV=1;
                COMMIT;
            ELSE
                DECLARE
                    V_FACTO_ACUM_1 NUMBER;
                    V_FACTO_ACUM_2 NUMBER;
                BEGIN
                    SELECT FACTORACUMULADO INTO V_FACTO_ACUM_1 FROM USRAES.DWH_TASAINTERES_SBS WHERE FECHA = TO_DATE('{$strFechaInteres}','YYYYMMDD');
                    SELECT FACTORACUMULADO INTO V_FACTO_ACUM_2 FROM USRAES.DWH_TASAINTERES_SBS WHERE FECHA = TO_DATE('{$strCorteFechaIniF1}','YYYYMMDD');

                    UPDATE USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier} T
                    SET T.FAC_NUEV_REFE = NULL,
                    T.FAC_ACUM_PROY = V_FACTO_ACUM_1,--COLOCAR FECHA DE INTERES CALCULADO (YYYYMMDD)
                    T.FAC_ACUM_INI = V_FACTO_ACUM_2;--COLOCAR FECHA DE INICIO DE CORTE (YYYYMMDD);
                    COMMIT;
                END;
            END IF;

            --PASO 15.- ACTUALIZA LA TASA

            UPDATE USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier} T
            SET T.TASA =  DECODE(ROUND( T.FAC_ACUM_PROY/T.FAC_ACUM_INI - 1 ,2),0,0.01,ROUND( T.FAC_ACUM_PROY/T.FAC_ACUM_INI - 1 ,2))
            WHERE DEV=1;
            COMMIT;

            --PASO 16.- ACTUALIZA LOS INTERESES

            UPDATE USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier} T
            SET T.INTERES=DECODE(ROUND( ((T.MONTO_DEVOLVER_IGV_DEV * T.FAC_ACUM_PROY)/T.FAC_ACUM_INI ) - T.MONTO_DEVOLVER_IGV_DEV,2),0,0.01,ROUND( ((T.MONTO_DEVOLVER_IGV_DEV * T.FAC_ACUM_PROY)/T.FAC_ACUM_INI ) - T.MONTO_DEVOLVER_IGV_DEV,2))
            WHERE T.DEV=1;
            COMMIT;

            --PASO 16.- ACTUALIZA EL MONTO TOTAL A DEVOLVER INC IGV

            UPDATE USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier} T
            SET T.MTO_TOTAL_DEV_IGV=ROUND((T.MONTO_DEVOLVER_IGV_DEV+INTERES),2)
            WHERE DEV=1;
            COMMIT;
        END;"];
        $queries[] = ["sql" => "ALTER TABLE USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier} ADD FEC_INICIO DATE"];
        $queries[] = ["sql" => "BEGIN
            UPDATE USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier}
            SET FEC_INICIO=TO_DATE('{$strCorteFechaIniF1}','YYYYMMDD'); -- FECHA DE INICIO DE CORTE
            COMMIT;
        END;"];
        $queries[] = ["sql" => "BEGIN
            UPDATE USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier}
            SET FEC_INICIO=TO_DATE('{$strCorteFechaIniF1}','YYYYMMDD'); -- FECHA DE INICIO DE CORTE
            COMMIT;
        END;"];
        // save results
        $queries[] = ["sql" => "DECLARE
            V_TICKET VARCHAR2(100);
            V_DEPARTAMENTO VARCHAR2(100);
        BEGIN
            SELECT TICKET, DEPARTAMENTO INTO V_TICKET, V_DEPARTAMENTO FROM USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier}
            FETCH FIRST 1 ROWS ONLY;

            DELETE FROM USRAES.BASE_PREV_BASEDEV WHERE TICKET = V_TICKET and DEPARTAMENTO = V_DEPARTAMENTO;
            COMMIT;

            INSERT INTO USRAES.BASE_PREV_BASEDEV(
            TICKET, MSISDN, MSISDN_ACTUAL, CELDA, ID_CLIENTE, NOMBRES, APELLIDOS, TIPO_DOCUMENTO, NRO_DOCUMENTO,
            CUSTOMER_FULL_NAME, TIPO_CLIENTE, SISTEMA_ORIGEN, TMCODE, PLAN_TARIFARIO, ESTADO_ACTUAL, CUSTOMER_ID,
            CUSTCODE, CONTRATO, MODO_CONTRATACION, FECHA_ACTIVACION, FECHA_BAJA, CICLOFACTURACION, DEPARTAMENTO,
            PROVINCIA, DISTRITO, FECHA_CORTE, FECHA_CARGA, CARGO_ACCESO_NORMAL, CARGO_COMBO, CARGO_BOLSA, CARGO_LINEA,
            FLG_GOB, CARGO_LINEA_IGV, MONTO_DEVOLVER_IGV, MSISDN_DEVOLVER, CUSTOMER_ID_DEVOLVER, CUSTCODE_DEVOLVER,
            CO_ID_DEVOLVER, MODO_CONTRATACION_DEV, TIPO_CLIENTE_DEV, FECHA_ACTIVACION_DEV, CICLOFACTURACION_DEV,
            SISTEMA_ORIGEN_DEV, DEV, MODALIDAD_DEV, CARGO_LINEA_IGV_DEV, MONTO_DEVOLVER_IGV_DEV, TASA, INTERES,
            MTO_TOTAL_DEV_IGV, FCH_DEV, INTERES_AL, GLOSA, FAC_ULTI_SBS, FAC_NUEV_REFE, FAC_ACUM_PROY, FAC_ACUM_INI, FEC_INICIO,
            FECHA_HORA_EXEC, FECHA_INTERES
            )
            SELECT
            TICKET, MSISDN, MSISDN_ACTUAL, CELDA, ID_CLIENTE, NOMBRES, APELLIDOS, TIPO_DOCUMENTO, NRO_DOCUMENTO,
            CUSTOMER_FULL_NAME, TIPO_CLIENTE, SISTEMA_ORIGEN, TMCODE, PLAN_TARIFARIO, ESTADO_ACTUAL, CUSTOMER_ID,
            CUSTCODE, CONTRATO, MODO_CONTRATACION, FECHA_ACTIVACION, FECHA_BAJA, CICLOFACTURACION, DEPARTAMENTO,
            PROVINCIA, DISTRITO, FECHA_CORTE, FECHA_CARGA, CARGO_ACCESO_NORMAL, CARGO_COMBO, CARGO_BOLSA, CARGO_LINEA,
            FLG_GOB, CARGO_LINEA_IGV, MONTO_DEVOLVER_IGV, MSISDN_DEVOLVER, CUSTOMER_ID_DEVOLVER, CUSTCODE_DEVOLVER,
            CO_ID_DEVOLVER, MODO_CONTRATACION_DEV, TIPO_CLIENTE_DEV, FECHA_ACTIVACION_DEV, CICLOFACTURACION_DEV,
            SISTEMA_ORIGEN_DEV, DEV, MODALIDAD_DEV, CARGO_LINEA_IGV_DEV, MONTO_DEVOLVER_IGV_DEV, TASA, INTERES,
            MTO_TOTAL_DEV_IGV, FCH_DEV, INTERES_AL, GLOSA, FAC_ULTI_SBS, FAC_NUEV_REFE, FAC_ACUM_PROY, FAC_ACUM_INI, FEC_INICIO,
            sysdate, TO_DATE('{$strFechaInteres}', 'YYYYMMDD') fecha_interes
            FROM USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier};
            COMMIT;

            DELETE FROM USRAES.BASE_PREV_BASEDEV_HIST WHERE TICKET = V_TICKET and DEPARTAMENTO = V_DEPARTAMENTO;
            COMMIT;

            INSERT INTO USRAES.BASE_PREV_BASEDEV_HIST(
                TICKET, DEPARTAMENTO, FECHA_HORA_EXEC, NUMERO_AFECTADOS, NUMERO_AFECTADOS_POST,
                NUMERO_AFECTADOS_PRE, ACREDITADOS_POST, ACREDITADOS_PRE
            )
            SELECT TICKET,
            DEPARTAMENTO,
            FECHA_HORA_EXEC,
            SUM(CASE WHEN MODALIDAD_DEV LIKE '%POSTPAGO%' OR MODALIDAD_DEV LIKE '%PREPAGO%' THEN 1 ELSE 0 END) NUMERO_AFECTADOS,
            SUM(CASE WHEN MODALIDAD_DEV LIKE '%POSTPAGO%' THEN 1 ELSE 0 END) NUMERO_AFECTADOS_POST,
            SUM(CASE WHEN MODALIDAD_DEV LIKE '%PREPAGO%' THEN 1 ELSE 0 END) NUMERO_AFECTADOS_PRE,
            0,
            0 
            FROM USRAES.BASE_PREV_BASEDEV 
            WHERE TICKET= V_TICKET and DEPARTAMENTO = V_DEPARTAMENTO
            GROUP BY TICKET,DEPARTAMENTO,FECHA_HORA_EXEC;
            COMMIT;
        EXCEPTION WHEN NO_DATA_FOUND THEN
            V_TICKET := null;
        END;"];

        $this->exec_sql($queries);
    }

    public function ticketAndDepartamentoExistsInConsolidado($ticket, $departamento)
    {
        $data = DB::connection("oracle_reptdm")
        ->select(DB::raw("SELECT
        TICKET,
        DEPARTAMENTO 
        FROM USRAES.BASE_PREV_BASEDEV 
        WHERE TICKET = :p_ticket AND DEPARTAMENTO = :p_departamento
        GROUP BY TICKET,DEPARTAMENTO"), ["p_ticket" => $ticket, "p_departamento" => $departamento]);
        return count($data) > 0;
    }

    public function getBaseAbonados()
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        return DB::connection("oracle_reptdm")
        ->table("USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier}")
        ->selectRaw("TICKET,ID_CLIENTE,NOMBRES,APELLIDOS,MSISDN,TIPO_DOCUMENTO,NRO_DOCUMENTO,MODALIDAD_DEV,DEPARTAMENTO,ESTADO_ACTUAL ESTADO_CLIENTE,FECHA_BAJA")
        ->whereRaw("MODALIDAD_DEV LIKE '%WEB%'")
        ->get();
    }

    public function getBaseDevolucionPostpago(Datetime $fechaCorteIni)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        $strFechaCorteIni = $fechaCorteIni->format("d-m-Y");
        return DB::connection("oracle_reptdm")
        ->table("USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier}")
        ->selectRaw("TICKET,MSISDN,MSISDN_ACTUAL MSISDN_DEVOL,CARGO_LINEA,CARGO_LINEA_IGV,MONTO_DEVOLVER_IGV/1.18 MTO_DEV,
        MONTO_DEVOLVER_IGV MTO_DEV_IGV,INTERES,TASA,MTO_TOTAL_DEV_IGV MTO_TOTAL_DEV_IGV,CUSTCODE,CUSTOMER_ID,CO_ID_DEVOLVER,CICLOFACTURACION_DEV CICLOFACTURACION,SISTEMA_ORIGEN_DEV FUENTE,
        FECHA_ACTIVACION FCH_ACTIVACION,FECHA_ACTIVACION,'Dev. por interrupcion del {$strFechaCorteIni}. Tasa aplicada  0.01%' GLOSA")
        ->whereRaw("MODALIDAD_DEV LIKE '%POSTPAGO%'")
        ->get();
    }

    public function getBaseDevolucionPrepago(Datetime $fechaCorteIni)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        $strFechaCorteIni = $fechaCorteIni->format("d-m-Y");
        return DB::connection("oracle_reptdm")
        ->table("USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier}")
        ->selectRaw("MSISDN,MSISDN_ACTUAL MSISDN_DEVOL,MTO_TOTAL_DEV_IGV*100 CENTIMOS,'Dev. por interrupcion del {$strFechaCorteIni}. Tasa aplicada  0.01%' GLOSA")
        ->whereRaw("MODALIDAD_DEV LIKE '%PREPAGO%'")
        ->get();
    }

    public function saveResultsInlog(string $log_id, DateTime $fechaCorteIni)
    {
        $strFechaCorteIni = $fechaCorteIni->format("d-m-Y");
        DB::connection("oracle_reptdm")
        ->statement("BEGIN
            INSERT INTO USRAES.BASE_ABONADOS_WEB_MOVIL_LOG(ID_UNIQUE,TICKET,ID_CLIENTE,NOMBRES,APELLIDOS,MSISDN,TIPO_DOCUMENTO,
            NRO_DOCUMENTO,MODALIDAD_DEV,DEPARTAMENTO,ESTADO_CLIENTE,FECHA_BAJA)
            SELECT
            '{$log_id}' AS ID_UNIQUE,TICKET,ID_CLIENTE,NOMBRES,APELLIDOS,MSISDN,TIPO_DOCUMENTO,NRO_DOCUMENTO,MODALIDAD_DEV,DEPARTAMENTO,ESTADO_ACTUAL ESTADO_CLIENTE,FECHA_BAJA
            FROM USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier}
            WHERE MODALIDAD_DEV LIKE '%WEB%';
            COMMIT;
            
            
            INSERT INTO USRAES.BASE_DEVOLUCION_POSTPAGO_LOG(
            ID_UNIQUE, TICKET, MSISDN, MSISDN_DEVOL, CARGO_LINEA, CARGO_LINEA_IGV, MTO_DEV, MTO_DEV_IGV, INTERES, TASA,
            MTO_TOTAL_DEV_IGV, CUSTCODE, CUSTOMER_ID, CO_ID_DEVOLVER, CICLOFACTURACION, FUENTE, FCH_ACTIVACION, FECHA_ACTIVACION, GLOSA
            )
            SELECT
            '{$log_id}' AS ID_UNIQUE,TICKET,MSISDN,MSISDN_ACTUAL MSISDN_DEVOL,CARGO_LINEA,CARGO_LINEA_IGV,MONTO_DEVOLVER_IGV/1.18 MTO_DEV,
            MONTO_DEVOLVER_IGV MTO_DEV_IGV,INTERES,TASA,MTO_TOTAL_DEV_IGV MTO_TOTAL_DEV_IGV,CUSTCODE,CUSTOMER_ID,CO_ID_DEVOLVER,CICLOFACTURACION_DEV CICLOFACTURACION,SISTEMA_ORIGEN_DEV FUENTE,
            FECHA_ACTIVACION FCH_ACTIVACION,FECHA_ACTIVACION,
            'Dev. por interrupcion del 20000101 Tasa aplicada  0.01%' GLOSA
            FROM USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier} a where MODALIDAD_DEV LIKE '%POSTPAGO%';
            COMMIT;
            
            
            INSERT INTO USRAES.BASE_DEVOLUCION_PREPAGO_LOG(ID_UNIQUE, MSISDN,MSISDN_DEVOL,CENTIMOS,GLOSA)
            SELECT
            '{$log_id}' AS ID_UNIQUE,
            MSISDN,MSISDN_ACTUAL MSISDN_DEVOL,MTO_TOTAL_DEV_IGV*100 CENTIMOS,'Dev. por interrupcion del {$strFechaCorteIni}. Tasa aplicada  0.01%' GLOSA
            FROM USRAES.BASE_PREV_BASEDEV_{$this->userIdentifier} a where MODALIDAD_DEV LIKE '%PREPAGO%';
            COMMIT;
        END;");
    }


    public function getUsuariosAfectadosBy($ticket, $departamento)
    {
        return DB::connection("oracle_reptdm")
        ->select(DB::raw("select TICKET,
        ID_CLIENTE,
        NRO_DOCUMENTO,
        CUSTOMER_FULL_NAME NOMBRES_APELLIDOS,
        'Comunicaciones Personales(PCS)' SERVICIO_AFECTADO,
        MSISDN,
        DEPARTAMENTO 
        FROM USRAES.BASE_PREV_BASEDEV 
        WHERE TICKET= :p_ticket AND (MODALIDAD_DEV LIKE '%POSTPAGO%' OR MODALIDAD_DEV LIKE '%PREPAGO%')
        and DEPARTAMENTO= :p_departamento"), ["p_ticket" => $ticket, "p_departamento" => $departamento]);
    }

    public function getPostpagoBy($ticket, $departamento)
    {
        return DB::connection("oracle_reptdm")
        ->select(DB::raw("SELECT TICKET,MSISDN,MSISDN_DEVOLVER MSISDN_DEVOL,ROUND(CARGO_LINEA, 2) CARGO_LINEA,ROUND(CARGO_LINEA_IGV, 2) CARGO_LINEA_IGV,ROUND(MONTO_DEVOLVER_IGV/1.18, 2) MTO_DEV,
        ROUND(MONTO_DEVOLVER_IGV, 2) MTO_DEV_IGV,INTERES,TASA,MTO_TOTAL_DEV_IGV MTO_TOTAL_DEV_IGV,CUSTCODE_DEVOLVER AS CUSTCODE, CUSTOMER_ID_DEVOLVER CUSTOMER_ID,CO_ID_DEVOLVER,CICLOFACTURACION_DEV CICLOFACTURACION,SISTEMA_ORIGEN_DEV FUENTE,
        FECHA_ACTIVACION FECHA_ALTA,FECHA_ACTIVACION,'Dev. por interrupcion del ' || TO_CHAR(FECHA_CORTE, 'DD-MM-YYYY') || '. Tasa aplicada  0.01%' GLOSA
        from USRAES.BASE_PREV_BASEDEV
        WHERE TICKET= :p_ticket
        AND  MODALIDAD_DEV LIKE '%POSTPAGO%'
        and DEPARTAMENTO= :p_departamento"), ["p_ticket" => $ticket, "p_departamento" => $departamento]);
    }

    public function getPrepagoBy($ticket, $departamento)
    {
        return DB::connection("oracle_reptdm")
        ->select(DB::raw("SELECT 
        MSISDN,ID_CLIENTE,NRO_DOCUMENTO,MSISDN_DEVOLVER MSISDN_DEVOL,MTO_TOTAL_DEV_IGV*100 CENTIMOS,
        'Dev. por interrupcion del ' || TO_CHAR(FECHA_CORTE, 'DD-MM-YYYY') || '. Tasa aplicada  0.01%' GLOSA
        FROM USRAES.BASE_PREV_BASEDEV
        WHERE TICKET= :p_ticket
        AND MODALIDAD_DEV LIKE '%PREPAGO%'
        and DEPARTAMENTO= :p_departamento"), ["p_ticket" => $ticket, "p_departamento" => $departamento]);
    }

    public function updateReporte($numero, $excel)
    {
        $exists = DB::table("usraes.noc_informe_de_fallas")->where('numero_de_reporte', $numero)->exists();
        if(!$exists) {
            $reporte = DB::table("usraes.noc_informe_de_fallas")
            ->insert([         
                "numero_de_reporte" => $numero,
                "name_file" => $excel
            ]);
            return 1;
        }else{
            return 0;
        }        
    }

    public function getReportes()
    {
        return DB::table("usraes.noc_informe_de_fallas")
        ->selectRaw("numero_de_reporte,ticket,name_file,fecha_carga,revisado,aprobado,procesado,acreditado_pre,acreditado_post,
        en_ejecucion_pre, en_ejecucion_post")
        ->orderByDesc('fecha_carga')
        // ->take(10)
        ->get();
    }

    public function findInputFor($numero)
    {
        return DB::table("usraes.noc_informe_de_fallas")
        ->selectRaw("numero_de_reporte,ticket,name_file,fecha_carga,revisado,aprobado,procesado,acreditado_pre,acreditado_post,
        en_ejecucion_pre, en_ejecucion_post")
        ->where('numero_de_reporte',$numero)
        ->get();
    }

    public function findInformeByTicket($ticket)
    {
        return DB::table("usraes.noc_informe_de_fallas")
        ->selectRaw("numero_de_reporte,ticket,name_file,fecha_carga,revisado,aprobado,procesado,acreditado_pre,acreditado_post,
        en_ejecucion_pre, en_ejecucion_post")
        ->where('ticket', $ticket)
        ->first();
    }

    public function deleteRecord($numero)
    {
        DB::connection($this->connection)
        ->table("usraes.base_ext_dev_input")
        ->where("num_reporte", $numero)
        ->delete();

        DB::connection($this->connection)
        ->table("usraes.base_ext_dev_celda")
        ->where("num_reporte", $numero)
        ->delete();

        DB::connection($this->connection)
        ->table("usraes.base_ext_dev_dist")
        ->where("num_reporte", $numero)
        ->delete();

        DB::connection($this->connection)
        ->table("usraes.base_ext_dev_msisdn")
        ->where("num_reporte", $numero)
        ->delete();

        return DB::table("usraes.noc_informe_de_fallas")
        ->where('numero_de_reporte',$numero)
        ->delete();
    }

    public function registerStatusChanges(string $numReporte, ?string $departamento, string $username, string $ipAddress, string $status, DateTime $fecha)
    {
        DB::table("usraes.noc_informe_de_fallas_status_log")
        ->insert([
            "numero_reporte" => $numReporte,
            "departamento" => $departamento,
            "username" => $username,
            "ip_address" => $ipAddress,
            "status" => $status,
            "fecha" => $fecha,
        ]);
    }

    public function aprobar($id,$ticket)
    {
        $exists = DB::table("usraes.noc_informe_de_fallas")->where('ticket', $ticket)->exists();
        if(!$exists) {
            DB::table("usraes.noc_informe_de_fallas")
            ->where('numero_de_reporte',$id)
            ->update(['ticket' => $ticket, 'aprobado' => '1']);
            return 1;
        }else{
            return 0;
        }
    }

    public function desaprobar($id)
    {
        return DB::table("usraes.noc_informe_de_fallas")
        ->where('numero_de_reporte',$id)
        ->update(['aprobado' => '2','ticket' => '']);
    }

    public function enEspera($id)
    {
        return DB::table("usraes.noc_informe_de_fallas")
        ->where('numero_de_reporte',$id)
        ->update(['aprobado' => '0','ticket' => '']);
    }

    public function revisado($id)
    {
        return DB::table("usraes.noc_informe_de_fallas")
        ->where('numero_de_reporte',$id)
        ->update(['revisado' => '1']);
    }

    public function procesado($id)
    {
        return DB::table("usraes.noc_informe_de_fallas")
        ->where('numero_de_reporte',$id)
        ->update(['procesado' => '1']);
    }

    public function enEjecucionPre($id){
        DB::table("usraes.noc_informe_de_fallas")
        ->where('numero_de_reporte', $id)
        ->update(['en_ejecucion_pre' => '1']);
    }

    public function enEsperaPre($id){
        DB::table("usraes.noc_informe_de_fallas")
        ->where('numero_de_reporte', $id)
        ->update(['en_ejecucion_pre' => '0']);
    }

    public function enEjecucionPost($id){
        DB::table("usraes.noc_informe_de_fallas")
        ->where('numero_de_reporte', $id)
        ->update(['en_ejecucion_post' => '1']);
    }

    public function enEsperaPost($id){
        DB::table("usraes.noc_informe_de_fallas")
        ->where('numero_de_reporte', $id)
        ->update(['en_ejecucion_post' => '0']);
    }

    public function acreditadoPost($id, bool $acreditado){
        DB::table("usraes.noc_informe_de_fallas")
        ->where('numero_de_reporte', $id)
        ->update(['acreditado_post' => $acreditado ? '1' : '0']);
    }

    public function acreditadoPre($id, bool $acreditado){
        DB::table("usraes.noc_informe_de_fallas")
        ->where('numero_de_reporte', $id)
        ->update(['acreditado_pre' => $acreditado ? '1' : '0']);
    }

    public function getReportesSnRevisado()
    {
        return DB::table("usraes.noc_informe_de_fallas")
        ->selectRaw("ticket,numero_de_reporte,name_file")
        ->where('revisado','0')
        ->get();
    }

    public function getReportesAprobados()
    {
        return DB::table("usraes.noc_informe_de_fallas")
        ->selectRaw("ticket,numero_de_reporte,name_file")
        ->where('aprobado','1')
        ->get();
    }

    public function getReportesProcesados()
    {
        return DB::table("usraes.noc_informe_de_fallas")
        ->selectRaw("ticket,numero_de_reporte,name_file")
        ->where('procesado','1')
        ->get();
    }

    public function saveAcreditacionPrepago($ticket, $departamento, $data)
    {
        DB::connection("oracle_reptdm")
        ->table("USRAES.ACREDITACION_PREPAGO_TEMP")
        ->where("ticket", $ticket)
        ->where("departamento", $departamento)
        ->delete();

        DB::table("USRAES.ACREDITACION_PREPAGO_TEMP")
        ->where("ticket", $ticket)
        ->where("departamento", $departamento)
        ->delete();

        foreach($data as $row){
            $values = [
                "ticket" => $ticket,
                "departamento" => $departamento,
                "customerid" => $row["CustomerId"],
                "package" => $row["Package"],
                "balanceadjustmode" => $row["BalanceAdjustMode"],
                "balanceadjustvalue" => $row["BalanceAdjustValue"],
                "eventinfo" => $row["@EventInfo"],
                "balanceused" => $row["BalanceUsed"],
                "chargedamount" => $row["ChargedAmount"],
                "balance" => $row["Balance"],
                "messageId" => $row["@MessageId"],
                "accountexpirydatepolicy" => $row["AccountExpiryDatePolicy"]??null,
                "accountexpirydate" => $row["AccountExpiryDate"]??null,
                "status" => $row["Status"]??null,
                "substate" => $row["SubState"]??null,
                "chargemode" => $row["ChargeMode"],
                "expirationdatepolicy" => $row["ExpirationDatePolicy"],
                "absolutedate" => $row["AbsoluteDate"],
                "activeenddate" => $row["ActiveEndDate"],
                "graceenddate" => $row["GraceEndDate"],
            ];
            DB::connection("oracle_reptdm")
            ->table("USRAES.ACREDITACION_PREPAGO_TEMP")
            ->insert($values);

            DB::table("USRAES.ACREDITACION_PREPAGO_TEMP")->insert($values);
        }
    }

    public function saveReportInputs(
        $num_reporte,
        $tipoReporte,
        array $celdas,
        array $distritos,
        DateTime $fechaIni,
        DateTime $fechaFin,
        $ticketOsiptel,
        DateTime $fechaInteres,
        DateTime $corteFechaIni,
        Datetime $corteFechaFin
    ) {
        $codigo_c = $this->authService->getUserIdentifier();;
        $departamento = $distritos[0][0];
        DB::connection($this->connection)
        ->table("usraes.base_ext_dev_input")
        ->where("num_reporte", $num_reporte)
        ->where("departamento", $departamento)
        ->delete();

        DB::connection($this->connection)
        ->table("usraes.base_ext_dev_celda")
        ->where("num_reporte", $num_reporte)
        ->where("departamento", $departamento)
        ->delete();

        DB::connection($this->connection)
        ->table("usraes.base_ext_dev_dist")
        ->where("num_reporte", $num_reporte)
        ->where("departamento", $departamento)
        ->delete();

        DB::connection($this->connection)
        ->table("usraes.base_ext_dev_input")
        ->insert([
            "num_reporte" => $num_reporte,
            "tipo_reporte" => $tipoReporte,
            "departamento" => $departamento,
            "codigo_c" => $codigo_c,
            "fecha_ini" => $fechaIni->format("Y-m-d H:i:s"),
            "fecha_fin" => $fechaFin->format("Y-m-d H:i:s"),
            "corte_fecha_ini" => $corteFechaIni->format("Y-m-d H:i:s"),
            "corte_fecha_fin" => $corteFechaFin->format("Y-m-d H:i:s"),
            "fecha_interes" => $fechaInteres->format("Y-m-d H:i:s"),
        ]);

        foreach($celdas as $celda){
            DB::connection($this->connection)
            ->table("USRAES.base_ext_dev_celda")
            ->insert([
                "num_reporte" => $num_reporte,
                "departamento" => $departamento,
                "codigo_c" => $codigo_c,
                "celda" => $celda,
            ]);
        }
        foreach($distritos as $row){
            DB::connection($this->connection)
            ->table("USRAES.base_ext_dev_dist")
            ->insert([
                "num_reporte" => $num_reporte,
                "codigo_c" => $codigo_c,
                "departamento" => $row[0],
                "provincia" => $row[1],
                "distrito" => $row[2],
            ]);
        }
    }

    public function saveInputMsisdn($num_reporte, $data){
        foreach($data as $row){
            DB::connection("oracle_reptdm")
            ->table("usraes.base_ext_dev_msisdn")
            ->insert([
                "num_reporte" => $num_reporte,
                "ticket" => $row["ticket"],
                "msisdn" => $row["msisdn"],
                "fecha_carga" => $row["fecha_carga"],
                "celda" => $row["celda"],
                "fecha_corte" => $row["fecha_corte"],
                "departamento" => $row["departamento"],
                "provincia" => $row["provincia"],
                "distrito" => $row["distrito"],
                "comentario" => $row["comentario"],
                "cod_cliente" => array_key_exists("cod_cliente", $row) ? $row["cod_cliente"] : null,
                "num_documento" => array_key_exists("num_documento", $row) ? $row["num_documento"] : null,
                "nombres_apellidos" => array_key_exists("nombres_apellidos", $row) ? $row["nombres_apellidos"] : null,
                // "apellidos" => array_key_exists("apellidos", $row) ? $row["apellidos"] : null,
                "monto_dev_igv" => array_key_exists("monto_dev_igv", $row) ? $row["monto_dev_igv"] : null,
            ]);
        }
    }

    public function updateTicketForInputMsisdn($num_reporte, $ticket){
        DB::connection("oracle_reptdm")
        ->table("usraes.base_ext_dev_msisdn")
        ->where("num_reporte", $num_reporte)
        ->update([
            "ticket" => $ticket,
        ]);
    }

    public function getInputByTicket($ticket) {
        $informe = DB::table("usraes.noc_informe_de_fallas")->where('ticket', $ticket)->first();
        $informeInput = DB::connection("oracle_reptdm")->table("usraes.base_ext_dev_input")
        ->where('num_reporte', $informe !== null ? $informe->numero_de_reporte : null)->first();
        return $informeInput;
    }

    public function getInputByNumReporte_Departamento($num_reporte, $departamento)
    {
        $input = DB::connection($this->connection)
        ->table("usraes.base_ext_dev_input")
        ->where("num_reporte", $num_reporte)
        ->where("departamento", $departamento)
        ->first();

        $celdas = DB::connection($this->connection)
        ->table("usraes.base_ext_dev_celda")
        ->where("num_reporte", $num_reporte)
        ->where("departamento", $departamento)
        ->get();

        $distritos = DB::connection($this->connection)
        ->table("usraes.base_ext_dev_dist")
        ->where("num_reporte", $num_reporte)
        ->where("departamento", $departamento)
        ->get();

        $reporte = DB::table("usraes.noc_informe_de_fallas")
        ->selectRaw("ticket,numero_de_reporte,name_file")
        ->where("numero_de_reporte", $num_reporte)
        ->first();
        
        if($input !== null){
            $input->celdas = [];
            foreach($celdas as $row){
                $input->celdas[] = $row->celda;
            }
            $input->distritos = $distritos;
            $input->ticket = null;
            if($reporte !== null){
                $input->ticket = $reporte->ticket;
            }
            return $input;
        }
        return null;
    }

    public function getDepartamentosByNumReporte($num_reporte)
    {
        $result = DB::connection($this->connection)
        ->table("usraes.base_ext_dev_input")
        ->where("num_reporte", $num_reporte)
        ->get();
        return $result;
    }

    public function getReportesByCriteria($filters)
    {
        $builder = DB::table("usraes.noc_informe_de_fallas")
        ->selectRaw("*");

        foreach($filters as $row){
            $builder->where($row[0], $row[1]);
        }

        return $builder->get();
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
