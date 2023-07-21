<?php

namespace AMovil\Reports\MINEDU\RepConsumo\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\MINEDU\RepConsumo\Domain\ReporteConsumoRepository;
use DateTime;
use Illuminate\Support\Facades\DB;

class EloquentReporteConsumoRepository implements ReporteConsumoRepository
{
    private $authService;
    private $userIdentifier;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function loadPlanDatos(array $data)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        DB::statement(DB::raw("BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.MINEDU_LINEAS_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"));
        DB::statement(DB::raw("CREATE TABLE USRAES.MINEDU_LINEAS_{$this->userIdentifier}(
            Contratista VARCHAR2(100),
            SerialdelChip VARCHAR2(100),
            linea VARCHAR2(100),
            plan VARCHAR2(100),
            Tecnologia VARCHAR2(100),
            servicio VARCHAR2(100),
            periodo VARCHAR2(100),
            ConsumoTotal VARCHAR2(100),
            CostoPlan VARCHAR2(100)
        )"));
        foreach($data as $row){
            DB::table("USRAES.MINEDU_LINEAS_{$this->userIdentifier}")->insert($row);
        }
    }

    public function getByConsumoPLanDatos_Tipo(DateTime $fecha1, DateTime $fecha2, string $tipo_reporte)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        $str_fecha1 = $fecha1->format("Ymd");
        $str_fecha2 = $fecha2->format("Ymd");

