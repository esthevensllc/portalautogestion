<?php

namespace AMovil\Reports\RepDetConsumo\Infrastructure\Repository;

use AMovil\Auth\AccessControl\Domain\AuthService;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;

class LaravelDetalleConsumoRepository
{
    private $userIdentifier;
    private $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
        $this->userIdentifier = $this->authService->getUserIdentifier();
    }
    
    public function generateReporteConsolidado(string $cliente, array $periodos, string $unidad_trafico_id, string $unidad_consumo_id, ?DateTime $fecha1, ?DateTime $fecha2)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();

        $binds = [];
        $str_binds_v = [];
        foreach($periodos as $i => $p){
            $binds['p_periodo_'.$i] = $p;
            $str_binds_v[] = ':p_periodo_'.$i;
        }
        $str_binds_v = implode(",", $str_binds_v);

        $unidad_trafico_by_id = [
            '1' => '',
            '2' => '*1024',
            '3' => '*1024*1024',
        ];
        $unidad_consumo_by_id = [
            '1' => ':f',
            '2' => "CASE WHEN floor(:f/60) < 10 THEN lpad(to_char(floor(:f/60)), 2, '0') ELSE to_char(floor(:f/60)) END
            || ':' || lpad(to_char((:f - floor(:f/60)*60)), 2, '0') :f",
            '3' => "CASE WHEN floor(:f/3600) < 10 THEN lpad(to_char(floor(:f/3600)) , 2, '0') ELSE to_char(floor(:f/3600)) END
            || ':' || lpad(to_char(floor((:f - floor(:f/3600)*3600)/60)), 2, '0') || ':' || lpad(to_char(:f - floor(:f/60)*60), 2, '0') :f",
        ];
        $def_unidad_consumo = "0";


        $sql_unidad_trafico = $unidad_trafico_by_id[$unidad_trafico_id];
        $sql_unidad_consumo = $unidad_consumo_by_id[$unidad_consumo_id];
        if($unidad_consumo_id === '1'){
            $def_unidad_consumo = "00";
        }else if ($unidad_consumo_id === '2') {
            $def_unidad_consumo = "00:00";
        }else if ($unidad_consumo_id === '3') {
            $def_unidad_consumo = "00:00:00";
        }

        $sqls = [];
        $sqls[] = ['sql' => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.T21_T_TAG_11_{$this->userIdentifier} PURGE';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_T_TAG_11_{$this->userIdentifier} AS SELECT * FROM TEMP_TAG_11 where 1=2"];
        $sqls[] = ['sql' => "BEGIN
            INSERT INTO USRAES.T21_T_TAG_11_{$this->userIdentifier}
            SELECT * FROM TEMP_TAG_11 WHERE CLIENTACCTNO in (:p_cod_cli) AND 
            PERIODO in({$str_binds_v});
            COMMIT;
        END;", 'params' => array_merge(['p_cod_cli' => $cliente], $binds)];

        $sqls[] = ['sql' => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.T21_T_TAG_1470_{$this->userIdentifier} PURGE';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_T_TAG_1470_{$this->userIdentifier} AS
        SELECT
        INVOICENUMBER NRO_FACTURA
        ,MSISDN NRO_TEL_ORIGEN
        ,TO_CHAR(CALLDATE,'DD/MM/YYYY') FECHA
        ,TO_CHAR(TO_DATE(SUBSTR('000000'||CALLTIME,-6),'HH24MISS'),'HH:MI:SS AM') HORA_INICIO
        ,CASE 
            WHEN CALLDESTINATION=1 THEN TO_CHAR(TO_DATE(SUBSTR('000000'||CALLTIME,-6),'HH24MISS') + (CASE 
            WHEN TRANSLATE(CALLDURATION,'+-0123456789',' ') IS NULL 
            THEN TO_NUMBER(CALLDURATION)
            WHEN TRANSLATE(CALLDURATION,'+-0123456789.',' ') IS NULL
            THEN REPLACE(CALLDURATION,'.',',')*60
            WHEN TRANSLATE(CALLDURATION,'0123456789:',' ') IS NULL
            THEN (SUBSTR(CALLDURATION,1,
                INSTR(CALLDURATION,':')-1)*60)+(SUBSTR(CALLDURATION,INSTR(CALLDURATION,':')+1))
            ELSE 0
        END)/86400,'HH:MI:SS AM')
            WHEN CALLDESTINATION=5 THEN null 
        END HORA_FIN 
        ,CALLORIGIN PAIS
        ,CALLNUMBER NRO_TEL_DESTINO
        ,CALLDURATION CONSUMO
        ,CALLDESTINATION DESTINO
        ,' ' OPERADOR
        ,'Saliente' TIPO_LLAMADA
        ,CALLTOTAL CARGO_FINAL
        FROM TEMP_TAG_1470 T1470 WHERE TRANSLATE(CALLTIME,'+-0123456789',' ') IS NULL
        AND INVOICENUMBER in (SELECT INVOICENUMBER FROM USRAES.T21_T_TAG_11_{$this->userIdentifier})"];

        $sqls[] = ['sql' => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.T21_T_TAG_1460_{$this->userIdentifier} PURGE';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_T_TAG_1460_{$this->userIdentifier} AS
        SELECT
        INVOICENUMBER NRO_FACTURA
        ,MSISDN NRO_TEL_ORIGEN
        ,TO_CHAR(CALLDATE,'DD/MM/YYYY') FECHA
        ,CASE WHEN SUBSTR(SUBSTR('000000'||CALLTIME,-6),3,2)>'59' OR SUBSTR(SUBSTR('000000'||CALLTIME,-6),1,2)>'23' 
        OR SUBSTR(SUBSTR('000000'||CALLTIME,-6),5,2)>'59'
        THEN TO_CHAR(TO_DATE(SUBSTR('000000'||SUBSTR(CALLTIME,1,5),-6),'HH24MISS'),'HH:MI:SS AM')
        ELSE TO_CHAR(TO_DATE(SUBSTR('000000'||CALLTIME,-6),'HH24MISS'),'HH:MI:SS AM')
        END HORA_INICIO
        ,TO_CHAR(CASE WHEN SUBSTR(SUBSTR('000000'||CALLTIME,-6),3,2)>'59' OR 
        SUBSTR(SUBSTR('000000'||CALLTIME,-6),1,2)>'23' OR SUBSTR(SUBSTR('000000'||CALLTIME,-6),5,2)>'59'
        THEN TO_DATE(SUBSTR('000000'||SUBSTR(CALLTIME,1,5),-6),'HH24MISS')
        ELSE TO_DATE(SUBSTR('000000'||CALLTIME,-6),'HH24MISS')
        END + (CASE 
        WHEN TRANSLATE(CALLDURATION,'+-0123456789',' ') IS NULL 
            THEN TO_NUMBER(CALLDURATION)
        WHEN TRANSLATE(CALLDURATION,'+-0123456789.',' ') IS NULL
            THEN REPLACE(CALLDURATION,'.',',')*60
        WHEN TRANSLATE(CALLDURATION,'0123456789:',' ') IS NULL
            THEN (SUBSTR(CALLDURATION,1,INSTR(CALLDURATION,':')-1)*60)+
            (SUBSTR(CALLDURATION,INSTR(CALLDURATION,':')+1))
        ELSE 0
        END)/86400,'HH:MI:SS AM') HORA_FIN
        ,CALLORIGIN PAIS
        ,(case when (substr(callnumber,1,2)='51' and substr(callnumber,-9,1)='9') then (substr(callnumber,-9))
            when (substr(callnumber,1,2)='51' and substr(callnumber,-9,1)='1') then (substr(callnumber,-8))
            else callnumber end) NRO_TEL_DESTINO
        ,CALLDURATION CONSUMO
        ,'Llamada Saliente' TIPO_SERVICIO
        ,CASE
        WHEN SUBSTR(TARIFFZONE,1,3)IN ('RPV','RTP') THEN 'RPC'
        WHEN SUBSTR(TARIFFZONE,1,3)IN ('NET','TDA') THEN 'OnNet'
        WHEN SUBSTR(TARIFFZONE,1,3)='FIJ' THEN 'OffNet Fijo'
        WHEN SUBSTR(TARIFFZONE,1,3)='MOV' THEN 'OffNet Movil'
        WHEN SUBSTR(TARIFFZONE,1,3)='LDN' THEN 'LDN'
        WHEN SUBSTR(TARIFFZONE,1,3)='LDI' THEN 'LDI'
        WHEN SUBSTR(TARIFFZONE,1,3)='GRA' THEN 'SIN CARGO'
        END DESTINO
        ,CALLDESTINATION OPERADOR
        ,'Saliente' TIPO_LLAMADA
        ,CALLTOTAL CARGO_FINAL
        from TEMP_TAG_1460 T1460
        WHERE INVOICENUMBER in (SELECT INVOICENUMBER FROM USRAES.T21_T_TAG_11_{$this->userIdentifier})"];


        $sqls[] = ['sql' => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.T21_T_TAG_1480_{$this->userIdentifier} PURGE';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_T_TAG_1480_{$this->userIdentifier} AS        
        SELECT
        INVOICENUMBER NRO_FACTURA
        ,MSISDN NRO_TEL_ORIGEN
        ,TO_CHAR(SMSDATE,'DD/MM/YYYY') FECHA
        ,CASE WHEN SUBSTR(SUBSTR('000000'||SMSTIME,-6),1,2)>'23'
        THEN TO_CHAR(TO_DATE(SUBSTR('000000'||SUBSTR(SMSTIME,1,5),-6),'HH24MISS'),'HH:MI:SS AM')
        ELSE TO_CHAR(TO_DATE(SUBSTR('000000'||SMSTIME,-6),'HH24MISS'),'HH:MI:SS AM')
        END HORA_INICIO
        ,' ' HORA_FIN
        ,SMSORIGIN PAIS
        ,SMSNUMBER NRO_TEL_DESTINO
        ,(SMSDURATION) CONSUMO
        ,CASE 
        WHEN TARIFFZONE NOT IN ('NET03','MAI01','DAT01') THEN 'SMS Saliente'
        WHEN TARIFFZONE IN ('NET03','MAI01') THEN 'MMS Saliente' 
        WHEN TARIFFZONE IN ('DAT01') THEN 'Datos'
        END TIPO_SERVICIO
        ,CASE 
        WHEN TARIFFZONE NOT IN ('NET03','MAI01','DAT01') THEN 'SMS'
        WHEN TARIFFZONE IN ('NET03','MAI01') THEN 'MMS'
        WHEN TARIFFZONE IN ('DAT01') THEN 'GPRS'
        END DESTINO
        ,SMSDESTINATION OPERADOR
        ,'Saliente' TIPO_LLAMADA
        ,SMSTOTAL CARGO_FINAL
        FROM TEMP_TAG_1480 T1480 
        WHERE INVOICENUMBER in (SELECT INVOICENUMBER FROM USRAES.T21_T_TAG_11_{$this->userIdentifier})"];

        $sqls[] = ['sql' => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.T21_T_TAG_1470_1_{$this->userIdentifier} PURGE';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_T_TAG_1470_1_{$this->userIdentifier} AS
        select NRO_FACTURA,NRO_TEL_ORIGEN,FECHA,HORA_INICIO,HORA_FIN,PAIS,NRO_TEL_DESTINO,CONSUMO,
        CASE WHEN HORA_FIN IS NOT NULL THEN 'VOZ'
            WHEN HORA_FIN IS NULL AND CONSUMO = '1' THEN 'SMS'
            ELSE 'DATOS' END TIPO_SERVICIO,OPERADOR,TIPO_LLAMADA,CARGO_FINAL FROM USRAES.T21_T_TAG_1470_{$this->userIdentifier}"];

        $sqls[] = ['sql' => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.T21_T_TAG_1470_2_{$this->userIdentifier} PURGE';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_T_TAG_1470_2_{$this->userIdentifier} AS
        select NRO_FACTURA,NRO_TEL_ORIGEN,FECHA,HORA_INICIO,HORA_FIN,PAIS,NRO_TEL_DESTINO,CONSUMO,TIPO_SERVICIO,
        CASE WHEN TIPO_SERVICIO  ='VOZ' THEN 'ROAMING VOZ'
        WHEN TIPO_SERVICIO  ='DATOS' THEN 'ROAMING DATOS'
            WHEN TIPO_SERVICIO = 'SMS' THEN 'ROAMING SMS'
        END DESTINO,OPERADOR,TIPO_LLAMADA,CARGO_FINAL FROM USRAES.T21_T_TAG_1470_1_{$this->userIdentifier}"];

        $sqls[] = ['sql' => "BEGIN
            UPDATE USRAES.T21_T_TAG_1460_{$this->userIdentifier}
            SET DESTINO = 'OnNet' 
            WHERE DESTINO IS NULL AND OPERADOR = 'CLA';
            COMMIT;

            DELETE FROM USRAES.T21_T_TAG_1460_{$this->userIdentifier} WHERE NRO_TEL_DESTINO LIKE '800%';
            COMMIT;
        END;"];

        $sqls[] = ['sql' => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.T21_T_TAG_1460_1_{$this->userIdentifier} PURGE';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_T_TAG_1460_1_{$this->userIdentifier} AS
        SELECT NRO_FACTURA,NRO_TEL_ORIGEN,FECHA,HORA_INICIO,HORA_FIN,PAIS,NRO_TEL_DESTINO,CONSUMO,'VOZ' TIPO_SERVICIO,DESTINO,OPERADOR,TIPO_LLAMADA,CARGO_FINAL FROM  USRAES.T21_T_TAG_1460_{$this->userIdentifier}"];

        $sqls[] = ['sql' => "BEGIN
            UPDATE USRAES.T21_T_TAG_1480_{$this->userIdentifier}
            SET TIPO_SERVICIO = 'DATOS'
            WHERE TIPO_SERVICIO = 'Datos';
            COMMIT;
        END;"];

        $sqls[] = ['sql' => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.T21_T_TAG_1480_1_{$this->userIdentifier} PURGE';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_T_TAG_1480_1_{$this->userIdentifier} AS
        SELECT NRO_FACTURA,NRO_TEL_ORIGEN,FECHA,HORA_INICIO,HORA_FIN,PAIS,NRO_TEL_DESTINO,CONSUMO,TIPO_SERVICIO,
        CASE WHEN TIPO_SERVICIO='DATOS' THEN DESTINO
            WHEN TIPO_SERVICIO = 'SMS Saliente' THEN DESTINO 
            ELSE DESTINO END DESTINO,OPERADOR,TIPO_LLAMADA,CARGO_FINAL FROM USRAES.T21_T_TAG_1480_{$this->userIdentifier}"];

        
        $sqls[] = ['sql' => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.T21_TEMP_TAG_{$this->userIdentifier} PURGE';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_TEMP_TAG_{$this->userIdentifier}(
        NRO_FACTURA VARCHAR2(16),
        NRO_TEL_ORIGEN varchar2(18),
        FECHA varchar2(10),
        HORA_INICIO varchar2(11),
        HORA_FIN varchar2(11),
        PAIS varchar2(35),
        NRO_TEL_DESTINO varchar2(20),
        CONSUMO varchar2(18),
        TIPO_SERVICIO varchar2(12),
        DESTINO varchar2(16),
        OPERADOR varchar2(35),
        TIPO_LLAMADA varchar2(8),
        CARGO_FINAL number(18,2)  
        )"];

        $sqls[] = ['sql' => "BEGIN
            INSERT INTO USRAES.T21_TEMP_TAG_{$this->userIdentifier}
            SELECT * FROM USRAES.T21_T_TAG_1470_2_{$this->userIdentifier};
            COMMIT;

            INSERT INTO USRAES.T21_TEMP_TAG_{$this->userIdentifier}
            SELECT * FROM USRAES.T21_T_TAG_1460_1_{$this->userIdentifier};
            COMMIT;

            INSERT INTO USRAES.T21_TEMP_TAG_{$this->userIdentifier}
            SELECT * FROM USRAES.T21_T_TAG_1480_1_{$this->userIdentifier}; 
            COMMIT;
        END;"];
        
        $sqls[] = ['sql' => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.T21_T_TAG_DET_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_T_TAG_DET_{$this->userIdentifier} AS (SELECT rownum item,A.* FROM (
        SELECT B.CLIENTACCTNO CUENTA,B.CYCLE CICLO,A.* FROM USRAES.T21_TEMP_TAG_{$this->userIdentifier} A
        LEFT JOIN USRAES.T21_T_TAG_11_{$this->userIdentifier} B ON (A.NRO_FACTURA = B.INVOICENUMBER)
        order by to_date(fecha,'dd/mm/yyyy') asc) A)"];
        
        $sqls[] = ['sql' => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.T21_T_TAG_CONSO_1_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $extra_filer = "";
        if($fecha1 !== null && $fecha2 !== null){
            $str_fecha1 = $fecha1->format('d/m/Y');
            $str_fecha2 = $fecha2->format('d/m/Y');
            
            $extra_filer = "where to_date('{$str_fecha1}', 'dd/mm/yyyy') <= to_date(fecha, 'dd/mm/yyyy') and to_date(fecha, 'dd/mm/yyyy') <= to_date('{$str_fecha2}', 'dd/mm/yyyy')";
        }
        $sqls[] = ['sql' => "CREATE TABLE  USRAES.T21_T_TAG_CONSO_1_{$this->userIdentifier} AS
        SELECT
        CUENTA, NRO_TELEFONO, FACTURA, CICLO, CANTIDAD_GPRS, CANTIDAD_GPRS_MB, CANTIDAD_GPRS_GB, DURACION_ROAMING_DATOS, TOTAL_CANTIDAD, CANTIDAD_SMS, CANTIDAD_MMS,
        ".str_replace(":f", "DURACION_RPC", $sql_unidad_consumo).",
        ".str_replace(":f", "DURACION_ONNET", $sql_unidad_consumo).",
        ".str_replace(":f", "DURACION_ONNET_ADI", $sql_unidad_consumo).",
        ".str_replace(":f", "DURACION_OFFNETFIJO", $sql_unidad_consumo).",
        ".str_replace(":f", "DURACION_OFFNETMOVIL", $sql_unidad_consumo).",
        ".str_replace(":f", "DURACION_ROAMING_VOZ", $sql_unidad_consumo).",
        ".str_replace(":f", "DURACION_LDN", $sql_unidad_consumo).",
        ".str_replace(":f", "DURACION_LDI", $sql_unidad_consumo).",
        ".str_replace(":f", "DURACION_SIN_CARGO", $sql_unidad_consumo)."
        FROM (SELECT 
        G.CUENTA,
        g.NRO_TEL_ORIGEN NRO_TELEFONO,
        g.NRO_FACTURA FACTURA,
        g.CICLO,
        sum(case when UPPER(g.TIPO_SERVICIO)='DATOS' AND g.DESTINO <> 'ROAMING DATOS' then g.CONSUMO/(1024) else 0 end) CANTIDAD_GPRS,
        sum(case when UPPER(g.TIPO_SERVICIO)='DATOS' AND g.DESTINO <> 'ROAMING DATOS' then g.CONSUMO/(1024*1024) else 0 end) CANTIDAD_GPRS_MB,
        sum(case when UPPER(g.TIPO_SERVICIO)='DATOS' AND g.DESTINO <> 'ROAMING DATOS' then g.CONSUMO/(1024*1024*1024) else 0 end) CANTIDAD_GPRS_GB,
        sum(case when UPPER(g.TIPO_SERVICIO)='DATOS' AND g.DESTINO = 'ROAMING DATOS' then g.CONSUMO/(1024{$sql_unidad_trafico}) else 0 end) DURACION_ROAMING_DATOS,
        sum(case when UPPER(g.TIPO_SERVICIO)='DATOS' AND g.DESTINO <> 'ROAMING DATOS'  then g.CONSUMO/(1024{$sql_unidad_trafico}) else 0 end) + 
        sum(case when UPPER(g.TIPO_SERVICIO)='DATOS' AND g.DESTINO = 'ROAMING DATOS' then g.CONSUMO/(1024{$sql_unidad_trafico}) else 0 end) TOTAL_CANTIDAD,
        sum(case when g.TIPO_SERVICIO IN ('SMS Saliente','SMS') then g.CONSUMO else 0 end) CANTIDAD_SMS,
        0 CANTIDAD_MMS,
        sum(case when g.TIPO_SERVICIO= 'VOZ' and g.DESTINO = 'RPC' then g.CONSUMO else 0 end) DURACION_RPC,
        sum(case when g.TIPO_SERVICIO= 'VOZ' and g.DESTINO IN ('ON NET','OnNet') then g.CONSUMO else 0 end) DURACION_ONNET,0 DURACION_ONNET_ADI,
        sum(case when g.TIPO_SERVICIO= 'VOZ' and g.DESTINO = 'OffNet Fijo'  then g.CONSUMO else 0 end) DURACION_OFFNETFIJO,
        sum(case when g.TIPO_SERVICIO= 'VOZ' and g.DESTINO = 'OffNet Movil' then g.CONSUMO else 0 end) DURACION_OFFNETMOVIL,
        sum(case when g.TIPO_SERVICIO= 'VOZ' and g.DESTINO = 'ROAMING VOZ' then g.CONSUMO else 0 end) DURACION_ROAMING_VOZ,
        sum(case when g.TIPO_SERVICIO= 'VOZ' and g.DESTINO = 'LDN'      then g.CONSUMO else 0 end) DURACION_LDN,
        sum(case when g.TIPO_SERVICIO= 'VOZ' and g.DESTINO LIKE '%LDI%' then g.CONSUMO else 0 end) DURACION_LDI,
        sum(case when g.TIPO_SERVICIO= 'VOZ' and g.DESTINO LIKE '%SIN CARGO%' then g.CONSUMO else 0 end) DURACION_SIN_CARGO
        FROM (SELECT CUENTA,CICLO,NRO_FACTURA,NRO_TEL_ORIGEN,FECHA,HORA_INICIO,HORA_FIN,PAIS,NRO_TEL_DESTINO,TO_NUMBER(CONSUMO) CONSUMO,TIPO_SERVICIO,DESTINO,
        OPERADOR,TIPO_LLAMADA,CARGO_FINAL FROM USRAES.T21_T_TAG_DET_{$this->userIdentifier} {$extra_filer})g
        GROUP BY g.NRO_TEL_ORIGEN,g.NRO_FACTURA,g.CICLO,g.CUENTA)"];

        $this->exec_sql($sqls);

        $sqls = [];

        $sqls[] = ['sql' => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.T_LINEAS_FAC_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T_LINEAS_FAC_{$this->userIdentifier}(
            CUENTA VARCHAR2(50),
            PERIODO VARCHAR2(10),
            NRO_TELEFONO VARCHAR2(20)
        )"];

        $this->exec_sql($sqls);
        $sqls = [];

        foreach($periodos as $p){
            $all_lineas = DB::select(DB::raw("select
            distinct customer_account_desc as cuenta,
            SUBSTR(s.subscription_access_number, 3, 9) NRO_TELEFONO
            from DWA.DW_M_SUBSCRIPTION_HIST PARTITION(P_{$p}) s
            where
            s.customer_account_desc = :p_cliente
            AND s.subscription_status <> 'D'
            AND s.subscription_status <> 'S'"), ['p_cliente' => $cliente]);

            //dd($all_lineas);

            foreach($all_lineas as $row){
                DB::connection("oracle_dbtodb")
                ->table("USRAES.T_LINEAS_FAC_{$this->userIdentifier}")
                ->insert(array(
                    'cuenta' => $row->cuenta,
                    'periodo' => $p,
                    'nro_telefono' => $row->nro_telefono,
                ));
            }
        }


        $sqls[] = ['sql' => "DECLARE
            CURSOR CUR_PERIODOS IS
            SELECT FACTURA, MAX(CICLO) AS CICLO FROM USRAES.T21_T_TAG_CONSO_1_{$this->userIdentifier}
            GROUP BY FACTURA;
        
            V_PERIODO VARCHAR2(10);
            V_CUENTA VARCHAR2(50);
        BEGIN
            FOR V_ROW IN CUR_PERIODOS
            LOOP
                SELECT PERIODO, CLIENTACCTNO INTO V_PERIODO, V_CUENTA FROM TEMP_TAG_11 WHERE INVOICENUMBER = V_ROW.FACTURA;
        
                EXECUTE IMMEDIATE 'INSERT INTO USRAES.T21_T_TAG_CONSO_1_{$this->userIdentifier}(
                    CUENTA, NRO_TELEFONO, FACTURA, CICLO, CANTIDAD_GPRS, CANTIDAD_GPRS_MB, CANTIDAD_GPRS_GB, DURACION_ROAMING_DATOS, TOTAL_CANTIDAD, CANTIDAD_SMS, CANTIDAD_MMS, DURACION_RPC, DURACION_ONNET, DURACION_ONNET_ADI, DURACION_OFFNETFIJO, DURACION_OFFNETMOVIL, DURACION_ROAMING_VOZ, DURACION_LDN, DURACION_LDI, DURACION_SIN_CARGO
                )
                SELECT
                '''||V_CUENTA||''' AS CUENTA,
                A.NRO_TELEFONO AS NRO_TELEFONO,
                '''|| V_ROW.FACTURA ||''' AS FACTURA,
                '''|| V_ROW.CICLO ||''' AS CICLO,
                0 AS CANTIDAD_GPRS,
                0 as CANTIDAD_GPRS_MB,
                0 as CANTIDAD_GPRS_GB,
                0 AS DURACION_ROAMING_DATOS,
                0 AS TOTAL_CANTIDAD,
                0 AS CANTIDAD_SMS,
                0 AS CANTIDAD_MMS,
                ''$def_unidad_consumo'' AS DURACION_RPC,
                ''$def_unidad_consumo'' AS DURACION_ONNET,
                ''$def_unidad_consumo'' AS DURACION_ONNET_ADI,
                ''$def_unidad_consumo'' AS DURACION_OFFNETFIJO,
                ''$def_unidad_consumo'' AS DURACION_OFFNETMOVIL,
                ''$def_unidad_consumo'' AS DURACION_ROAMING_VOZ,
                ''$def_unidad_consumo'' AS DURACION_LDN,
                ''$def_unidad_consumo'' AS DURACION_LDI,
                ''$def_unidad_consumo'' AS DURACION_SIN_CARGO
                FROM (
                    select
                    *
                    from USRAES.T_LINEAS_FAC_{$this->userIdentifier}
                    where cuenta = '''|| V_CUENTA ||''' and periodo = '''|| V_PERIODO ||'''
                ) A
                LEFT JOIN (
                    SELECT DISTINCT NRO_TELEFONO FROM USRAES.T21_T_TAG_CONSO_1_{$this->userIdentifier}
                    WHERE CUENTA = '''|| V_CUENTA ||''' AND FACTURA = '''|| V_ROW.FACTURA ||'''
                ) B ON B.NRO_TELEFONO = A.NRO_TELEFONO
                WHERE B.NRO_TELEFONO IS NULL';
                COMMIT;
            END lOOP;
        END;"];

        $this->exec_sql($sqls);
    }

    public function getReporteConsolidado()
    {
        return DB::connection('oracle_dbtodb')
        ->table("USRAES.T21_T_TAG_CONSO_1_{$this->userIdentifier}")
        ->select("cuenta","nro_telefono","factura","ciclo","cantidad_gprs","cantidad_gprs_mb","cantidad_gprs_gb","duracion_roaming_datos","total_cantidad","cantidad_sms","cantidad_mms","duracion_rpc","duracion_onnet","duracion_onnet_adi","duracion_offnetfijo","duracion_offnetmovil","duracion_roaming_voz","duracion_ldn","duracion_ldi", "duracion_sin_cargo")->get()->toArray();
    }

    /*public function getReporteConsolidadoByFechas(DateTime $fecha1, DateTime $fecha2)
    {
        $str_fecha1 = $fecha1->format('d/m/Y');
        $str_fecha2 = $fecha2->format('d/m/Y');

        return DB::connection('oracle_dbtodb')
        ->table("USRAES.T21_T_TAG_CONSO_1_{$this->userIdentifier}")
        ->select("cuenta","nro_telefono","factura","ciclo","cantidad_gprs","duracion_roaming_datos","total_cantidad","cantidad_sms","cantidad_mms","duracion_rpc","duracion_onnet","duracion_onnet_adi","duracion_offnetfijo","duracion_offnetmovil","duracion_roaming_voz","duracion_ldn","duracion_ldi")
        ->whereRaw("to_date(?, 'dd/mm/yyyy') <= to_date(fecha, 'dd/mm/yyyy') and to_date(fecha, 'dd/mm/yyyy') <= to_date(?, 'dd/mm/yyyy')", [$str_fecha1, $str_fecha2])
        ->get()->toArray();
    }*/

    public function generateReporteDetallado(string $cliente, array $periodos)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();

        $binds = [];
        $str_binds_v = [];
        foreach($periodos as $i => $p){
            $binds['p_periodo_'.$i] = $p;
            $str_binds_v[] = ':p_periodo_'.$i;
        }
        $str_binds_v = implode(",", $str_binds_v);

        $sqls = [];
        $sqls[] = ['sql' => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.T21_T_TAG_11_{$this->userIdentifier} PURGE';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_T_TAG_11_{$this->userIdentifier} AS SELECT * FROM TEMP_TAG_11 WHERE 1=2"];
        $sqls[] = ['sql' => "BEGIN
            INSERT INTO USRAES.T21_T_TAG_11_{$this->userIdentifier}
            SELECT * FROM TEMP_TAG_11 WHERE CLIENTACCTNO in (:p_cod_cli) AND 
            PERIODO in({$str_binds_v});
            COMMIT;
        END;", 'params' => array_merge(['p_cod_cli' => $cliente], $binds)];

        $sqls[] = ['sql' => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.T21_T_TAG_1470_{$this->userIdentifier} PURGE';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_T_TAG_1470_{$this->userIdentifier} AS
        SELECT
        INVOICENUMBER NRO_FACTURA
        ,MSISDN NRO_TEL_ORIGEN
        ,TO_CHAR(CALLDATE,'DD/MM/YYYY') FECHA
        ,TO_CHAR(TO_DATE(SUBSTR('000000'||CALLTIME,-6),'HH24MISS'),'HH:MI:SS AM') HORA_INICIO
        ,CASE 
        WHEN CALLDESTINATION=1 THEN TO_CHAR(TO_DATE(SUBSTR('000000'||CALLTIME,-6),'HH24MISS') + (CASE 
        WHEN TRANSLATE(CALLDURATION,'+-0123456789',' ') IS NULL 
            THEN TO_NUMBER(CALLDURATION)
        WHEN TRANSLATE(CALLDURATION,'+-0123456789.',' ') IS NULL
            THEN REPLACE(CALLDURATION,'.',',')*60
        WHEN TRANSLATE(CALLDURATION,'0123456789:',' ') IS NULL
            THEN (SUBSTR(CALLDURATION,1,
            INSTR(CALLDURATION,':')-1)*60)+(SUBSTR(CALLDURATION,INSTR(CALLDURATION,':')+1))
        ELSE 0
        END)/86400,'HH:MI:SS AM')
        WHEN CALLDESTINATION=5 THEN null 
        END HORA_FIN 
        ,CALLORIGIN PAIS
        ,CALLNUMBER NRO_TEL_DESTINO
        ,CALLDURATION CONSUMO
        ,CALLDESTINATION DESTINO
        ,' ' OPERADOR
        ,'Saliente' TIPO_LLAMADA
        ,CALLTOTAL CARGO_FINAL
        FROM TEMP_TAG_1470 T1470 WHERE TRANSLATE(CALLTIME,'+-0123456789',' ') IS NULL
        AND INVOICENUMBER in (SELECT INVOICENUMBER FROM USRAES.T21_T_TAG_11_{$this->userIdentifier})"];

        $sqls[] = ['sql' => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.T21_T_TAG_1460_{$this->userIdentifier} PURGE';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_T_TAG_1460_{$this->userIdentifier} AS
        SELECT
        INVOICENUMBER NRO_FACTURA
        ,MSISDN NRO_TEL_ORIGEN
        ,TO_CHAR(CALLDATE,'DD/MM/YYYY') FECHA
        ,CASE WHEN SUBSTR(SUBSTR('000000'||CALLTIME,-6),3,2)>'59' OR SUBSTR(SUBSTR('000000'||CALLTIME,-6),1,2)>'23' 
        OR SUBSTR(SUBSTR('000000'||CALLTIME,-6),5,2)>'59'
        THEN TO_CHAR(TO_DATE(SUBSTR('000000'||SUBSTR(CALLTIME,1,5),-6),'HH24MISS'),'HH:MI:SS AM')
        ELSE TO_CHAR(TO_DATE(SUBSTR('000000'||CALLTIME,-6),'HH24MISS'),'HH:MI:SS AM')
        END HORA_INICIO
        ,TO_CHAR(CASE WHEN SUBSTR(SUBSTR('000000'||CALLTIME,-6),3,2)>'59' OR 
        SUBSTR(SUBSTR('000000'||CALLTIME,-6),1,2)>'23' OR SUBSTR(SUBSTR('000000'||CALLTIME,-6),5,2)>'59'
        THEN TO_DATE(SUBSTR('000000'||SUBSTR(CALLTIME,1,5),-6),'HH24MISS')
        ELSE TO_DATE(SUBSTR('000000'||CALLTIME,-6),'HH24MISS')
        END + (CASE 
        WHEN TRANSLATE(CALLDURATION,'+-0123456789',' ') IS NULL 
            THEN TO_NUMBER(CALLDURATION)
        WHEN TRANSLATE(CALLDURATION,'+-0123456789.',' ') IS NULL
            THEN REPLACE(CALLDURATION,'.',',')*60
        WHEN TRANSLATE(CALLDURATION,'0123456789:',' ') IS NULL
            THEN (SUBSTR(CALLDURATION,1,INSTR(CALLDURATION,':')-1)*60)+
            (SUBSTR(CALLDURATION,INSTR(CALLDURATION,':')+1))
        ELSE 0
        END)/86400,'HH:MI:SS AM') HORA_FIN
        ,CALLORIGIN PAIS
        ,(case when (substr(callnumber,1,2)='51' and substr(callnumber,-9,1)='9') then (substr(callnumber,-9))
            when (substr(callnumber,1,2)='51' and substr(callnumber,-9,1)='1') then (substr(callnumber,-8))
            else callnumber end) NRO_TEL_DESTINO
        ,CALLDURATION CONSUMO
        ,'Llamada Saliente' TIPO_SERVICIO
        ,CASE
        WHEN SUBSTR(TARIFFZONE,1,3)IN ('RPV','RTP') THEN 'RPC'
        WHEN SUBSTR(TARIFFZONE,1,3)IN ('NET','TDA') THEN 'OnNet'
        WHEN SUBSTR(TARIFFZONE,1,3)='FIJ' THEN 'OffNet Fijo'
        WHEN SUBSTR(TARIFFZONE,1,3)='MOV' THEN 'OffNet Movil'
        WHEN SUBSTR(TARIFFZONE,1,3)='LDN' THEN 'LDN'
        WHEN SUBSTR(TARIFFZONE,1,3)='LDI' THEN 'LDI'
        WHEN SUBSTR(TARIFFZONE,1,3)='GRA' THEN 'SIN CARGO'
        END DESTINO
        ,CALLDESTINATION OPERADOR
        ,'Saliente' TIPO_LLAMADA
        ,CALLTOTAL CARGO_FINAL
        from TEMP_TAG_1460 T1460
        WHERE INVOICENUMBER in (SELECT INVOICENUMBER FROM USRAES.T21_T_TAG_11_{$this->userIdentifier})"];

        $sqls[] = ['sql' => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.T21_T_TAG_1480_{$this->userIdentifier} PURGE';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_T_TAG_1480_{$this->userIdentifier} AS        
        SELECT
        INVOICENUMBER NRO_FACTURA
        ,MSISDN NRO_TEL_ORIGEN
        ,TO_CHAR(SMSDATE,'DD/MM/YYYY') FECHA
        ,CASE WHEN SUBSTR(SUBSTR('000000'||SMSTIME,-6),1,2)>'23'
        THEN TO_CHAR(TO_DATE(SUBSTR('000000'||SUBSTR(SMSTIME,1,5),-6),'HH24MISS'),'HH:MI:SS AM')
        ELSE TO_CHAR(TO_DATE(SUBSTR('000000'||SMSTIME,-6),'HH24MISS'),'HH:MI:SS AM')
        END HORA_INICIO
        ,' ' HORA_FIN
        ,SMSORIGIN PAIS
        ,SMSNUMBER NRO_TEL_DESTINO
        ,(SMSDURATION) CONSUMO
        ,CASE 
        WHEN TARIFFZONE NOT IN ('NET03','MAI01','DAT01') THEN 'SMS Saliente'
        WHEN TARIFFZONE IN ('NET03','MAI01') THEN 'MMS Saliente' 
        WHEN TARIFFZONE IN ('DAT01') THEN 'Datos'
        END TIPO_SERVICIO
        ,CASE 
        WHEN TARIFFZONE NOT IN ('NET03','MAI01','DAT01') THEN 'SMS'
        WHEN TARIFFZONE IN ('NET03','MAI01') THEN 'MMS'
        WHEN TARIFFZONE IN ('DAT01') THEN 'GPRS'
        END DESTINO
        ,SMSDESTINATION OPERADOR
        ,'Saliente' TIPO_LLAMADA
        ,SMSTOTAL CARGO_FINAL
        FROM TEMP_TAG_1480 T1480 
        WHERE INVOICENUMBER in (SELECT INVOICENUMBER FROM USRAES.T21_T_TAG_11_{$this->userIdentifier})"];

        $sqls[] = ['sql' => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.T21_T_TAG_1470_1_{$this->userIdentifier} PURGE';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_T_TAG_1470_1_{$this->userIdentifier} AS
        select NRO_FACTURA,NRO_TEL_ORIGEN,FECHA,HORA_INICIO,HORA_FIN,PAIS,NRO_TEL_DESTINO,CONSUMO,
        CASE WHEN HORA_FIN IS NOT NULL THEN 'VOZ'
            WHEN HORA_FIN IS NULL AND CONSUMO = '1' THEN 'SMS'
            ELSE 'DATOS' END TIPO_SERVICIO,OPERADOR,TIPO_LLAMADA,CARGO_FINAL FROM USRAES.T21_T_TAG_1470_{$this->userIdentifier}"];

        $sqls[] = ['sql' => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.T21_T_TAG_1470_2_{$this->userIdentifier} PURGE';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_T_TAG_1470_2_{$this->userIdentifier} AS
        select NRO_FACTURA,NRO_TEL_ORIGEN,FECHA,HORA_INICIO,HORA_FIN,PAIS,NRO_TEL_DESTINO,CONSUMO,TIPO_SERVICIO,
        CASE WHEN TIPO_SERVICIO  ='VOZ' THEN 'ROAMING VOZ'
        WHEN TIPO_SERVICIO  ='DATOS' THEN 'ROAMING DATOS'
            WHEN TIPO_SERVICIO = 'SMS' THEN 'ROAMING SMS'
        END DESTINO,OPERADOR,TIPO_LLAMADA,CARGO_FINAL FROM USRAES.T21_T_TAG_1470_1_{$this->userIdentifier}"];

        $sqls[] = ['sql' => "BEGIN
            UPDATE USRAES.T21_T_TAG_1460_{$this->userIdentifier}
            SET DESTINO = 'OnNet' 
            WHERE DESTINO IS NULL AND OPERADOR = 'CLA';
            COMMIT;
        END;"];

        $sqls[] = ['sql' => "BEGIN
        DELETE FROM USRAES.T21_T_TAG_1460_{$this->userIdentifier} WHERE NRO_TEL_DESTINO LIKE '800%';
        COMMIT;
        END;"];

        $sqls[] = ['sql' => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.T21_T_TAG_1460_1_{$this->userIdentifier} PURGE';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_T_TAG_1460_1_{$this->userIdentifier} AS
        SELECT NRO_FACTURA,NRO_TEL_ORIGEN,FECHA,HORA_INICIO,HORA_FIN,PAIS,NRO_TEL_DESTINO,CONSUMO,'VOZ' TIPO_SERVICIO,DESTINO,OPERADOR,TIPO_LLAMADA,CARGO_FINAL FROM  USRAES.T21_T_TAG_1460_{$this->userIdentifier}"];

        $sqls[] = ['sql' => "BEGIN
            UPDATE USRAES.T21_T_TAG_1480_{$this->userIdentifier}
            SET TIPO_SERVICIO = 'DATOS'
            WHERE TIPO_SERVICIO = 'Datos';
            COMMIT;
        END;"];

        $sqls[] = ['sql' => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.T21_T_TAG_1480_1_{$this->userIdentifier} PURGE';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_T_TAG_1480_1_{$this->userIdentifier} AS
        SELECT NRO_FACTURA,NRO_TEL_ORIGEN,FECHA,HORA_INICIO,HORA_FIN,PAIS,NRO_TEL_DESTINO,CONSUMO,TIPO_SERVICIO,
        CASE WHEN TIPO_SERVICIO='DATOS' THEN DESTINO
            WHEN TIPO_SERVICIO = 'SMS Saliente' THEN DESTINO 
            ELSE DESTINO END DESTINO,OPERADOR,TIPO_LLAMADA,CARGO_FINAL FROM USRAES.T21_T_TAG_1480_{$this->userIdentifier}"];

        $sqls[] = ['sql' => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.T21_TEMP_TAG_{$this->userIdentifier} PURGE';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_TEMP_TAG_{$this->userIdentifier}(
        NRO_FACTURA VARCHAR2(16),
        NRO_TEL_ORIGEN varchar2(18),
        FECHA varchar2(10),
        HORA_INICIO varchar2(11),
        HORA_FIN varchar2(11),
        PAIS varchar2(35),
        NRO_TEL_DESTINO varchar2(20),
        CONSUMO varchar2(18),
        TIPO_SERVICIO varchar2(12),
        DESTINO varchar2(16),
        OPERADOR varchar2(35),
        TIPO_LLAMADA varchar2(8),
        CARGO_FINAL number(18,2)  
        )"];

        $sqls[] = ['sql' => "BEGIN
            INSERT INTO USRAES.T21_TEMP_TAG_{$this->userIdentifier}
            SELECT * FROM USRAES.T21_T_TAG_1470_2_{$this->userIdentifier};
            COMMIT;
            
            INSERT INTO USRAES.T21_TEMP_TAG_{$this->userIdentifier}
            SELECT * FROM USRAES.T21_T_TAG_1460_1_{$this->userIdentifier};
            COMMIT;

            INSERT INTO USRAES.T21_TEMP_TAG_{$this->userIdentifier}
            SELECT * FROM USRAES.T21_T_TAG_1480_1_{$this->userIdentifier}; 
            COMMIT;
        END;"];

        $sqls[] = ['sql' => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.T21_T_TAG_DET_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_T_TAG_DET_{$this->userIdentifier} AS (SELECT rownum item,A.* FROM (
        SELECT B.CLIENTACCTNO CUENTA,B.CYCLE CICLO,A.* FROM USRAES.T21_TEMP_TAG_{$this->userIdentifier} A
        LEFT JOIN USRAES.T21_T_TAG_11_{$this->userIdentifier} B ON (A.NRO_FACTURA = B.INVOICENUMBER)
        order by to_date(fecha,'dd/mm/yyyy') asc) A)"];

        $sqls[] = ['sql' => "create index index1_T21_T_TAG_DET_{$this->userIdentifier} on USRAES.T21_T_TAG_DET_{$this->userIdentifier}(item)"];
        $sqls[] = ['sql' => "alter table USRAES.T21_T_TAG_DET_{$this->userIdentifier} add fecha_det date"];

        $sqls[] = ['sql' => "BEGIN
            UPDATE USRAES.T21_T_TAG_DET_{$this->userIdentifier} set fecha_det = to_date(fecha, 'dd/mm/yyyy');
            COMMIT;
        END;"];

        $this->exec_sql($sqls);
    }

    public function getPeriodosByFechas(string $cliente, DateTime $fecha1, DateTime $fecha2)
    {
        $data = DB::connection('oracle_dbtodb')->select(DB::raw("SELECT max(cycle) cycle FROM TEMP_TAG_11 WHERE CLIENTACCTNO in (?) and PERIODO = ?"), [$cliente, $fecha2->format("Ym")]);
        $ciclo = null;
        if(count($data)>0){
            $ciclo = $data[0]->cycle;
        }
        
        // buscar un periodo antes
        if($ciclo === null){
            $fecha_periodo = clone $fecha2;
            $fecha_periodo->modify("-1 month");
            $data = DB::connection('oracle_dbtodb')->select(DB::raw("SELECT max(cycle) cycle FROM TEMP_TAG_11 WHERE CLIENTACCTNO in (?) and PERIODO = ?"), [$cliente, $fecha_periodo->format("Ym")]);
            if(count($data)>0){
                $ciclo = $data[0]->cycle;
            }
        }

        // buscar un periodo despues
        if($ciclo === null){
            $fecha_periodo = clone $fecha2;
            $fecha_periodo->modify("+1 month");
            $data = DB::connection('oracle_dbtodb')->select(DB::raw("SELECT max(cycle) cycle FROM TEMP_TAG_11 WHERE CLIENTACCTNO in (?) and PERIODO = ?"), [$cliente, $fecha_periodo->format("Ym")]);
            if(count($data)>0){
                $ciclo = $data[0]->cycle;
            }
        }

        if ($ciclo === null) {
            throw new Exception("No se pudo obtener el ciclo para el cliente {$cliente}");
        }

        $fecha_ini = DateTime::createFromFormat("Y-m-d", $fecha1->format("Y-m")."-".$ciclo);
        $fecha_fin = (clone $fecha2);
        
	    $periodos = [];
	
        if($fecha1->format("Ymd") < $fecha_ini->format("Ymd")){
            $periodo = DateTime::createFromFormat("Y-m-d", $fecha1->format("Y-m")."-".$ciclo);
            $periodos[] = $periodo->format("Ym");
        }
        while ($fecha_ini->format("Ymd") <= $fecha_fin->format("Ymd")) {
            $periodo = (clone $fecha_ini)->modify("+1 month");
            //$periodo->modify("-1 day");
            $periodos[] = $periodo->format("Ym");
            $fecha_ini->modify("+1 month");
        }
        return $periodos;
    }

    public function getReporteDetallado(array $periodos, $offset = null, $limit = null, $options = [])
    {
        sort($periodos);
        $ciclos = \DB::connection('oracle_dbtodb')->table("USRAES.T21_T_TAG_DET_{$this->userIdentifier}")
        ->select('ciclo')
        ->whereNotNull('ciclo')
        ->groupBy('ciclo')->get();
        $ciclo = null;
        $extraWhere = [];
        if(array_key_exists("add_sin_cargo", $options)){
            if($options["add_sin_cargo"] === false){
                $extraWhere[] = "destino != 'SIN CARGO'";
            }
        }
        if(count($ciclos)>0){
            $ciclo = $ciclos[0]->ciclo;

            $data = [];
            /*foreach($periodos as $periodo){
                $dt = DateTime::createFromFormat('Ym', $periodo);
                $str_fecha2 = "{$ciclo}/".$dt->format('m/Y');
                $str_fecha1 = DateTime::createFromFormat('d/m/Y', $str_fecha2)->modify('-1 month')->format('d/m/Y');
        
                $data_to_add = DB::connection('oracle_dbtodb')
                    ->table('USRAES.T21_T_TAG_DET')
                    ->select("cuenta","ciclo","nro_factura","nro_tel_origen","fecha","hora_inicio","hora_fin","pais","nro_tel_destino","consumo","tipo_servicio","destino","operador","tipo_llamada","cargo_final")
                    ->whereRaw("to_date(?, 'dd/mm/yyyy') <= to_date(fecha, 'dd/mm/yyyy') and to_date(fecha, 'dd/mm/yyyy') < to_date(?, 'dd/mm/yyyy')", [$str_fecha1, $str_fecha2])
                    ->get()
                    ->toArray();
                $data = array_merge($data, $data_to_add);
            }*/
            $dt1 = DateTime::createFromFormat('Ym', $periodos[0]);
            $dt2 = DateTime::createFromFormat('Ym', $periodos[count($periodos)-1]);
            $str_fecha2 = "{$ciclo}/".$dt2->format('m/Y');
            $str_fecha1 = DateTime::createFromFormat('d/m/Y', "{$ciclo}/".$dt1->format('m/Y'))
            ->modify('-1 month')
            ->format('d/m/Y');

            $builder = DB::connection('oracle_dbtodb')
            ->table(function($sub) use ($str_fecha1, $str_fecha2, $extraWhere) {
                $sub->from("USRAES.T21_T_TAG_DET_{$this->userIdentifier}")
                ->select(DB::Raw("rownum as rnum"),"cuenta","ciclo","nro_factura","nro_tel_origen","fecha","hora_inicio","hora_fin","pais","nro_tel_destino","consumo","tipo_servicio","destino","operador","tipo_llamada","cargo_final")
                ->whereRaw("to_date(?, 'dd/mm/yyyy') <= to_date(fecha, 'dd/mm/yyyy') and to_date(fecha, 'dd/mm/yyyy') < to_date(?, 'dd/mm/yyyy')".(count($extraWhere) > 0 ? " and ":"").implode(" AND ", $extraWhere), [$str_fecha1, $str_fecha2]);
            }, 'a')
            ->select("cuenta","ciclo","nro_factura","nro_tel_origen","fecha","hora_inicio","hora_fin","pais","nro_tel_destino","consumo","tipo_servicio","destino","operador","tipo_llamada","cargo_final")
            ;

            if($offset !== null && $limit !== null){
                // $builder->limit($limit)->offset($offset);
                $builder->where("rnum", '>', $offset)->where("rnum", "<=", $offset+$limit);
            }

            $data = $builder->get()->toArray();
            return $data;
        }
        return [];
    }

    public function getReporteDetalladoByFechas(DateTime $fecha1, DateTime $fecha2, $offset = null, $limit = null, $options = [])
    {
        $str_fecha1 = $fecha1->format('d/m/Y');
        $str_fecha2 = $fecha2->format('d/m/Y');

        $extraWhere = [];
        if(($options["add_sin_cargo"]??false) === false){
            $extraWhere[] = "destino != 'SIN CARGO'";
        }

        $builder = DB::connection('oracle_dbtodb')
        ->table(function($sub) use ($str_fecha1, $str_fecha2, $extraWhere) {
            $sub->from("USRAES.T21_T_TAG_DET_{$this->userIdentifier}")
            ->select(DB::Raw("rownum as rnum"),"cuenta","ciclo","nro_factura","nro_tel_origen","fecha","hora_inicio","hora_fin","pais","nro_tel_destino","consumo","tipo_servicio","destino","operador","tipo_llamada","cargo_final")
            ->whereRaw("to_date(?, 'dd/mm/yyyy') <= to_date(fecha, 'dd/mm/yyyy') and to_date(fecha, 'dd/mm/yyyy') <= to_date(?, 'dd/mm/yyyy')".(count($extraWhere) > 0 ? " and ":"").join(" AND ", $extraWhere), [$str_fecha1, $str_fecha2]);
        }, 'a')
        ->select("cuenta","ciclo","nro_factura","nro_tel_origen","fecha","hora_inicio","hora_fin","pais","nro_tel_destino","consumo","tipo_servicio","destino","operador","tipo_llamada","cargo_final");

        if($offset !== null && $limit !== null){
            // $builder->limit($limit)->offset($offset);
            $builder->where("rnum", '>', $offset)->where("rnum", "<=", $offset+$limit);
        }

        $data = $builder->get()->toArray();
        return $data;
    }

    public function clienteExists(?string $cliente): bool
    {
        $result = DB::connection('oracle_dbtodb')
        ->table('TEMP_TAG_11')
        ->select(DB::Raw("count(*) as counter"))
        ->where('CLIENTACCTNO', $cliente)
        ->get();
        return $result[0]->counter > 0;
    }

    public function hasRecords(?string $cliente, ?string $periodo): bool
    {
        $params = ['p_cliente' => $cliente, 'p_periodo' => $periodo];
        $resp = DB::connection('oracle_dbtodb')->select(DB::Raw("SELECT COUNT(*) AS flag FROM (SELECT invoicenumber, clientacctno, cycle, periodo,accountname  ,fecregistro ,customerid,PERIODSTART,PERIODEND
        FROM TEMP_TAG_11  WHERE CLIENTACCTNO IN (:p_cliente) AND PERIODO=:p_periodo ORDER BY PERIODO DESC)A"), $params);
        return $resp[0]->flag > 0;
    }

    public function invoicenumberHasRecords(?string $cliente, ?string $periodo): bool
    {
        $invoicenumber = DB::connection('oracle_dbtodb')
        ->table("TEMP_TAG_11")
        ->where("CLIENTACCTNO", $cliente)
        ->where("periodo", $periodo)
        ->get();

        $val1 = false;
        $val2 = false;
        $val3 = false;

        if(count($invoicenumber) > 0){
            $invoicenumber = $invoicenumber[0]->invoicenumber;
            
            $resp_val1 = DB::connection('oracle_dbtodb')
            ->table("TEMP_TAG_1470")
            ->select(DB::raw("count(*) as v_counter"))
            ->whereRaw("TRANSLATE(CALLTIME,'+-0123456789',' ') IS NULL and invoicenumber in (?)", [$invoicenumber])
            ->get();

            $resp_val2 = DB::connection('oracle_dbtodb')
            ->table("TEMP_TAG_1460")
            ->select(DB::raw("count(*) as v_counter"))
            ->whereRaw("invoicenumber in (?)", [$invoicenumber])
            ->get();

            $resp_val3 = DB::connection('oracle_dbtodb')
            ->table("TEMP_TAG_1480")
            ->select(DB::raw("count(*) as v_counter"))
            ->whereRaw("invoicenumber in (?)", [$invoicenumber])
            ->get();

            $val1 = $resp_val1[0]->v_counter > 0;
            $val2 = $resp_val2[0]->v_counter > 0;
            $val3 = $resp_val3[0]->v_counter > 0;
        }
        
        return $val1 || $val2 || $val3;
    }

    public function findCliente(string $cliente)
    {
        $result = DB::connection('oracle_dbtodb')
        ->table('TEMP_TAG_11')
        ->select(DB::Raw("min(periodo) as min_periodo"), DB::Raw("max(periodo) as max_periodo"))
        ->where('CLIENTACCTNO', $cliente)
        ->get();
        
        if($result[0]->min_periodo !== null){
            return $result[0];
        }
        return null;
    }

    private function exec_sql(array $plsql)
    {
        foreach($plsql as $row){
            if(array_key_exists('params', $row)){
                DB::connection('oracle_dbtodb')->statement(DB::Raw($row['sql']), $row['params']);
            }else{
                DB::connection('oracle_dbtodb')->statement(DB::Raw($row['sql']));
            }
        }
    }

    public function getCustomerFullName(string $cliente, string $periodo){
        try {
            $result = DB::select(DB::Raw("select distinct customer_full_name from DWA.DW_M_SUBSCRIPTION_HIST PARTITION(P_{$periodo})
            where customer_account_desc LIKE :p_cliente"), ['p_cliente' => $cliente]);
            if(count($result) > 0){
                return $result[0]->customer_full_name;
            }
        } catch (\Throwable $th) {}
        return null;
    }
}
