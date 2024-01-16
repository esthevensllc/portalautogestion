<?php

namespace AMovil\Reports\RepDetLlamadas\Infrastructure\Repository;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\RepDetLlamadas\Domain\DetalleLlamadasRepository;
use AMovil\Reports\RepDetLlamadas\Domain\ReporteDetalleLlamada;
use AMovil\Reports\RepDetLlamadas\Domain\TipoReporte;
use DateTime;
use DB;
use Ramsey\Uuid\Uuid;

class EloquentDetalleLlamadasRepository implements DetalleLlamadasRepository
{
    private $connection = '';
    private $authService;
    private $userIdentifier;
    private $reporte_by;
    private $is_primarios = false;
    private $limit = ReporteDetalleLlamada::LIMIT;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    private function setConnection(DateTime $periodo)
    {
        if(DateTime::createFromFormat('Ym', '202101')->format("Ym") <= $periodo->format("Ym")){
            $this->connection = 'oracle_reptdm';
        } else {
            $this->connection = 'oracle_dwhhis';
        }
        $this->userIdentifier = $this->authService->getUserIdentifier();
    }

    public function getReporteByPeriodo_CodCliente_tipo(DateTime $periodo1, DateTime $periodo2, array $cod_cliente, string $tipo_reporte): ReporteDetalleLlamada
    {
        $this->setConnection($periodo2);

        //$str_periodo = $periodo2->format("Ym");
        //str_pad($cod_cliente, 16);
        foreach ($cod_cliente as $key => $value) {
            $cod_cliente[$key] = str_pad($value, 8, "0", STR_PAD_LEFT);
        }
        $this->reporte_by = "cod_cliente";
        $this->generateLineasTableBy($periodo1, 'customer_account_high_sc', $cod_cliente);
        $str_periodo_1 = $periodo1->format("Ymd")."000000";
        $str_periodo_2 = $periodo2->format("Ymd")."235959";

        //$records_type = $this->getRecordTypeByTipoReporte($tipo_reporte);

        $this->generateReporte($periodo1, $periodo2, $tipo_reporte);
        //return $this->reporteBuilder()->get()->toArray();
        return $this->getReporteLlamadas();

        /*return $this->reporteBuilder()
        ->where('charging_start_time', '>=', $str_periodo_1)
        ->where('charging_start_time', '<=', $str_periodo_2)
        ->whereIn('record_type', $records_type)
        ->where('call_duration', '>', 0)
        ->where(DB::Raw("nvl(nvl(number_b,dialled_digits),'NN')"), '<>', 'NN')
        ->orderBy('charging_start_time')
        ->get()
        ->toArray();*/
    }

    private function getReporteLlamadas(): ReporteDetalleLlamada {
        $offset = 0;
        $chunks = [];
        $dataCount = 1;
        while ($dataCount > 0) {
            $data = $this->reporteBuilder($this->limit, $offset);
            $offset += $this->limit;
            $dataCount = count($data);
            $filepath = $this->getTempfilename();
            $f = fopen($filepath, "w");
            fwrite($f, json_encode($data));
            fclose($f);
            if($dataCount > 0){
                $chunks[] = [
                    "file" => $filepath
                ];
            }
        }
        return new ReporteDetalleLlamada($chunks);
    }

    private function getTempfilename(){
        $filename = sys_get_temp_dir() ."/phpexport-". Uuid::uuid4()->toString().".json";
        return $filename;
    }

    public function getReporteByPeriodo_NumDocumento_tipo(DateTime $periodo1, DateTime $periodo2, array $num_documento, string $tipo_reporte): ReporteDetalleLlamada
    {
        $this->setConnection($periodo2);

        //$str_periodo = $periodo2->format("Ym");
        $this->generateLineasTableBy($periodo1, 'id_card_value', $num_documento);
        $str_periodo_1 = $periodo1->format("Ymd")."000000";
        $str_periodo_2 = $periodo2->format("Ymd")."235959";

        //$records_type = $this->getRecordTypeByTipoReporte($tipo_reporte);

        $this->generateReporte($periodo1, $periodo2, $tipo_reporte);
        //return $this->reporteBuilder()->get()->toArray();
        return $this->getReporteLlamadas();

        /*return $this->reporteBuilder()
        ->where('charging_start_time', '>=', $str_periodo_1)
        ->where('charging_start_time', '<=', $str_periodo_2)
        ->whereIn('record_type', $records_type)
        ->where('call_duration', '>', 0)
        ->where(DB::Raw("nvl(nvl(number_b,dialled_digits),'NN')"), '<>', 'NN')
        ->orderBy('charging_start_time')
        ->get()
        ->toArray();*/
    }

