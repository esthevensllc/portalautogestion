<?php

namespace AMovil\Reports\RepCursado\Infrastructure\Repository;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\RepCursado\Domain\ReporteCursadoRepository;
use DateTime;
use Illuminate\Support\Facades\DB;

class LaravelReporteCursadoRepository implements ReporteCursadoRepository
{
    private $authService;
    private $connection;
    private $schema;
    private $tablespace;
    private $userIdentifier;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    private function setUserIdentifier(){
        $this->userIdentifier = $this->authService->getUserIdentifier();
    }

    private function setConnection(DateTime $periodo)
    {
        $this->connection = 'oracle';
        $this->tablespace = '';
        /*if(DateTime::createFromFormat('Ym', '202101') <= $periodo){
            $this->connection = 'oracle_reptdm';
            $this->schema = 'DM';
            $this->tablespace = 'tablespace WORKAREA';
        } else {
            $this->connection = 'oracle_dwhhis';
            $this->schema = 'DWM';
            $this->tablespace = '';
        }*/
    }

    private function load_lineas_table($data,$conexion)
    {   
        // DB::connection($this->connection)->statement(DB::raw());
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.TMP_PULL_LINES_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.TMP_PULL_LINES_{$this->userIdentifier}(LINEA VARCHAR2(20), CYCLE VARCHAR2(20), IDX NUMBER) {$this->tablespace}"];
        
        $this->exec_sql($queries,$conexion);

        foreach($data as $index => $row){
            $data[$index]['idx'] = $index+1;
        }

        DB::connection($conexion)->table("USRAES.TMP_PULL_LINES_{$this->userIdentifier}")->insert($data);        
    }

