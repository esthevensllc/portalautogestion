<?php

namespace AMovil\Reports\RepFiscalia\Infrastructure\Repository;

use AMovil\Reports\RepFiscalia\Domain\ReporteFiscalRepository;
use DateTime;
use DB;

class EloquentReporteFiscalRepository implements ReporteFiscalRepository
{
    private $connnection = 'oracle_reptdm';
    private $cdr_dwh_table = '';
    private $cdr_tfi_table = '';
    private $tablespace = '';

    public function getReporteByMsisdn_Periodos(string $msisdn, DateTime $periodo1, DateTime $periodo2)
    {
        $dt = DateTime::createFromFormat("Y-m-d", "2021-01-01");
        $data = [];
        if(($periodo1 >= $dt && $periodo2 >= $dt) || ($periodo1 < $dt && $periodo2 < $dt)){
            $this->setConnection($periodo1, $periodo2);
            $this->generateReporteByMsisdn_Periodos($msisdn, $periodo1, $periodo2);
            $data = $this->getReporteData();
        }else{
            $this->setConnection($periodo1, $dt);
            $this->generateReporteByMsisdn_Periodos($msisdn, $periodo1, $dt);
            $data1 = $this->getReporteData();
            
            $dt->modify('+1 day');
            $this->setConnection($dt, $periodo2);
            $this->generateReporteByMsisdn_Periodos($msisdn, $dt, $periodo2);
            $data2 = $this->getReporteData();
            $data = array_merge($data1, $data2);
        }
        return $data;
    }