        $queries = [];
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.MINEDU_TRAFICO_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.MINEDU_TRAFICO_{$this->userIdentifier}(
            USAGE_DATE_START                           DATE,
            USAGE_TIME_START                                   VARCHAR2(10),
            SERVED_NUMBER                              VARCHAR2(25),
            OTHER_NUMBER                                       VARCHAR2(35),
            REQUESTTYPE                                        VARCHAR2(100),
            REQUESTTYPEDESC                                    VARCHAR2(250),
            SERVICETYPEDESC                                    VARCHAR2(250),
            USAGE_DETAIL_USED                                  NUMBER(5),
            WALLETDESC                                         VARCHAR2(250),
            UNITMEASUREDESC                                    VARCHAR2(254),
            USAGE_UNITS                                        NUMBER,
            START_WALLET_BALANCE                               NUMBER,
            END_WALLET_BALANCE                                 NUMBER,
            CHAPP_AMT                                          NUMBER,
            RATINGZONEDESC                                     VARCHAR2(250),
            OTHER_OPERATOR                                     VARCHAR2(50),
            RATING_GROUP                                       VARCHAR2(50),
            SOURCE_SYSTEM                                      VARCHAR2(5),
            APNDESC                                            VARCHAR2(30)
        )"];
        $queries[] = ["sql" => "declare
            sto           varchar2(8000);
            v_partition   varchar2(100);
            v_msgerror    varchar2(200);
            v_d_fecha_ini date;
            v_d_fecha_fin date;
            v_nro_dias    integer;
            v_msisdn      varchar2(30);
            v_dia      varchar2(30);
        begin
            --INGRESAR LAS FECHAS INDICADAS
            v_d_fecha_ini := to_date('{$str_fecha1}', 'yyyymmdd'); --- Fecha inicio
            v_d_fecha_fin := to_date('{$str_fecha2}', 'yyyymmdd'); --- Fecha fin
            v_nro_dias := to_number(v_d_fecha_fin - v_d_fecha_ini) + 1; 
            begin
            for J in 1 .. v_nro_dias loop
                v_partition := 'p_' ||
                            to_char(v_d_fecha_fin - J + 1, 'YYYYMMDD');
                v_dia := to_char(v_d_fecha_fin - J + 1, 'YYYYMMDD');
            sto := 'insert into USRAES.MINEDU_TRAFICO_{$this->userIdentifier}
                select /*+ parallel(20) */n.*
                from DWO.DW_T_USAGE_POSTPAGO PARTITION (p_' ||v_dia|| ') n, (SELECT ''51''||LINEA as linea FROM USRAES.MINEDU_LINEAS_{$this->userIdentifier}) g
                where g.linea =n.served_number';
                execute immediate sto;
                commit;
            end loop;
            end;
        end; "];
        $queries[] = ["sql" => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.MINEDU_LINEAS2_{$this->userIdentifier}';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $queries[] = ["sql" => "CREATE TABLE USRAES.MINEDU_LINEAS2_{$this->userIdentifier} (
            Contratista VARCHAR2(100),
            SerialdelChip VARCHAR2(100),
            linea VARCHAR2(100),
            plan VARCHAR2(100),
            Tecnologia VARCHAR2(100),
            servicio VARCHAR2(100),
            periodo VARCHAR2(100),
            ConsumoTotal VARCHAR2(100),
            CostoPlan VARCHAR2(100)
        )"];

        switch ($tipo_reporte) {
            case '1':
                $queries[] = ["sql" => "BEGIN
                    INSERT INTO USRAES.MINEDU_LINEAS2_{$this->userIdentifier}(Contratista,SerialdelChip,linea,plan,Tecnologia,servicio,periodo,ConsumoTotal,CostoPlan)
                    SELECT BB.Contratista,BB.SerialdelChip,BB.linea,BB.plan,BB.Tecnologia,
                    BB.servicio,BB.periodo,AA.DATOS,BB.CostoPlan FROM USRAES.MINEDU_LINEAS_{$this->userIdentifier} BB
                    LEFT JOIN (
                        select substr(served_number,3,9) served_number, sum(usage_units)/1024/1024 DATOS from USRAES.MINEDU_TRAFICO_{$this->userIdentifier}
                        where walletdesc not in ('Paquete Datos M 12gb Corp','Paquete Datos M 6gb Corp') or walletdesc is null
                        group by served_number) AA
                    ON AA.served_number= BB.linea;
                    COMMIT;
                END;"];
                break;
            case '2':
                $queries[] = ["sql" => "BEGIN
                    INSERT INTO USRAES.MINEDU_LINEAS2_{$this->userIdentifier}(Contratista,SerialdelChip,linea,plan,Tecnologia,servicio,periodo,ConsumoTotal,CostoPlan)
                    SELECT BB.Contratista,BB.SerialdelChip,BB.linea,BB.plan,BB.Tecnologia,
                    BB.servicio,BB.periodo,AA.DATOS,BB.CostoPlan FROM USRAES.MINEDU_LINEAS_{$this->userIdentifier} BB
                    LEFT JOIN (
                        select substr(served_number,3,9) served_number, sum(usage_units)/1024/1024 DATOS from USRAES.MINEDU_TRAFICO_{$this->userIdentifier} 
                        where walletdesc in ('Paquete Datos M 12gb Corp','Paquete Datos M 6gb Corp') 
                        group by served_number) AA 
                    ON AA.served_number= BB.linea;
                    COMMIT;
                END;"];
                break;
            case '3':
                $queries[] = ["sql" => "BEGIN
                    INSERT INTO USRAES.MINEDU_LINEAS2_{$this->userIdentifier}(Contratista,SerialdelChip,linea,plan,Tecnologia,servicio,periodo,ConsumoTotal,CostoPlan)
                    SELECT BB.Contratista,BB.SerialdelChip,BB.linea,BB.plan,BB.Tecnologia,
                    BB.servicio,BB.periodo,AA.DATOS,BB.CostoPlan FROM USRAES.MINEDU_LINEAS_{$this->userIdentifier} BB
                    LEFT JOIN (
                    SELECT SERVED_NUMBER, SUM(DATOS) DATOS FROM (
                    select substr(served_number,3,9) served_number, sum(usage_units)/1024/1024 DATOS from USRAES.MINEDU_TRAFICO_{$this->userIdentifier} 
                    where walletdesc not in ('Paquete Datos M 12gb Corp','Paquete Datos M 6gb Corp') or walletdesc is null
                    group by served_number
                    union all
                    select substr(served_number,3,9) served_number, sum(usage_units)/1024/1024 DATOS from USRAES.MINEDU_TRAFICO_{$this->userIdentifier} 
                    where walletdesc in ('Paquete Datos M 12gb Corp','Paquete Datos M 6gb Corp') 
                    group by served_number
                    )
                    GROUP BY served_number) AA 
                    ON AA.served_number= BB.linea;
                    COMMIT;
                END;"];
                break;
            default:
                break;
        }

        $this->exec_sql($queries);

        return DB::table("USRAES.MINEDU_LINEAS2_{$this->userIdentifier}")->get();
    }

    private function exec_sql(array $plsql)
    {

        foreach($plsql as $row){
            if(array_key_exists('params', $row)){
                DB::statement(DB::Raw($row['sql']), $row['params']);
            }else{
                DB::statement(DB::Raw($row['sql']));
            }
        }
    }
}
