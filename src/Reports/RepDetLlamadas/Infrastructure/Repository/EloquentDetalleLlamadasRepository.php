<?php

namespace AMovil\Reports\RepDetLlamadas\Infrastructure\Repository;

use AMovil\Reports\RepDetLlamadas\Domain\DetalleLlamadasRepository;
use AMovil\Reports\RepDetLlamadas\Domain\TipoReporte;
use DateTime;
use DB;

class EloquentDetalleLlamadasRepository implements DetalleLlamadasRepository
{
    private $connection = '';

    private function setConnection(DateTime $periodo)
    {
        if(DateTime::createFromFormat('Ym', '202101') <= $periodo){
            $this->connection = 'oracle_reptdm';
        } else {
            $this->connection = 'oracle_dwhhis';
        }
    }

    public function getReporteByPeriodo_CodCliente_tipo(DateTime $periodo1, DateTime $periodo2, array $cod_cliente, string $tipo_reporte)
    {
        $this->setConnection($periodo2);

        $str_periodo = $periodo2->format("Ym");
        $this->generateLineasTableBy($str_periodo, 'customer_account_sc', $cod_cliente);
        $str_periodo_1 = $periodo1->format("Ymd")."000000";
        $str_periodo_2 = $periodo2->format("Ymd")."235959";

        $records_type = $this->getRecordTypeByTipoReporte($tipo_reporte);

        return $this->reporteBuilder()
        ->where('charging_start_time', '>=', $str_periodo_1)
        ->where('charging_start_time', '<=', $str_periodo_2)
        ->whereIn('record_type', $records_type)
        ->where('call_duration', '>', 0)
        ->where(DB::Raw("nvl(nvl(number_b,dialled_digits),'NN')"), '<>', 'NN')
        ->orderBy('charging_start_time')
        ->get()
        ->toArray();
    }

    public function getReporteByPeriodo_NumDocumento_tipo(DateTime $periodo1, DateTime $periodo2, array $num_documento, string $tipo_reporte)
    {
        $this->setConnection($periodo2);

        $str_periodo = $periodo2->format("Ym");
        $this->generateLineasTableBy($str_periodo, 'id_card_value', $num_documento);
        $str_periodo_1 = $periodo1->format("Ymd")."000000";
        $str_periodo_2 = $periodo2->format("Ymd")."235959";

        $records_type = $this->getRecordTypeByTipoReporte($tipo_reporte);

        return $this->reporteBuilder()
        ->where('charging_start_time', '>=', $str_periodo_1)
        ->where('charging_start_time', '<=', $str_periodo_2)
        ->whereIn('record_type', $records_type)
        ->where('call_duration', '>', 0)
        ->where(DB::Raw("nvl(nvl(number_b,dialled_digits),'NN')"), '<>', 'NN')
        ->orderBy('charging_start_time')
        ->get()
        ->toArray();
    }

    public function getReporteByPeriodo_NumCuenta_tipo(DateTime $periodo1, DateTime $periodo2, array $num_cuenta, string $tipo_reporte)
    {
        $this->setConnection($periodo2);

        $str_periodo = $periodo2->format("Ym");
        $this->generateLineasTableBy($str_periodo, 'customer_account_desc', $num_cuenta);
        $str_periodo_1 = $periodo1->format("Ymd")."000000";
        $str_periodo_2 = $periodo2->format("Ymd")."235959";

        $records_type = $this->getRecordTypeByTipoReporte($tipo_reporte);

        return $this->reporteBuilder()
        ->where('charging_start_time', '>=', $str_periodo_1)
        ->where('charging_start_time', '<=', $str_periodo_2)
        ->whereIn('record_type', $records_type)
        ->where('call_duration', '>', 0)
        ->where(DB::Raw("nvl(nvl(number_b,dialled_digits),'NN')"), '<>', 'NN')
        ->orderBy('charging_start_time')
        ->get()
        ->toArray();
    }