    private function generateReporteByMsisdn_Periodos(string $msisdn, DateTime $periodo1, DateTime $periodo2)
    {
        $int_msisdn = (int) $msisdn;
        $periodo1_f1 = $periodo1->format('Ymd');
        $periodo2_f1 = $periodo2->format('Ymd');
        $periodo1_f2 = $periodo1->format('Y/m/d');
        $periodo2_f2 = $periodo2->format('Y/m/d');

        $sql = "BEGIN
            BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.TEMP_FISCAL';
            EXCEPTION
                WHEN OTHERS THEN
                    IF SQLCODE != -942 THEN
                        RAISE;
                    END IF;
            END;
        END;";
        DB::connection($this->connnection)->statement(DB::Raw($sql));

        $sql = "create table USRAES.TEMP_FISCAL 
            {$this->tablespace} AS
            SELECT  CDR.RECORD_TYPE MODALIDAD,
            decode(cdr.record_type,'12','Llamada Saliente',
            '04','Llamada Saliente',
            '14','Llamada Entrante',
            '30','Llamada Entrante',
            '02','Llamada Entrante',
            '31','Llamada Saliente',
            '11','Llamada Entrante',
            '03','Llamada Saliente',
            '13','Llamada Saliente',
            '01','Llamada Saliente',
            '08','Mensaje Saliente',
            '09','Mensaje Entrante') TIPO_LLAMADA,
            CASE WHEN RECORD_TYPE IN ( '01', '03', '04', '08' ) THEN CDR.TIM_NUMBER 
                WHEN RECORD_TYPE IN ( '02', '09' ) THEN CDR.NUMBER_B  
                END NUMERO_ORIGEN,    
            CASE WHEN RECORD_TYPE IN ( '01', '03', '04', '08' ) THEN NVL(CDR.NUMBER_B,DIALLED_DIGITS) 
                WHEN RECORD_TYPE IN ( '02', '09' ) THEN CDR.TIM_NUMBER  
                END NUMERO_DESTINO, 
            TO_CHAR(TO_DATE(SUBSTR(CDR.CHARGING_START_TIME,1,8),'YYYYMMDD'),'DD/MM/YYYY') ||' '||SUBSTR(CHARGING_START_TIME,9,2)||':'||SUBSTR(CHARGING_START_TIME,11,2)||':'||SUBSTR(CHARGING_START_TIME,13,2) FECHA_HORA,
            TRUNC(CALL_DURATION/60) MINUTOS,ROUND(((CALL_DURATION/60)- TRUNC(CALL_DURATION/60))*60) SEGUNDOS,
            CDR.TIM_NUMBER_SUBS_FIRST_LAC AS LAC,
            CDR.TIM_NUMBER_SUBS_FIRTS_CI AS CELDA,
            CDR.TIM_NUMBER_IMEI IMEI_A,
            CDR.NUMBER_B_IMEI IMEI_B     
            FROM {$this->cdr_dwh_table} CDR  
            WHERE CDR.TIM_NUMBER =  '51'||'{$int_msisdn}' AND 
            CDR.CHARGING_START_TIME >= '{$periodo1_f1}'||'000000' 
            AND CDR.CHARGING_START_TIME <= '{$periodo2_f1}'||'235959' 
            and RECORD_TYPE IN ('04','03','02','01','08','09') 
            AND NVL(NVL(NUMBER_B,DIALLED_DIGITS),'NN')<>'NN'

            ----------------------------------------LLAMADAS SALIENTES ---------------------------
            UNION ALL SELECT DISTINCT ct.orientacion AS modalidad,
            'Llamada Saliente' TIPO_LLAMADA,
            '51'||CT.ANI as NUMERO_ORIGEN, --llamada saliente
            case when LENGTH(NVL(CT.DNI,'--'))>=9 and CT.plannumeraciondni='N' then 
            '51'||CT.DNI when LENGTH(NVL(CT.DNI,'--'))<=8 and CT.plannumeraciondni='N' then
            '51'||CT.DNI else CT.ANI end AS NUMERO_DESTINO, 
            TO_CHAR(CT.FECHAINICIO, 'DD/MM/YYYY HH24:MI:SS') FECHA_HORA,
            TRUNC(ct.DURACION / 60) MINUTOS,
            ROUND(((ct.DURACION / 60) - TRUNC(ct.duracion / 60)) * 60) SEGUNDOS,
            -------------------------------------------------------------
            CASE WHEN REGEXP_INSTR(UPPER(substr(substr(textocdr,instr(textocdr,'-3gpp=')+6,17),6,4)),'[^0123456789ABCDEF]')=0
            THEN TO_CHAR(to_number(substr(substr(textocdr,instr(textocdr,'-3gpp=')+6,17),6,4),'xxxx')) 
            WHEN REGEXP_INSTR(UPPER(CT.LAC),'[^0123456789ABCDEF]')=0 
            THEN TO_CHAR(TO_NUMBER(CT.LAC,'xxxx'))
            ELSE
            TO_CHAR(CT.LAC) END LAC,
            
            -------------------------------------------------------------
            CASE WHEN REGEXP_INSTR(UPPER(replace(substr(substr(textocdr,instr(textocdr,'-3gpp=')+6,16),-7),';ne','')),'[^0123456789ABCDEF]')=0
            THEN TO_CHAR(to_number(replace(substr(substr(textocdr,instr(textocdr,'-3gpp=')+6,16),-7),';ne',''),'xxxxxxx')) 
            WHEN REGEXP_INSTR(UPPER(CT.CELDA),'[^0123456789ABCDEF]')=0 
            THEN TO_CHAR(TO_NUMBER(CT.CELDA,'xxxxxxx'))
            ELSE 
            TO_CHAR(CT.CELDA) END CELDA,-- SE INCLUYE CAMBIO PARA EXTRAER CELL_ID DESDE EL CAMPO TEXTOCDR
            ' ' as IMEI_A,' ' as IMEI_B
            FROM {$this->cdr_tfi_table} CT  where -- PARA DATA ANTIGUA USAR LA DWM.CDR_TFI
            ct.ani='{$int_msisdn}' AND ct.fechafin >= TO_DATE('{$periodo1_f2}'||' 00:00:00','yyyy/mm/dd hh24:mi:ss') -- INPUT'S CAMBIAR FORMATO DE FECHA
            AND ct.fechafin <= TO_DATE('{$periodo2_f2}'||' 23:59:59', 'yyyy/mm/dd hh24:mi:ss') -- INPUT'S CAMBIAR FORMATO DE FECHA
            AND NVL(CT.ERROR,0)=0 and ct.orientacion='Originated'
            AND length(CT.dni)>=8 and (ct.OUT_TIPOTRAFICO='VoLTE' or ct.OUT_TIPOTRAFICO='VoWiFi') and idcentral=301 
            ------------------------------------LLAMADAS ENTRAMTES-------------------------------        
            UNION ALL SELECT /*+ PARALLEL (20)*/DISTINCT ORIENTACION MODALIDAD,
            'Llamada Entrante' TIPO_LLAMADA,--llamada entrantes
            '51'||CT.ANI as NUMERO_ORIGEN,
            case when LENGTH(NVL(CT.DNI,'--'))>=9 and CT.plannumeraciondni='N' then
            '51'||CT.DNI when LENGTH(NVL(CT.DNI,'--'))<=8 and CT.plannumeraciondni='N' then
            '51'||CT.DNI else CT.DNI end AS NUMERO_DESTINO, 
            TO_CHAR(CT.FECHAINICIO, 'DD/MM/YYYY HH24:MI:SS') FECHA_HORA,
            TRUNC(CT.DURACION / 60) MINUTOS,
            ROUND(((CT.DURACION / 60) - TRUNC(duracion / 60)) * 60) SEGUNDOS,
            -----------------------------------------------------------------
            CASE WHEN REGEXP_INSTR(UPPER(substr(substr(textocdr,instr(textocdr,'-3gpp=')+6,17),6,4)),'[^0123456789ABCDEF]')=0
            THEN TO_CHAR(to_number(substr(substr(textocdr,instr(textocdr,'-3gpp=')+6,17),6,4),'xxxx')) 
            WHEN REGEXP_INSTR(UPPER(CT.LAC),'[^0123456789ABCDEF]')=0 
            THEN TO_CHAR(TO_NUMBER(CT.LAC,'xxxx'))
            ELSE
            TO_CHAR(CT.LAC) END LAC,
            -----------------------------------------------------------------
            CASE WHEN REGEXP_INSTR(UPPER(replace(substr(substr(textocdr,instr(textocdr,'-3gpp=')+6,16),-7),';ne','')),'[^0123456789ABCDEF]')=0
            THEN TO_CHAR(to_number(replace(substr(substr(textocdr,instr(textocdr,'-3gpp=')+6,16),-7),';ne',''),'xxxxxxx')) 
            WHEN REGEXP_INSTR(UPPER(CT.CELDA),'[^0123456789ABCDEF]')=0 
            THEN TO_CHAR(TO_NUMBER(CT.CELDA,'xxxxxxx'))
            ELSE 
            TO_CHAR(CT.CELDA) END CELDA,-- SE INCLUYE CAMBIO PARA EXTRAER CELL_ID DESDE EL CAMPO TEXTOCDR
            ' ' as IMEI_A,' ' as IMEI_B
            FROM {$this->cdr_tfi_table} CT  where -- PARA DATA ANTIGUA USAR LA DWM.CDR_TFI
            ct.DNI='{$int_msisdn}' AND ct.fechafin >= TO_DATE('{$periodo1_f2}'||' 00:00:00','yyyy/mm/dd hh24:mi:ss') -- INPUT'S CAMBIAR FORMATO DE FECHA
            AND ct.fechafin <=  TO_DATE('{$periodo2_f2}'||' 23:59:59', 'yyyy/mm/dd hh24:mi:ss') -- INPUT'S CAMBIAR FORMATO DE FECHA
            AND NVL(CT.ERROR,0)=0 and ct.orientacion='Terminated'
            AND length(CT.dni)>=8 and (ct.OUT_TIPOTRAFICO='VoLTE' or ct.OUT_TIPOTRAFICO='VoWiFi') and idcentral=301
        ";
        DB::connection($this->connnection)->statement(DB::Raw($sql));

        if($this->connnection !== 'oracle_reptdm'){
            $data = DB::connection($this->connnection)->table('USRAES.TEMP_FISCAL')->get()->toJson();
            $data = json_decode($data, true);

            $sql = "BEGIN
                BEGIN
                    EXECUTE IMMEDIATE 'DROP TABLE USRAES.TEMP_FISCAL';
                EXCEPTION
                    WHEN OTHERS THEN
                        IF SQLCODE != -942 THEN
                            RAISE;
                        END IF;
                END;
            END;";
            DB::connection('oracle_reptdm')->statement(DB::Raw($sql));

            $sql = "CREATE TABLE USRAES.TEMP_FISCAL (
                modalidad varchar2(250),
                tipo_llamada varchar2(250),
                numero_origen varchar2(250),
                numero_destino varchar2(250),
                fecha_hora varchar2(250),
                minutos varchar2(250),
                segundos number,
                lac varchar2(250),
                celda varchar2(250),
                imei_a varchar2(250),
                imei_b varchar2(250)
            ) tablespace WORKAREA";
            DB::connection('oracle_reptdm')->statement(DB::Raw($sql));
            DB::connection('oracle_reptdm')->table('USRAES.TEMP_FISCAL')->insert($data);
            DB::connection('oracle_reptdm')->commit();
        }
    }

    private function getReporteData(){
        $data = DB::connection('oracle_reptdm')->select(DB::Raw("
            select  *  from 
            (select T.*, row_number() over(partition by tipo_llamada,numero_origen,numero_destino,fecha_hora order by ubicacion_a desc) RANK from 

            (select   MODALIDAD, 
            A.tipo_llamada,a.numero_origen,a.numero_destino,a.fecha_hora,a.minutos, a.segundos,a.lac, a.celda,b.direccion ubicacion_A,b.provincia,b.distrito,b.departamento,
            a.imei_a,a.imei_b --,' ' observaciones
            from USRAES.TEMP_FISCAL a 
            left join  dm.cdr_celdas_red b
            ON (a.celda=b.cell_id and a.LAC=b.lac))T
            ) r where rank=1
        "));
        return $data;
    }

    private function setConnection(DateTime $fecha1, DateTime $fecha2)
    {
        $dt = DateTime::createFromFormat("Y-m-d", "2021-01-01");
        if($fecha1 >= $dt && $fecha2 >= $dt){
            $this->connnection = 'oracle_reptdm';
            $this->cdr_dwh_table = 'DM.CDR_DWH';
            $this->cdr_tfi_table = 'DM.CDR_TFI';
            $this->tablespace = ' tablespace WORKAREA';
        }else{
            $this->connnection = 'oracle_dwhhis';
            $this->cdr_dwh_table = 'DWM.CDR_DWH';
            $this->cdr_tfi_table = 'DWM.CDR_TFI';
            $this->tablespace = '';
        }
    }

    public function msisdnExists($msisdn, DateTime $fechaIni, DateTime $fechaFin): bool
    {
        $this->setConnection($fechaIni, $fechaFin);

        $result = DB::connection($this->connnection)
        ->table($this->cdr_dwh_table)
        ->select('tim_number')
        ->where('tim_number', "51{$msisdn}")
        ->groupBy('tim_number')
        ->get();
        return count($result) > 0;
    }

    public function hasRecords(string $msisdn, DateTime $fechaIni, DateTime $fechaFin): bool
    {
        $this->setConnection($fechaIni, $fechaFin);

        $strFechaIni = $fechaIni->format("Ymd");
        $strFechaFin = $fechaFin->format("Ymd");

        $result = DB::connection($this->connnection)
        ->table($this->cdr_dwh_table)
        ->select(DB::Raw("count(*) as counter"))
        ->where('tim_number', "51{$msisdn}")
        ->where('charging_start_time', ">=", "{$strFechaIni}000000")
        ->where('charging_start_time', "<=", "{$strFechaFin}235959")
        ->get();
        return $result[0]->counter > 0;
    }
}