    private function getLineasFrom(DateTime $fecha, $field, $value){
        $lineas = DB::select(DB::raw("
            SELECT DISTINCT SUBSCRIPTION_ACCESS_NUMBER LINEA,customer_account_billing_cycle_sc CYCLE FROM (
            SELECT DISTINCT customer_FULL_name CLIENTE,
             SUBSCRIPTION_ACCESS_NUMBER,
            customer_account_billing_cycle_sc,
             SUBSCRIPTION_STATUS ESTADO 
            FROM DWA.DW_M_SUBSCRIPTION_HIST PARTITION (P_".$fecha->format('Ym').") s
            WHERE s.{$field} = :p_value
            AND s.subscription_status <> 'D')"), ['p_value' => $value]);
        $lineas = json_decode(json_encode($lineas), true);
        return $lineas;
    }

    public function getReporteByNumCuenta($num_cuenta, DateTime $fecha1, DateTime $fecha2)
    {
        $this->setUserIdentifier();
        $this->setConnection($fecha2);
        $lineas = $this->getLineasFrom($fecha2, 'customer_account_desc', $num_cuenta);
        $this->load_lineas_table($lineas,'oracle');
        $this->load_lineas_table($lineas,'oracle_reptdm');

        return $this->getReporte($fecha1, $fecha2);
    }

    public function getReporteByCodCliente($cod_cliente, DateTime $fecha1, DateTime $fecha2)
    {
        $this->setUserIdentifier();
        $this->setConnection($fecha2);
        $lineas = $this->getLineasFrom($fecha2,'customer_account_high_sc', $cod_cliente);
        $this->load_lineas_table($lineas,'oracle');
        $this->load_lineas_table($lineas,'oracle_reptdm');

        return $this->getReporte($fecha1, $fecha2);
    }

    public function getReporteByNumDocumento($num_documento, DateTime $fecha1, DateTime $fecha2)
    {
        $this->setUserIdentifier();
        $this->setConnection($fecha2);
        $lineas = $this->getLineasFrom($fecha2, 'id_card_value', $num_documento);
        $this->load_lineas_table($lineas,'oracle');
        $this->load_lineas_table($lineas,'oracle_reptdm');

        return $this->getReporte($fecha1, $fecha2);
    }

    public function getReporteByLineas(array $lineas, DateTime $fecha1, DateTime $fecha2)
    {
        $this->setUserIdentifier();
        $this->setConnection($fecha2);
        $this->load_lineas_table($lineas,'oracle');
        $this->load_lineas_table($lineas,'oracle_reptdm');

        return $this->getReporte($fecha1, $fecha2);
    }

    public function truncateConsolidado()
    {
        $this->setUserIdentifier();
        $queries = [];
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.T_REPORTE_VISANET10_C_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];

        $queries[] = ["sql" => "CREATE TABLE USRAES.T_REPORTE_VISANET10_C_{$this->userIdentifier} {$this->tablespace} (
            PERIODO CHAR(6),
            STR_PERIODO CHAR(25),
            LINEA VARCHAR2(100),
            SERVICIO CHAR(100),
            CONSUMO_BYTES NUMBER
        )"];

        $conexion = 'oracle';
        $this->exec_sql($queries,$conexion);
    }

    public function getConsolidado()
    {
        return DB::table("USRAES.T_REPORTE_VISANET10_C_{$this->userIdentifier}")->get();
    }

    private function getReporte(DateTime $fecha1, DateTime $fecha2)
    {
        $now = new DateTime();
        $str_fecha1 = $fecha1->format('d/m/Y');
        $str_fecha2 = $fecha2->format('d/m/Y');

        $before_now = $now->modify('-2 days');

        $sum_traf_mb = 0;

        while($fecha2->format('Ymd') >= $before_now->format('Ymd')){

            $conexion = 'oracle_reptdm';

            $queries = [];

            $queries[] = ["sql" => "BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.REPORTE_GPRS_LINEAS_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                    IF SQLCODE != -942 THEN
                        RAISE;
                    END IF;
            END;"];

            $queries[] = ["sql" => "CREATE TABLE USRAES.REPORTE_GPRS_LINEAS_{$this->userIdentifier} AS 
                SELECT /*+parallel(4)*/ TO_DATE(TO_CHAR(s_rec_opening_time,'YYYY-MM-DD'),'YYYY-MM-DD') as FECHA,
                served_msisdn AS MSISDN,
                round(sum(s_uplink+s_downlink),2) as traf_b 
                from DM.CDR_GPRS PARTITION(P_{$before_now->format('Ymd')}) 
                where served_msisdn in (select LINEA from USRAES.TMP_PULL_LINES_{$this->userIdentifier}) 
                group by TO_DATE(TO_CHAR(s_rec_opening_time,'YYYY-MM-DD'),'YYYY-MM-DD'),served_msisdn"
            ];

            $this->exec_sql($queries,$conexion);

            $result = DB::connection($conexion)->table("USRAES.REPORTE_GPRS_LINEAS_{$this->userIdentifier}")->get();

            $sum_traf_mb = $result->sum('traf_mb') + $sum_traf_mb;

            $before_now = $before_now->modify('+1 days');
        }

        $conexion = 'oracle';

        $queries = [];

        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.T_MB_CONSUMIDOS10_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];

        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.T_MB_CONSUMIDOS10_{$this->userIdentifier}_parts';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.T_MB_CONSUMIDOS10_{$this->userIdentifier}_parts(str_partition varchar2(20)) {$this->tablespace}"];

        $queries[] = ["sql" => "CREATE TABLE USRAES.T_MB_CONSUMIDOS10_{$this->userIdentifier} {$this->tablespace} (
            FECHA VARCHAR2(8),
            LINEA VARCHAR2(50),
            S_UPLINK NUMBER,
            S_DOWNLINK NUMBER,
            CONSUMO_TOTAL NUMBER
        )"];

        $queries[] = ["sql" => "DECLARE
            sto varchar2(8000);
            v_partition   varchar2(100);
            v_msgerror varchar2(200);
            v_d_fecha_ini date;
            v_d_fecha_fin date;
            v_nro_dias integer;
            v_msisdn varchar2(30);
        BEGIN
        v_d_fecha_ini:=to_date('{$str_fecha1}','dd/mm/yyyy'); -- COLOCAR FECHA INICIO
        v_d_fecha_fin:=to_date('{$str_fecha2}','dd/mm/yyyy'); -- COLOCAR FECHA FIN DEL CICLO DE FACTURACION.
    
        v_nro_dias:=to_number(v_d_fecha_fin-v_d_fecha_ini)+1;
    
        BEGIN
            FOR J IN 1 .. v_nro_dias LOOP
            v_partition :=to_char(v_d_fecha_fin - J +1,'YYYYMMDD');

            insert into USRAES.T_MB_CONSUMIDOS10_{$this->userIdentifier}_parts(str_partition) values (v_partition);
            commit;
            
                sto:= 'INSERT INTO USRAES.T_MB_CONSUMIDOS10_{$this->userIdentifier}
                SELECT
                TO_CHAR(A.CARRIED_START_DATE,''YYYYMMDD'') FECHA,                       
                A.ACCESS_NUMBER LINEA,
                sum(NVL(A.DATA_BYTE_UP,0)) S_UPLINK,
                sum(NVL(A.DATA_BYTE_DW,0)) S_DOWNLINK,
                SUM(NVL(A.DATA_BYTE_UP,0))+SUM(NVL(A.DATA_BYTE_DW,0)) CONSUMO_TOTAL             
                FROM DWA.AYF_A_D_TRAF_CURS_DATOS_SAP PARTITION(P_'||v_partition||') A          
                WHERE A.ACCESS_NUMBER IN (SELECT LINEA FROM USRAES.TMP_PULL_LINES_{$this->userIdentifier})
                GROUP BY A.ACCESS_NUMBER,TO_CHAR(A.CARRIED_START_DATE,''YYYYMMDD'')';
                execute immediate sto; 

            END LOOP;
        END;    
        END;"];

        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.T_REPORTE_VISANET10_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.T_REPORTE_VISANET10_{$this->userIdentifier} {$this->tablespace} AS
        SELECT '".$fecha1->format('Ym')."' periodo,'{$str_fecha1} - {$str_fecha2}' STR_PERIODO, A.LINEA LINEA,'DATOS MOVIL' SERVICIO,NVL(B.CONSUMO_BYTES,0) + {$sum_traf_mb} CONSUMO_BYTES 
        FROM USRAES.TMP_PULL_LINES_{$this->userIdentifier} A
        LEFT JOIN (
        SELECT LINEA,SUM(CONSUMO_TOTAL) CONSUMO_BYTES FROM USRAES.T_MB_CONSUMIDOS10_{$this->userIdentifier}
        GROUP BY LINEA
        ) B ON (A.LINEA = B.LINEA)"];

        $queries[] = ["sql" => "BEGIN
            INSERT INTO USRAES.T_REPORTE_VISANET10_C_{$this->userIdentifier}(PERIODO, STR_PERIODO, LINEA, SERVICIO, CONSUMO_BYTES)
            SELECT PERIODO, STR_PERIODO, LINEA, SERVICIO, CONSUMO_BYTES FROM USRAES.T_REPORTE_VISANET10_{$this->userIdentifier}
            COMMIT;
        END;"];

        $this->exec_sql($queries,$conexion);
        
        return DB::connection($conexion)->table("USRAES.T_REPORTE_VISANET10_{$this->userIdentifier}")->get();
    }

    private function exec_sql($queries,$conexion){
        foreach($queries as $row){
            DB::connection($conexion)->statement(DB::raw($row['sql']));
        }
    }

    public function getCicloByNumCuenta($num_cuenta, DateTime $fecha1, DateTime $fecha2)
    {
        $this->setUserIdentifier();
        $this->setConnection($fecha2);
        $lineas = $this->getLineasFrom($fecha2, 'customer_account_desc', $num_cuenta);
        if(count($lineas)>0){
            return $lineas[0]['cycle'];
        }
        return null;
    }

    public function getCicloByCodCliente($cod_cliente, DateTime $fecha1, DateTime $fecha2)
    {
        $this->setUserIdentifier();
        $this->setConnection($fecha2);
        $lineas = $this->getLineasFrom($fecha2,'customer_account_high_sc', $cod_cliente);
        if(count($lineas)>0){
            return $lineas[0]['cycle'];
        }
        return null;
    }

    public function getCicloByNumDocumento($num_documento, DateTime $fecha1, DateTime $fecha2)
    {
        $this->setUserIdentifier();
        $this->setConnection($fecha2);
        $lineas = $this->getLineasFrom($fecha2,'id_card_value', $num_documento);
        if(count($lineas)>0){
            return $lineas[0]['cycle'];
        }
        return null;
    }

    public function getPeriodosDisponibles()
    {
        $result = DB::select(DB::raw("SELECT replace(MAX(PARTITION_name), 'P_') max_date, replace(MIN(PARTITION_name), 'P_') min_date
        FROM All_tab_partitions WHERE table_name = 'AYF_A_D_TRAF_CURS_DATOS_SAP' and segment_created like 'YES'"));
        return $result[0];
    }
}