    public function getReporteByPeriodo_NumCuenta_tipo(DateTime $periodo1, DateTime $periodo2, array $num_cuenta, string $tipo_reporte): ReporteDetalleLlamada
    {
        $this->setConnection($periodo2);

        //$str_periodo = $periodo2->format("Ym");
        $this->generateLineasTableBy($periodo1, 'customer_account_desc', $num_cuenta);
        $str_periodo_1 = $periodo1->format("Ymd")."000000";
        $str_periodo_2 = $periodo2->format("Ymd")."235959";

        //$records_type = $this->getRecordTypeByTipoReporte($tipo_reporte);

        $this->generateReporte($periodo1, $periodo2, $tipo_reporte);
        //return $this->reporteBuilder()->get()->toArray();
        // return $this->reporteBuilder();
        return $this->getReporteLlamadas();

        /*return $this->reporteBuilder()
        ->where('charging_start_time', '>=', $str_periodo_1)
        ->where('charging_start_time', '<=', $str_periodo_2)
        ->whereIn('record_type', $records_type)
        ->where('call_duration', '>', 0)
        ->where(DB::Raw("nvl(nvl(number_b,dialled_digits),'NN')"), '<>', 'NN')
        ->orderBy('charging_start_time')
        ->get()
        ->toArray();*/
    }

    public function getReporteByPeriodo_Lineas_tipo(DateTime $periodo1, DateTime $periodo2, array $lineas, string $tipo_reporte, $is_primarios = false): ReporteDetalleLlamada
    {
        $this->setConnection($periodo2);

        $this->is_primarios = $is_primarios;

        $this->reloadLineasTable($lineas);
        $str_periodo_1 = $periodo1->format("Ymd")."000000";
        $str_periodo_2 = $periodo2->format("Ymd")."235959";

        //$records_type = $this->getRecordTypeByTipoReporte($tipo_reporte);

        $this->generateReporte($periodo1, $periodo2, $tipo_reporte);
        //return $this->reporteBuilder()->get()->toArray();
        // return $this->reporteBuilder();
        return $this->getReporteLlamadas();

        /*return $this->reporteBuilder()
        ->where('charging_start_time', '>=', $str_periodo_1)
        ->where('charging_start_time', '<=', $str_periodo_2)
        ->whereIn('record_type', $records_type)
        ->where('call_duration', '>', 0)
        ->where(DB::Raw("nvl(nvl(number_b,dialled_digits),'NN')"), '<>', 'NN')
        ->orderBy('charging_start_time')
        ->get()
        ->toArray();*/
    }

    public function saveLogReporteTemp(string $filename, DateTime $createAt, int $size_bytes)
    {
        DB::table("USRAES.REP_LLAMADAS_ARCHIVO")->where("filename", $filename)->delete();
        DB::table("USRAES.REP_LLAMADAS_ARCHIVO")->insert([
            "filename" => $filename,
            "created_at" => $createAt->format("Y-m-d H:i:s"),
            "size_bytes" => $size_bytes,
        ]);
    }

    public function getLogReporteTemp()
    {
        return DB::table("USRAES.REP_LLAMADAS_ARCHIVO")->get();
    }

    public function deleteLogReporteTemp()
    {
        return DB::table("USRAES.REP_LLAMADAS_ARCHIVO")->delete();
    }

