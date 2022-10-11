<?php

namespace AMovil\Reports\RepDetConsumo\Infrastructure\Repository;

use DateTime;
use DB;

class LaravelDetalleConsumoRepository
{
    public function generateReporteConsolidado(string $cliente, array $periodos, string $unidad_trafico_id, string $unidad_consumo_id)
    {
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
            '1' => '',
            '2' => '/60'
        ];

        $sql_unidad_trafico = $unidad_trafico_by_id[$unidad_trafico_id];
        $sql_unidad_consumo = $unidad_consumo_by_id[$unidad_consumo_id];

        $sqls = [];
        $sqls[] = ['sql' => "DROP TABLE USRAES.T21_TEMP_TAG_11 PURGe"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_TEMP_TAG_11 AS SELECT * FROM TEMP_TAG_11 where 1=2"];
        $sqls[] = ['sql' => "BEGIN
            INSERT INTO USRAES.T21_TEMP_TAG_11
            SELECT * FROM TEMP_TAG_11 WHERE CLIENTACCTNO in (:p_cod_cli) AND 
            PERIODO in({$str_binds_v});
            COMMIT;
        END;", 'params' => array_merge(['p_cod_cli' => $cliente], $binds)];

        $sqls[] = ['sql' => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.T21_TEMP_TAG_1470 PURGE';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_TEMP_TAG_1470 AS
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
        AND INVOICENUMBER in (SELECT INVOICENUMBER FROM USRAES.T21_TEMP_TAG_11)"];

        $sqls[] = ['sql' => "DROP TABLE USRAES.T21_TEMP_TAG_1460 PURGE"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_TEMP_TAG_1460 AS
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
        END DESTINO
        ,CALLDESTINATION OPERADOR
        ,'Saliente' TIPO_LLAMADA
        ,CALLTOTAL CARGO_FINAL
        from TEMP_TAG_1460 T1460
        WHERE INVOICENUMBER in (SELECT INVOICENUMBER FROM USRAES.T21_TEMP_TAG_11)  and tariffzone not in 'GRA01'"];


        $sqls[] = ['sql' => "DROP TABLE USRAES.T21_TEMP_TAG_1480 PURGE"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_TEMP_TAG_1480 AS        
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
        WHERE INVOICENUMBER in (SELECT INVOICENUMBER FROM USRAES.T21_TEMP_TAG_11)"];
    
        $sqls[] = ['sql' => "DROP TABLE USRAES.T21_TEMP_TAG_1470_1 PURGE"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_TEMP_TAG_1470_1 AS
        select NRO_FACTURA,NRO_TEL_ORIGEN,FECHA,HORA_INICIO,HORA_FIN,PAIS,NRO_TEL_DESTINO,CONSUMO,
        CASE WHEN HORA_FIN IS NOT NULL THEN 'VOZ'
            WHEN HORA_FIN IS NULL AND CONSUMO = '1' THEN 'SMS'
            ELSE 'DATOS' END TIPO_SERVICIO,OPERADOR,TIPO_LLAMADA,CARGO_FINAL FROM T21_TEMP_TAG_1470"];

        $sqls[] = ['sql' => "DROP TABLE USRAES.T21_TEMP_TAG_1470_2 PURGE"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_TEMP_TAG_1470_2 AS
        select NRO_FACTURA,NRO_TEL_ORIGEN,FECHA,HORA_INICIO,HORA_FIN,PAIS,NRO_TEL_DESTINO,CONSUMO,TIPO_SERVICIO,
        CASE WHEN TIPO_SERVICIO  ='VOZ' THEN 'ROAMING VOZ'
        WHEN TIPO_SERVICIO  ='DATOS' THEN 'ROAMING DATOS'
            WHEN TIPO_SERVICIO = 'SMS' THEN 'ROAMING SMS'
        END DESTINO,OPERADOR,TIPO_LLAMADA,CARGO_FINAL FROM USRAES.T21_TEMP_TAG_1470_1"];

        $sqls[] = ['sql' => "BEGIN
            UPDATE USRAES.T21_TEMP_TAG_1460
            SET DESTINO = 'OnNet' 
            WHERE DESTINO IS NULL AND OPERADOR = 'CLA';
            COMMIT;

            DELETE FROM USRAES.T21_TEMP_TAG_1460 WHERE NRO_TEL_DESTINO LIKE '800%';
            COMMIT;
        END;"];

        $sqls[] = ['sql' => "DROP TABLE USRAES.T21_TEMP_TAG_1460_1 PURGE"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_TEMP_TAG_1460_1 AS
        SELECT NRO_FACTURA,NRO_TEL_ORIGEN,FECHA,HORA_INICIO,HORA_FIN,PAIS,NRO_TEL_DESTINO,CONSUMO,'VOZ' TIPO_SERVICIO,DESTINO,OPERADOR,TIPO_LLAMADA,CARGO_FINAL FROM  USRAES.T21_TEMP_TAG_1460"];

        $sqls[] = ['sql' => "BEGIN
            UPDATE USRAES.T21_TEMP_TAG_1480
            SET TIPO_SERVICIO = 'DATOS'
            WHERE TIPO_SERVICIO = 'Datos';
            COMMIT;
        END;"];

        $sqls[] = ['sql' => "DROP TABLE USRAES.T21_TEMP_TAG_1480_1 PURGE"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_TEMP_TAG_1480_1 AS
        SELECT NRO_FACTURA,NRO_TEL_ORIGEN,FECHA,HORA_INICIO,HORA_FIN,PAIS,NRO_TEL_DESTINO,CONSUMO,TIPO_SERVICIO,
        CASE WHEN TIPO_SERVICIO='DATOS' THEN DESTINO
            WHEN TIPO_SERVICIO = 'SMS Saliente' THEN DESTINO 
            ELSE DESTINO END DESTINO,OPERADOR,TIPO_LLAMADA,CARGO_FINAL FROM USRAES.T21_TEMP_TAG_1480"];


        $sqls[] = ['sql' => "DROP TABLE USRAES.T21_TEMP_TAG PURGE"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_TEMP_TAG(
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
            INSERT INTO USRAES.T21_TEMP_TAG
            SELECT * FROM USRAES.T21_TEMP_TAG_1470_2;
            COMMIT;

            INSERT INTO USRAES.T21_TEMP_TAG
            SELECT * FROM USRAES.T21_TEMP_TAG_1460_1;
            COMMIT;

            INSERT INTO USRAES.T21_TEMP_TAG
            SELECT * FROM USRAES.T21_TEMP_TAG_1480_1; 
            COMMIT;
        END;"];

        $sqls[] = ['sql' => "DROP TABLE USRAES.T21_TEMP_TAG_DETALLADO"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_TEMP_TAG_DETALLADO AS (SELECT rownum item,A.* FROM (
        SELECT B.CLIENTACCTNO CUENTA,B.CYCLE CICLO,A.* FROM USRAES.T21_TEMP_TAG A
        LEFT JOIN USRAES.T21_TEMP_TAG_11 B ON (A.NRO_FACTURA = B.INVOICENUMBER)
        order by to_date(fecha,'dd/mm/yyyy') asc) A)"];
        
        $sqls[] = ['sql' => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.T21_TEMP_TAG_CONSOLIDADO_1';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $sqls[] = ['sql' => "CREATE TABLE  USRAES.T21_TEMP_TAG_CONSOLIDADO_1 AS
        SELECT 
        G.CUENTA,
        g.NRO_TEL_ORIGEN NRO_TELEFONO,
        g.NRO_FACTURA FACTURA,
        g.CICLO,
        sum(case when UPPER(g.TIPO_SERVICIO)='DATOS' AND g.DESTINO <> 'ROAMING DATOS' then g.CONSUMO/(1024{$sql_unidad_trafico}) else 0 end) CANTIDAD_GPRS,
        sum(case when UPPER(g.TIPO_SERVICIO)='DATOS' AND g.DESTINO = 'ROAMING DATOS' then g.CONSUMO/(1024{$sql_unidad_trafico}) else 0 end) DURACION_ROAMING_DATOS,
        sum(case when UPPER(g.TIPO_SERVICIO)='DATOS' AND g.DESTINO <> 'ROAMING DATOS'  then g.CONSUMO/(1024{$sql_unidad_trafico}) else 0 end) + 
        sum(case when UPPER(g.TIPO_SERVICIO)='DATOS' AND g.DESTINO = 'ROAMING DATOS' then g.CONSUMO/(1024{$sql_unidad_trafico}) else 0 end) TOTAL_CANTIDAD,
        sum(case when g.TIPO_SERVICIO IN ('SMS Saliente','SMS') then g.CONSUMO else 0 end) CANTIDAD_SMS,
        0 CANTIDAD_MMS,
        sum(case when g.TIPO_SERVICIO= 'VOZ' and g.DESTINO = 'RPC' then g.CONSUMO{$sql_unidad_consumo} else 0 end) DURACION_RPC,
        sum(case when g.TIPO_SERVICIO= 'VOZ' and g.DESTINO IN ('ON NET','OnNet') then g.CONSUMO{$sql_unidad_consumo} else 0 end) DURACION_ONNET,0 DURACION_ONNET_ADI,
        sum(case when g.TIPO_SERVICIO= 'VOZ' and g.DESTINO = 'OffNet Fijo'  then g.CONSUMO{$sql_unidad_consumo} else 0 end) DURACION_OFFNETFIJO,
        sum(case when g.TIPO_SERVICIO= 'VOZ' and g.DESTINO = 'OffNet Movil' then g.CONSUMO{$sql_unidad_consumo} else 0 end) DURACION_OFFNETMOVIL,
        sum(case when g.TIPO_SERVICIO= 'VOZ' and g.DESTINO = 'ROAMING VOZ' then g.CONSUMO{$sql_unidad_consumo} else 0 end) DURACION_ROAMING_VOZ,
        sum(case when g.TIPO_SERVICIO= 'VOZ' and g.DESTINO = 'LDN'      then g.CONSUMO{$sql_unidad_consumo} else 0 end) DURACION_LDN,
        sum(case when g.TIPO_SERVICIO= 'VOZ' and g.DESTINO LIKE '%LDI%' then g.CONSUMO{$sql_unidad_consumo} else 0 end) DURACION_LDI
        FROM (SELECT CUENTA,CICLO,NRO_FACTURA,NRO_TEL_ORIGEN,FECHA,HORA_INICIO,HORA_FIN,PAIS,NRO_TEL_DESTINO,TO_NUMBER(CONSUMO) CONSUMO,TIPO_SERVICIO,DESTINO,
        OPERADOR,TIPO_LLAMADA,CARGO_FINAL FROM USRAES.T21_TEMP_TAG_DETALLADO)g
        GROUP BY g.NRO_TEL_ORIGEN,g.NRO_FACTURA,g.CICLO,g.CUENTA"];

        $this->exec_sql($sqls);
    }

    public function getReporteConsolidado()
    {
        return DB::connection('oracle_dbtodb')
        ->table('USRAES.T21_TEMP_TAG_CONSOLIDADO_1')
        ->select("cuenta","nro_telefono","factura","ciclo","cantidad_gprs","duracion_roaming_datos","total_cantidad","cantidad_sms","cantidad_mms","duracion_rpc","duracion_onnet","duracion_onnet_adi","duracion_offnetfijo","duracion_offnetmovil","duracion_roaming_voz","duracion_ldn","duracion_ldi")->get()->toArray();
    }

    public function generateReporteDetallado(string $cliente, array $periodos)
    {
        $binds = [];
        $str_binds_v = [];
        foreach($periodos as $i => $p){
            $binds['p_periodo_'.$i] = $p;
            $str_binds_v[] = ':p_periodo_'.$i;
        }
        $str_binds_v = implode(",", $str_binds_v);

        $sqls = [];
        $sqls[] = ['sql' => "DROP TABLE USRAES.T21_TEMP_TAG_11 PURGe"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_TEMP_TAG_11 AS SELECT * FROM TEMP_TAG_11 WHERE 1=2"];
        $sqls[] = ['sql' => "BEGIN
            INSERT INTO USRAES.T21_TEMP_TAG_11
            SELECT * FROM TEMP_TAG_11 WHERE CLIENTACCTNO in (:p_cod_cli) AND 
            PERIODO in({$str_binds_v});
            COMMIT;
        END;", 'params' => array_merge(['p_cod_cli' => $cliente], $binds)];

        $sqls[] = ['sql' => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.T21_TEMP_TAG_1470 PURGE';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_TEMP_TAG_1470 AS
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
        AND INVOICENUMBER in (SELECT INVOICENUMBER FROM USRAES.T21_TEMP_TAG_11)"];

        $sqls[] = ['sql' => "DROP TABLE USRAES.T21_TEMP_TAG_1460 PURGE"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_TEMP_TAG_1460 AS
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
        END DESTINO
        ,CALLDESTINATION OPERADOR
        ,'Saliente' TIPO_LLAMADA
        ,CALLTOTAL CARGO_FINAL
        from TEMP_TAG_1460 T1460
        WHERE INVOICENUMBER in (SELECT INVOICENUMBER FROM USRAES.T21_TEMP_TAG_11)  and tariffzone not in 'GRA01'"];

        $sqls[] = ['sql' => "DROP TABLE USRAES.T21_TEMP_TAG_1480 PURGE"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_TEMP_TAG_1480 AS        
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
        WHERE INVOICENUMBER in (SELECT INVOICENUMBER FROM USRAES.T21_TEMP_TAG_11)"];

        $sqls[] = ['sql' => "DROP TABLE USRAES.T21_TEMP_TAG_1470_1 PURGE"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_TEMP_TAG_1470_1 AS
        select NRO_FACTURA,NRO_TEL_ORIGEN,FECHA,HORA_INICIO,HORA_FIN,PAIS,NRO_TEL_DESTINO,CONSUMO,
        CASE WHEN HORA_FIN IS NOT NULL THEN 'VOZ'
            WHEN HORA_FIN IS NULL AND CONSUMO = '1' THEN 'SMS'
            ELSE 'DATOS' END TIPO_SERVICIO,OPERADOR,TIPO_LLAMADA,CARGO_FINAL FROM T21_TEMP_TAG_1470"];

        $sqls[] = ['sql' => "DROP TABLE USRAES.T21_TEMP_TAG_1470_2 PURGE"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_TEMP_TAG_1470_2 AS
        select NRO_FACTURA,NRO_TEL_ORIGEN,FECHA,HORA_INICIO,HORA_FIN,PAIS,NRO_TEL_DESTINO,CONSUMO,TIPO_SERVICIO,
        CASE WHEN TIPO_SERVICIO  ='VOZ' THEN 'ROAMING VOZ'
        WHEN TIPO_SERVICIO  ='DATOS' THEN 'ROAMING DATOS'
            WHEN TIPO_SERVICIO = 'SMS' THEN 'ROAMING SMS'
        END DESTINO,OPERADOR,TIPO_LLAMADA,CARGO_FINAL FROM USRAES.T21_TEMP_TAG_1470_1"];

        $sqls[] = ['sql' => "BEGIN
            UPDATE USRAES.T21_TEMP_TAG_1460
            SET DESTINO = 'OnNet' 
            WHERE DESTINO IS NULL AND OPERADOR = 'CLA';
            COMMIT;
        END;"];

        $sqls[] = ['sql' => "BEGIN
        DELETE FROM USRAES.T21_TEMP_TAG_1460 WHERE NRO_TEL_DESTINO LIKE '800%';
        COMMIT;
        END;"];
        $sqls[] = ['sql' => "DROP TABLE USRAES.T21_TEMP_TAG_1460_1 PURGE"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_TEMP_TAG_1460_1 AS
        SELECT NRO_FACTURA,NRO_TEL_ORIGEN,FECHA,HORA_INICIO,HORA_FIN,PAIS,NRO_TEL_DESTINO,CONSUMO,'VOZ' TIPO_SERVICIO,DESTINO,OPERADOR,TIPO_LLAMADA,CARGO_FINAL FROM  USRAES.T21_TEMP_TAG_1460"];

        $sqls[] = ['sql' => "BEGIN
            UPDATE USRAES.T21_TEMP_TAG_1480
            SET TIPO_SERVICIO = 'DATOS'
            WHERE TIPO_SERVICIO = 'Datos';
            COMMIT;
        END;"];

        $sqls[] = ['sql' => "DROP TABLE USRAES.T21_TEMP_TAG_1480_1 PURGE"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_TEMP_TAG_1480_1 AS
        SELECT NRO_FACTURA,NRO_TEL_ORIGEN,FECHA,HORA_INICIO,HORA_FIN,PAIS,NRO_TEL_DESTINO,CONSUMO,TIPO_SERVICIO,
        CASE WHEN TIPO_SERVICIO='DATOS' THEN DESTINO
            WHEN TIPO_SERVICIO = 'SMS Saliente' THEN DESTINO 
            ELSE DESTINO END DESTINO,OPERADOR,TIPO_LLAMADA,CARGO_FINAL FROM USRAES.T21_TEMP_TAG_1480"];

        $sqls[] = ['sql' => "DROP TABLE USRAES.T21_TEMP_TAG PURGE"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_TEMP_TAG(
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
            INSERT INTO USRAES.T21_TEMP_TAG
            SELECT * FROM USRAES.T21_TEMP_TAG_1470_2;
            COMMIT;
            
            INSERT INTO USRAES.T21_TEMP_TAG
            SELECT * FROM USRAES.T21_TEMP_TAG_1460_1;
            COMMIT;

            INSERT INTO USRAES.T21_TEMP_TAG
            SELECT * FROM USRAES.T21_TEMP_TAG_1480_1; 
            COMMIT;
        END;"];

        $sqls[] = ['sql' => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.T21_TEMP_TAG_DETALLADO';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $sqls[] = ['sql' => "CREATE TABLE USRAES.T21_TEMP_TAG_DETALLADO AS (SELECT rownum item,A.* FROM (
        SELECT B.CLIENTACCTNO CUENTA,B.CYCLE CICLO,A.* FROM USRAES.T21_TEMP_TAG A
        LEFT JOIN USRAES.T21_TEMP_TAG_11 B ON (A.NRO_FACTURA = B.INVOICENUMBER)
        order by to_date(fecha,'dd/mm/yyyy') asc) A)"];

        $this->exec_sql($sqls);
    }

    public function getReporteDetallado(array $periodos, $offset = null, $limit = null)
    {
        sort($periodos);
        $ciclos = \DB::connection('oracle_dbtodb')->table('USRAES.T21_TEMP_TAG_DETALLADO')->select('ciclo')->groupBy('ciclo')->get();
        $ciclo = null;
        if(count($ciclos)>0){
            $ciclo = $ciclos[0]->ciclo;

            $data = [];
            /*foreach($periodos as $periodo){
                $dt = DateTime::createFromFormat('Ym', $periodo);
                $str_fecha2 = "{$ciclo}/".$dt->format('m/Y');
                $str_fecha1 = DateTime::createFromFormat('d/m/Y', $str_fecha2)->modify('-1 month')->format('d/m/Y');
        
                $data_to_add = DB::connection('oracle_dbtodb')
                    ->table('USRAES.T21_TEMP_TAG_DETALLADO')
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
            ->table(function($sub) use ($str_fecha1, $str_fecha2) {
                $sub->from('USRAES.T21_TEMP_TAG_DETALLADO')
                ->select(DB::Raw("rownum as rnum"),"cuenta","ciclo","nro_factura","nro_tel_origen","fecha","hora_inicio","hora_fin","pais","nro_tel_destino","consumo","tipo_servicio","destino","operador","tipo_llamada","cargo_final")
                ->whereRaw("to_date(?, 'dd/mm/yyyy') <= to_date(fecha, 'dd/mm/yyyy') and to_date(fecha, 'dd/mm/yyyy') < to_date(?, 'dd/mm/yyyy')", [$str_fecha1, $str_fecha2]);
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