    public function getReporteByPeriodo_Lineas_tipo(DateTime $periodo1, DateTime $periodo2, array $lineas, string $tipo_reporte)
    {
        $this->setConnection($periodo2);

        $this->reloadLineasTable($lineas);
        $str_periodo_1 = $periodo1->format("Ymd")."000000";
        $str_periodo_2 = $periodo2->format("Ymd")."235959";

        $records_type = $this->getRecordTypeByTipoReporte($tipo_reporte);

        return $this->reporteBuilder()
        ->where('charging_start_time', '>=', $str_periodo_1)
        ->where('charging_start_time', '<=', $str_periodo_2)
        ->whereIn('record_type', $records_type)
        ->where('call_duration', '>', 0)
        ->where(DB::Raw("nvl(nvl(number_b,dialled_digits),'NN')"), '<>', 'NN')
        ->orderBy('charging_start_time')
        ->get()
        ->toArray();
    }

    private function generateLineasTableBy(string $periodo, string $field, array $values)
    {
        $binds = [];
        $sql_binds = [];
        foreach($values as $i => $v){
            $binds["p_{$i}"] = $v;
            $sql_binds[] = ":p_{$i}";
        }
        $sql_binds = implode(",", $sql_binds);

        $sql1 = ['sql' => "BEGIN
            BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE usraes.lineas_tmp1';
            EXCEPTION
                WHEN OTHERS THEN
                    IF SQLCODE != -942 THEN
                        RAISE;
                    END IF;
            END;

            EXECUTE IMMEDIATE 'CREATE TABLE usraes.lineas_tmp1(subscription_access_number varchar2(250))';
        END;"];
        DB::statement(DB::Raw($sql1['sql']));

        $sql2 = ["sql" => "BEGIN
            INSERT INTO usraes.lineas_tmp1(subscription_access_number)
            SELECT subscription_access_number  FROM (
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
            AND s.subscription_status <> 'D'
            AND s.subscription_status <> 'S'
            ORDER BY F_FIN desc);
            COMMIT;
        END;", 'params' => $binds];
        DB::statement(DB::Raw($sql2['sql']), $binds);

        $data = DB::table('usraes.lineas_tmp1')->get()->toArray();
        for ($i=0; $i < count($data); $i++) { 
            $data[$i] = $data[$i]->subscription_access_number;
        }
        $this->reloadLineasTable($data);
    }

    private function reloadLineasTable(array $lineas){
        $tablespace = '';
        //if($this->connection === 'oracle_reptdm'){
        //}
        $tablespace = ' tablespace WORKAREA';
        $sql = ['sql' => "BEGIN
            BEGIN
                EXECUTE IMMEDIATE 'DROP TABLE usraes.lineas_tmp1';
            EXCEPTION
                WHEN OTHERS THEN
                    IF SQLCODE != -942 THEN
                        RAISE;
                    END IF;
            END;

            EXECUTE IMMEDIATE 'CREATE TABLE usraes.lineas_tmp1(subscription_access_number varchar2(250)){$tablespace}';
        END;"];
        $this->exec_sql([$sql]);

        $data = [];
        for ($i=0; $i < count($lineas); $i++) { 
            $data[$i] = ['subscription_access_number' => $lineas[$i]];
        }
        DB::connection($this->connection)->table('usraes.lineas_tmp1')->insert($data);
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

    private function reporteBuilder(){
        $schema = 'dwm';
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
            $query->select('subscription_access_number')->from('usraes.lineas_tmp1');
        });
    }

    private function getRecordTypeByTipoReporte(string $tipo_reporte): array
    {
        $records_type = [];
        switch ($tipo_reporte) {
            case TipoReporte::ENTRANTES:
                $records_type = ['09','11','14', '30'];
                break;
            case TipoReporte::SALIENTES:
                $records_type = ['01','03','08','04','12','13','31'];
                break;
            case TipoReporte::ENTRANTES_SALIENTES:
                $records_type = ['01','03','08','04','09', '12', '14', '30', '31', '11', '13'];
                break;
            default:
                break;
        }
        return $records_type;
    }
}