    private function generateLineasTableBy(DateTime $fecha2, string $field, array $values)
    {
        $periodo = $fecha2->format("Ym");
        $str_fecha_fin = $fecha2->format("Ymd");
        $binds = [];
        $sql_binds = [];
        foreach($values as $i => $v){
            $binds["p_{$i}"] = $v;
            $sql_binds[] = ":p_{$i}";
        }
        $sql_binds = implode(",", $sql_binds);

        $sql1 = ['sql' => "BEGIN
            BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE usraes.lineas_tmp1_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                    IF SQLCODE != -942 THEN
                        RAISE;
                    END IF;
            END;

            EXECUTE IMMEDIATE 'CREATE TABLE usraes.lineas_tmp1_{$this->userIdentifier}(subscription_access_number varchar2(250))';
        END;"];
        DB::statement(DB::Raw($sql1['sql']));

        $sql2 = ["sql" => "BEGIN
            INSERT INTO usraes.lineas_tmp1_{$this->userIdentifier}(subscription_access_number)
            SELECT /*+PARALLEL(4)*/ subscription_access_number  FROM (
            select
            distinct s.customer_account_sc codigo_cliente,s.customer_FULL_name CLIENTE, s.id_card_value NRO_DOC,
                    s.id_card_type_value DOC,s.subscription_status Estado, s.agreement_product_offering_desc PRODUCTO,
                    S.customer_account_desc,S.customer_account_sc,s.agreement_contract_number, SUBSTR(s.subscription_access_number, 3, 9) MSISDN, s.subscription_access_number,
                    ROUND(S.agreement_flat_charge * 1.18, 2) CARGO_FIJO, s.subscription_start_date F_INICIO,
                    s.subscription_end_date F_FIN, s.subscription_source_system_desc, 
                    s.agreement_flat_charge, agreement_mode, S.AGREEMENT_REASON_STATUS_DESC, S.subscription_source_system_desc FUENTE,
                    s.customer_email, S.CUSTOMER_ACCOUNT_DESC, s.customer_account_billing_cycle_sc
            from DWA.DW_M_SUBSCRIPTION_HIST PARTITION(P_{$periodo}) s
            where
            s.{$field} in ($sql_binds)
            --AND s.subscription_status <> 'D'
            AND (s.subscription_status <> 'D' OR s.AGREEMENT_END_DATE> to_date('{$str_fecha_fin}','YYYYMMDD'))
            --AND s.subscription_status <> 'S'
            ORDER BY F_FIN desc);
            COMMIT;
        END;", 'params' => $binds];
        DB::statement(DB::Raw($sql2['sql']), $binds);

        $data = DB::table("usraes.lineas_tmp1_{$this->userIdentifier}")->get()->toArray();
        for ($i=0; $i < count($data); $i++) { 
            $data[$i] = $data[$i]->subscription_access_number;
        }
        $this->reloadLineasTable($data);
    }

    private function reloadLineasTable(array $lineas){
        $tablespace = '';
        if($this->connection === 'oracle_reptdm'){
            $tablespace = ' tablespace WORKAREA';
        }
        $sql = ['sql' => "BEGIN
            BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE usraes.lineas_tmp1_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                    IF SQLCODE != -942 THEN
                        RAISE;
                    END IF;
            END;

            EXECUTE IMMEDIATE 'CREATE TABLE usraes.lineas_tmp1_{$this->userIdentifier}(subscription_access_number varchar2(250)){$tablespace}';
        END;"];
        $this->exec_sql([$sql]);

        $data = [];
        for ($i=0; $i < count($lineas); $i++) { 
            $data[$i] = ['subscription_access_number' => $lineas[$i]];
        }
        DB::connection($this->connection)->table("usraes.lineas_tmp1_{$this->userIdentifier}")->insert($data);
        DB::connection($this->connection)->commit();
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

    private function reporteBuilder($limit, $offset){
        /*$schema = 'dwm';
        if($this->connection === 'oracle_reptdm'){
            $schema = 'dm';
        }
        return DB::connection($this->connection)
        ->table("{$schema}.cdr_dwh")
        ->select(
            DB::Raw("tim_number as numero_origen"),
            DB::Raw("to_char(to_date(substr(charging_start_time,1,8),'yyyymmdd'),'dd/mm/yyyy') as fecha"),
            DB::Raw("substr(charging_start_time,9,2)||':'||substr(charging_start_time,11,2)||':'||substr(charging_start_time,13,2) as hora_inicio"),
            DB::Raw("substr(charging_end_time,9,2)||':'||substr(charging_end_time,11,2)||':'||substr(charging_end_time,13,2) as hora_fin"),
            DB::Raw("nvl(number_b,dialled_digits) as numero_destino"),
            DB::Raw("decode(sign(trunc(call_duration/60)-10),-1,'0'||trunc(call_duration/60),trunc(call_duration/60))||':'||decode(sign(mod(call_duration,60)-10),-1,'0'||mod(call_duration,60),mod(call_duration,60)) as consumo"),
            DB::Raw("decode(record_type,'12','Llamada Saliente','04','LLamada Saliente','14','Llamada Entrante',
            '30','Llamada Entrante','31','Llamada Saliente','11','Llamada Entrante',
            '03','Llamada Saliente','08','Mensaje Saliente','13','Llamada Saliente','01','Llamada Saliente',
            '09','Mensaje Entrante') as tipo"),
        )
        ->whereIn('tim_number', function($query){
            $query->select('subscription_access_number')->from("usraes.lineas_tmp1_{$this->userIdentifier}");
        });*/
        //return DB::connection($this->connection)->table("USRAES.TEMP_REPORTE_LLAMADAS_{$this->userIdentifier}");
        
        /*return DB::connection($this->connection)->select(DB::raw("SELECT NUMERO_ORIGEN,FECHA,MIN(HORA_INICIO) as HORA_INICIO,HORA_FIN,NUMERO_DESTINO,MAX(CONSUMO) as CONSUMO,TIPO
        FROM (
            SELECT * FROM (
                SELECT NUMERO_ORIGEN,FECHA,MIN(HORA_INICIO) AS HORA_INICIO,HORA_FIN,NUMERO_DESTINO,CONSUMO,TIPO
                FROM
                (
                    SELECT NUMERO_ORIGEN,FECHA,HORA_INICIO,MAX(HORA_FIN) AS HORA_FIN,NUMERO_DESTINO,CONSUMO,TIPO
                    FROM (
                        SELECT NUMERO_ORIGEN,FECHA,HORA_INICIO,HORA_FIN,NUMERO_DESTINO,MAX(CONSUMO) AS CONSUMO,TIPO
                        FROM (
                            select distinct numero_origen, fecha, hora_inicio, hora_fin, numero_destino, consumo, tipo from (
                            select * from USRAES.TEMP_REPORTE_LLAMADAS1_{$this->userIdentifier}
                            union all
                            select * from USRAES.TEMP_REPORTE_LLAMADAS2_{$this->userIdentifier})
                        )
                        GROUP BY NUMERO_ORIGEN,FECHA,HORA_INICIO,HORA_FIN,NUMERO_DESTINO,TIPO
                    )
                    GROUP BY NUMERO_ORIGEN,FECHA,HORA_INICIO,NUMERO_DESTINO,CONSUMO,TIPO
                )
                GROUP BY NUMERO_ORIGEN,FECHA,HORA_FIN,NUMERO_DESTINO,CONSUMO,TIPO
            )WHERE (HORA_INICIO=HORA_FIN AND CONSUMO='00:00') OR (HORA_INICIO<>HORA_FIN AND CONSUMO<>'00:00')
        )
        GROUP BY NUMERO_ORIGEN,FECHA,HORA_FIN,NUMERO_DESTINO,TIPO"));*/
        return DB::connection($this->connection)
        ->select(DB::raw("SELECT NUMERO_ORIGEN,FECHA,HORA_INICIO,HORA_FIN,NUMERO_DESTINO,CONSUMO,TIPO FROM USRAES.T_REP_LLA2_{$this->userIdentifier}
        OFFSET {$offset} ROWS FETCH NEXT {$limit} ROWS ONLY"));
    }

    private function generateReporte(DateTime $periodo1, DateTime $periodo2, $tipo_reporte)
    {
        $schema = 'dwm';
        $tablespace = '';
        $volte_filter = "";
        if($this->connection === 'oracle_reptdm'){
            $schema = 'dm';
            $tablespace = ' tablespace WORKAREA';
            $volte_filter = " and TECNOLOGIA like '3GPP-E-UTRAN%'";
        }

        $records_type = $this->getRecordTypeByTipoReporte($tipo_reporte);

        $str_records_type = "'".implode("','", $records_type)."'";

        $str_periodo_1 = $periodo1->format("Ymd")."000000";
        $str_periodo_2 = $periodo2->format("Ymd")."235959";

        $queries = [];

        $queries[] = ['sql' => "BEGIN
            BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE USRAES.T_REP_LLA2_{$this->userIdentifier}';
            EXCEPTION
                WHEN OTHERS THEN
                    IF SQLCODE != -942 THEN
                        RAISE;
                    END IF;
            END;
        END;"];

        $queries[] = ['sql' => "CREATE TABLE USRAES.T_REP_LLA2_{$this->userIdentifier} (
            NUMERO_ORIGEN  VARCHAR2(100),
            FECHA          VARCHAR2(10),
            HORA_INICIO    VARCHAR2(8),
            HORA_FIN       VARCHAR2(8),
            NUMERO_DESTINO VARCHAR2(100),
            CONSUMO        VARCHAR2(100),
            TIPO           VARCHAR2(100)
        ) $tablespace"];

        $periodo = clone $periodo1;

        /*$cod_cli_entrantes = "SELECT DNI NUMERO_ORIGEN,
        TO_CHAR(FECHAINICIO,'DD/MM/YYYY') FECHA,
        TO_CHAR(FECHAINICIO,'HH24:MI:SS') HORA_INICIO,
        TO_CHAR(FECHAFIN,'HH24:MI:SS') HORA_FIN,
        ANICENTRAL NUMERO_DESTINO,
        DECODE(SIGN(TRUNC(DURACION/60)-10),-1,'0'||TRUNC(DURACION/60),TRUNC(DURACION/60))||':'||
        DECODE(SIGN(MOD(DURACION,60)-10),-1,'0'||MOD(DURACION,60),MOD(DURACION,60)) CONSUMO,
        'Llamada Entrante' TIPO--,
        --OUT_TIPOTRAFICO,ORIENTACION,TECNOLOGIA
        from {$schema}.cdr_tfi PARTITION(CDR_TFI_[partition])
        where dni in (SELECT substr(subscription_access_number, 3, 11) FROM usraes.lineas_tmp1_{$this->userIdentifier})
        and fechainicio >= to_date('[fecha1]','yyyymmddHH24MISS')
        and fechainicio <= to_date('[fecha2]','yyyymmddHH24MISS')
        AND ORIENTACION='Terminated'";

        $cod_cli_salientes = "SELECT ANI NUMERO_ORIGEN,  
        TO_CHAR(FECHAINICIO,'DD/MM/YYYY') FECHA,
        TO_CHAR(FECHAINICIO,'HH24:MI:SS') HORA_INICIO,
        TO_CHAR(FECHAFIN,'HH24:MI:SS') HORA_FIN,
        DNICENTRAL NUMERO_DESTINO,
        DECODE(SIGN(TRUNC(DURACION/60)-10),-1,'0'||TRUNC(DURACION/60),TRUNC(DURACION/60))||':'||
        DECODE(SIGN(MOD(DURACION,60)-10),-1,'0'||MOD(DURACION,60),MOD(DURACION,60)) CONSUMO,
        'Llamada Saliente' TIPO--,
        --OUT_TIPOTRAFICO,ORIENTACION,TECNOLOGIA
        from {$schema}.cdr_tfi PARTITION(CDR_TFI_[partition])
        where ani in (SELECT substr(subscription_access_number, 3, 11) FROM usraes.lineas_tmp1_{$this->userIdentifier})
        and fechainicio >= to_date('[fecha1]','yyyymmddHH24MISS')
        and fechainicio <= to_date('[fecha2]','yyyymmddHH24MISS')
        and ORIENTACION='Originated'";*/

        $sql_entrantes = "SELECT /*+PARALLEL(4)*/ DNICENTRAL NUMERO_ORIGEN,
        TO_CHAR(FECHAINICIO,'DD/MM/YYYY') FECHA,
        TO_CHAR(FECHAINICIO,'HH24:MI:SS') HORA_INICIO,
        TO_CHAR(FECHAFIN,'HH24:MI:SS') HORA_FIN,
        ANICENTRAL NUMERO_DESTINO,
        DECODE(SIGN(TRUNC(DURACION/60)-10),-1,'0'||TRUNC(DURACION/60),TRUNC(DURACION/60))||':'||
        DECODE(SIGN(MOD(DURACION,60)-10),-1,'0'||MOD(DURACION,60),MOD(DURACION,60)) CONSUMO,
        'Llamada Entrante VoLTE' TIPO--,
        --OUT_TIPOTRAFICO 
        from {$schema}.cdr_tfi PARTITION(CDR_TFI_[partition])
        where dni in (SELECT substr(subscription_access_number, 3, 11) FROM usraes.lineas_tmp1_{$this->userIdentifier}) and 
        fechainicio >= to_date('[fecha1]','yyyymmddHH24MISS') and 
        fechainicio <= to_date('[fecha2]','yyyymmddHH24MISS') and 
        OUT_TIPOTRAFICO = 'VoLTE'";
        //OUT_TIPOTRAFICO like 'VoLTE' AND ORIENTACION='Terminated'{$volte_filter}";

        $sql_salientes = "SELECT /*+PARALLEL(4)*/ ANICENTRAL NUMERO_ORIGEN,  
        TO_CHAR(FECHAINICIO,'DD/MM/YYYY') FECHA,
        TO_CHAR(FECHAINICIO,'HH24:MI:SS') HORA_INICIO,
        TO_CHAR(FECHAFIN,'HH24:MI:SS') HORA_FIN,
        DNICENTRAL NUMERO_DESTINO,
        DECODE(SIGN(TRUNC(DURACION/60)-10),-1,'0'||TRUNC(DURACION/60),TRUNC(DURACION/60))||':'||
        DECODE(SIGN(MOD(DURACION,60)-10),-1,'0'||MOD(DURACION,60),MOD(DURACION,60)) CONSUMO,
        'Llamada Saliente VoLTE' TIPO--,
        --OUT_TIPOTRAFICO 
        from {$schema}.cdr_tfi PARTITION(CDR_TFI_[partition])
        where ani in (SELECT substr(subscription_access_number, 3, 11) FROM usraes.lineas_tmp1_{$this->userIdentifier})
        and fechainicio >= to_date('[fecha1]','yyyymmddHH24MISS')
        and fechainicio <= to_date('[fecha2]','yyyymmddHH24MISS')
        and OUT_TIPOTRAFICO = 'VoLTE'
        AND dni != '997991802'";
        //and OUT_TIPOTRAFICO like 'VoLTE' and ORIENTACION='Originated'{$volte_filter}";

        /*if($this->reporte_by === "cod_cliente" || $this->is_primarios){
            $sql_entrantes = $cod_cli_entrantes;
            $sql_salientes = $cod_cli_salientes;
        }*/

        /*$dwh_parts = DB::connection($this->connection)
        ->select(DB::raw("SELECT
        max(replace(PARTITION_name, 'CDR_DWH_')) fecha_max,
        min(replace(PARTITION_name, 'CDR_DWH_')) fecha_min
        FROM all_tab_partitions
        WHERE table_name = 'CDR_DWH' and segment_created = 'YES' AND NUM_ROWS IS NOT NULL AND NUM_ROWS<>0"))[0];

        $tfi_parts = DB::connection($this->connection)
        ->select(DB::raw("SELECT
        MAX(replace(PARTITION_name, 'CDR_TFI_')) fecha_max, MIN(replace(PARTITION_name, 'CDR_TFI_')) fecha_min
        FROM all_tab_partitions
        WHERE table_name = 'CDR_TFI' and segment_created = 'YES'"))[0];*/

        /*while ($periodo->format("Ymd") <= $periodo2->format("Ymd"))
        {
        $str_periodo = $periodo->format("Ymd");
        */

        /*if($this->reporte_by !== "cod_cliente" || !$this->is_primarios){
            $queries[] = ["sql" => "BEGIN
            INSERT INTO USRAES.T_REP_LLA2_{$this->userIdentifier}(NUMERO_ORIGEN,FECHA,HORA_INICIO,HORA_FIN,NUMERO_DESTINO,CONSUMO,TIPO)
            SELECT
            TIM_NUMBER NUMERO_ORIGEN, 
            TO_CHAR(TO_DATE(SUBSTR(CHARGING_START_TIME,1,8),'YYYYMMDD'),'DD/MM/YYYY') FECHA,
            SUBSTR(CHARGING_START_TIME,9,2)||':'||SUBSTR(CHARGING_START_TIME,11,2)||':'||SUBSTR(CHARGING_START_TIME,13,2) HORA_INICIO,
            SUBSTR(CHARGING_END_TIME,9,2)||':'||SUBSTR(CHARGING_END_TIME,11,2)||':'||SUBSTR(CHARGING_END_TIME,13,2) HORA_FIN,
            NVL(NUMBER_B,DIALLED_DIGITS) NUMERO_DESTINO,
            DECODE(SIGN(TRUNC(CALL_DURATION/60)-10),-1,'0'||TRUNC(CALL_DURATION/60),TRUNC(CALL_DURATION/60))||':'||
            DECODE(SIGN(MOD(CALL_DURATION,60)-10),-1,'0'||MOD(CALL_DURATION,60),MOD(CALL_DURATION,60)) CONSUMO,
            DECODE(RECORD_TYPE,'08','Mensaje Saliente','01','Llamada Saliente','09','Mensaje Entrante','02','Llamada Entrante') TIPO--,RECORD_TYPE
            FROM {$schema}.CDR_DWH PARTITION(CDR_DWH_".$str_periodo.")
            WHERE TIM_NUMBER IN (SELECT subscription_access_number FROM usraes.lineas_tmp1_{$this->userIdentifier}) --COLOCAR LA LINEA
            AND CHARGING_START_TIME >= '$str_periodo_1' -- FECHA DE INICIO
            AND CHARGING_START_TIME <= '$str_periodo_2' -- FECHA FIN
            AND RECORD_TYPE IN ($str_records_type)
            AND CALL_DURATION > 0
            AND NVL(NVL(NUMBER_B,DIALLED_DIGITS),'NN')<>'NN'
            ORDER BY CHARGING_START_TIME;
            COMMIT;
            END;"];
        }*/
        $queries[] = ["sql" => "BEGIN
            INSERT INTO USRAES.T_REP_LLA2_{$this->userIdentifier}(NUMERO_ORIGEN,FECHA,HORA_INICIO,HORA_FIN,NUMERO_DESTINO,CONSUMO,TIPO)
            SELECT /*+PARALLEL(4)*/
            TIM_NUMBER NUMEROA, 
            TO_CHAR(TO_DATE(SUBSTR(CHARGING_START_TIME,1,8),'YYYYMMDD'),'DD/MM/YYYY') FECHA,
            SUBSTR(CHARGING_START_TIME,9,2)||':'||SUBSTR(CHARGING_START_TIME,11,2)||':'||SUBSTR(CHARGING_START_TIME,13,2) HORA_INICIO,
            SUBSTR(CHARGING_END_TIME,9,2)||':'||SUBSTR(CHARGING_END_TIME,11,2)||':'||SUBSTR(CHARGING_END_TIME,13,2) HORA_FIN,
            NVL(NUMBER_B,DIALLED_DIGITS) NUMEROB,
            DECODE(SIGN(TRUNC(CALL_DURATION/60)-10),-1,'0'||TRUNC(CALL_DURATION/60),TRUNC(CALL_DURATION/60))||':'||
            DECODE(SIGN(MOD(CALL_DURATION,60)-10),-1,'0'||MOD(CALL_DURATION,60),MOD(CALL_DURATION,60)) CONSUMO,
            DECODE(RECORD_TYPE,'02','Llamada Entrante','12','Llamada Saliente'/*SALIENTE VOLTE*/,'04','LLamada Entrante','14','Llamada Entrante',
            '30','Llamada Entrante','31','Llamada Saliente','11','Llamada Entrante',/*ENTRANTE VOLTE,*/
            '08','Mensaje Saliente','13','Llamada Saliente','01','Llamada Saliente','03','Llamada Saliente',
            '09','Mensaje Entrante') TIPO--,RECORD_TYPE
            FROM {$schema}.CDR_DWH
            WHERE TIM_NUMBER IN (select * from usraes.LINEAS_TMP1_{$this->userIdentifier})
            AND CHARGING_START_TIME >= '$str_periodo_1'
            AND CHARGING_START_TIME <= '$str_periodo_2'
            AND RECORD_TYPE IN ($str_records_type)
            AND CALL_DURATION > 0
            AND NVL(NVL(NUMBER_B,DIALLED_DIGITS),'NN')<>'NN'
            ORDER BY CHARGING_START_TIME;
            COMMIT;
        END;"];

        while ($periodo->format("Ymd") <= $periodo2->format("Ymd"))
        {
            $str_periodo = $periodo->format("Ymd");

            switch ($tipo_reporte) {
                case TipoReporte::ENTRANTES:
                    $queries[] = ["sql" => "DECLARE
                        V_ERROR NUMBER := 0;
                    BEGIN
                        INSERT INTO USRAES.T_REP_LLA2_{$this->userIdentifier}(NUMERO_ORIGEN,FECHA,HORA_INICIO,HORA_FIN,NUMERO_DESTINO,CONSUMO,TIPO)
                        ".str_replace(["[fecha1]","[fecha2]", "[partition]"], [$str_periodo_1,$str_periodo_2,$str_periodo], $sql_entrantes).";
                        COMMIT;
                    EXCEPTION
                        WHEN OTHERS THEN
                            V_ERROR := 1;
                    END;"];
                    break;
                case TipoReporte::SALIENTES:
                    $queries[] = ["sql" => "DECLARE
                        V_ERROR NUMBER := 0;
                    BEGIN
                        INSERT INTO USRAES.T_REP_LLA2_{$this->userIdentifier}(NUMERO_ORIGEN,FECHA,HORA_INICIO,HORA_FIN,NUMERO_DESTINO,CONSUMO,TIPO)
                        ".str_replace(["[fecha1]","[fecha2]", "[partition]"], [$str_periodo_1,$str_periodo_2,$str_periodo], $sql_salientes).";
                        COMMIT;
                    EXCEPTION
                        WHEN OTHERS THEN
                            V_ERROR := 1;
                    END;"];
                    break;
                case TipoReporte::ENTRANTES_SALIENTES:
                    $queries[] = ["sql" => "DECLARE
                        V_ERROR NUMBER := 0;
                    BEGIN
                        INSERT INTO USRAES.T_REP_LLA2_{$this->userIdentifier}(NUMERO_ORIGEN,FECHA,HORA_INICIO,HORA_FIN,NUMERO_DESTINO,CONSUMO,TIPO)
                        ".str_replace(["[fecha1]","[fecha2]", "[partition]"], [$str_periodo_1,$str_periodo_2,$str_periodo], $sql_entrantes)."
                        UNION ALL
                        ".str_replace(["[fecha1]","[fecha2]", "[partition]"], [$str_periodo_1,$str_periodo_2,$str_periodo], $sql_salientes).";
                        COMMIT;
                    EXCEPTION
                        WHEN OTHERS THEN
                            V_ERROR := 1;
                    END;"];
                    break;
                default:
                    break;
            }

            $periodo->modify("+1 day");
        }

        // quitar duplicados

        $queries[] = ['sql' => "BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.T_REP_LLA2_{$this->userIdentifier}_TEMP';
        EXCEPTION
            WHEN OTHERS THEN
                IF SQLCODE != -942 THEN
                    RAISE;
                END IF;
        END;"];
        $queries[] = ['sql' => "CREATE TABLE USRAES.T_REP_LLA2_{$this->userIdentifier}_TEMP (
            NUMERO_ORIGEN  VARCHAR2(100),
            FECHA          VARCHAR2(10),
            HORA_INICIO    VARCHAR2(8),
            HORA_FIN       VARCHAR2(8),
            NUMERO_DESTINO VARCHAR2(100),
            CONSUMO        VARCHAR2(100),
            TIPO           VARCHAR2(100)
        ) $tablespace"];
        $queries[] = ['sql' => "BEGIN
            INSERT INTO USRAES.T_REP_LLA2_{$this->userIdentifier}_TEMP
            (NUMERO_ORIGEN,FECHA,HORA_INICIO,HORA_FIN,NUMERO_DESTINO,CONSUMO,TIPO)
            SELECT NUMERO_ORIGEN,FECHA,HORA_INICIO,HORA_FIN,NUMERO_DESTINO,CONSUMO,TIPO FROM USRAES.T_REP_LLA2_{$this->userIdentifier}
            GROUP BY NUMERO_ORIGEN,FECHA,HORA_INICIO,HORA_FIN,NUMERO_DESTINO,CONSUMO,TIPO;
            COMMIT;
        END;"];
        $queries[] = ['sql' => "DROP TABLE USRAES.T_REP_LLA2_{$this->userIdentifier}"];
        // $queries[] = ['sql' => "RENAME TABLE USRAES.T_REP_LLA2_{$this->userIdentifier}_TEMP to USRAES.T_REP_LLA2_{$this->userIdentifier}"];
        $queries[] = ['sql' => "CREATE TABLE USRAES.T_REP_LLA2_{$this->userIdentifier} as SELECT * FROM usraes.t_rep_lla2_{$this->userIdentifier}_temp"];
        $queries[] = ['sql' => "DROP TABLE USRAES.T_REP_LLA2_{$this->userIdentifier}_TEMP"];

        /*$periodo->modify("+1 day");
        }*/

        $this->exec_sql($queries);
    }

    private function getRecordTypeByTipoReporte(string $tipo_reporte): array
    {
        $records_type = [];
        switch ($tipo_reporte) {
            case TipoReporte::ENTRANTES:
                $records_type = ['02','09','04','14', '30','11'];
                //$records_type = ['02','09'];
                break;
            case TipoReporte::SALIENTES:
                $records_type = ['01','08','12','13','03','31'];
                //$records_type = ['01','08'];
                break;
            case TipoReporte::ENTRANTES_SALIENTES:
                $records_type = ['02','09','04','14','12','30','31','01','08','11','13','03'];
                //$records_type = ['01','02','08','09'];
                break;
            default:
                break;
        }
        return $records_type;
    }
}
