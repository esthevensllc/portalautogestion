<?php

namespace AMovil\Reports\General\FacturacionAdelantada\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\General\FacturacionAdelantada\Domain\FacturacionAdelantadaRepository;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;

class EloquentFacturacionAdelantadaRepository implements FacturacionAdelantadaRepository
{
    private $userIdentifier;
    private $authService;
    private $main_query;
    private $unidad_trafico_by_id = [
        '1' => '',
        '2' => '*1024',
        '3' => '*1024*1024',
    ];
    private $unidad_consumo_by_id = [
        '1' => ':f',
        '2' => "CASE WHEN floor(:f/60) < 10 THEN lpad(to_char(floor(:f/60)), 2, '0') ELSE to_char(floor(:f/60)) END
        || ':' || lpad(to_char((:f - floor(:f/60)*60)), 2, '0') :f",
        '3' => "CASE WHEN floor(:f/3600) < 10 THEN lpad(to_char(floor(:f/3600)) , 2, '0') ELSE to_char(floor(:f/3600)) END
        || ':' || lpad(to_char(floor((:f - floor(:f/3600)*3600)/60)), 2, '0') || ':' || lpad(to_char(:f - floor(:f/60)*60), 2, '0') :f",
    ];

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
        $this->main_query = "SELECT
        ':cuenta' CUENTA,
        :ciclo CICLO,
        '-' NRO_FACTURA,
        SUBSTR(r.s_p_number_address,3,9) NRO_TELEF_ORIG,
        --TO_CHAR(r.start_time_charge_timestamp + start_time_charge_offset / 86400,'DD/MM/YYYY') FECHA,
        TRUNC(r.start_time_charge_timestamp + start_time_charge_offset / 86400, 'DD') FECHA,
        TO_CHAR(r.start_time_charge_timestamp + start_time_charge_offset / 86400,'HH:MI:SS AM') HORA_INICIO,
         NULL AS HORA_FIN,
         remark PAIS,
        r.o_p_number_address TELEF_DESTINO,
        sum(decode(tariff_detail_rate_type_id, 2, 0, r.rated_volume)) CONSUMO,
        decode(tariff_info_sncode,
               1,
               'VOZ',
               26,
               'VOZ',
               1009,
               'MMS',
               1198,
               'IDEAS',
               1007,
               'DATOS',
               1008,
               'DATOS',
               1010,
               'DATOS',
               1107,
               'TV Movil',
               1243,
               'DATOS',
               1267,
               'DATOS',
               'SMS') TIPO_SERVICIO, 
              -- remark as DESTINO,
        zn.des DESTINO,
        REMARK as OPERADOR,
        decode(follow_up_call_type, 1, 'Saliente', 'Entrante') as TIPO_LLAMADA,
        sum(round(decode(uds_free_unit_part_id, 2, 0, r.rated_flat_amount), 2) -
            least(nvl(r.free_charge_amount, 0),
                  round(decode(uds_free_unit_part_id, 2, 0, r.rated_flat_amount), 2))) cargo_final
         FROM udr_lt_01  r, mputmtab tm, mpuzntab zn, mputttab tt, thufitab tu 
        WHERE (r.cust_info_customer_id, r.cust_info_contract_id) IN
              (SELECT co.customer_id, co.co_id
                 FROM contract_all co
                WHERE co.customer_id = ':customer_id')
          AND r.entry_date_timestamp >= to_date(':fecha_ini', 'yyyymmdd') -- FECHA_INICIO_ENTRO_AL_SISTEMA 
          AND r.entry_date_timestamp < (to_date(':fecha_fin', 'yyyymmdd') + 1) -- FECHA_FIN_ENTRO_AL_SISTEMA
          and r.start_time_charge_timestamp + start_time_charge_offset / 86400 >= to_date(':fecha_ini', 'yyyymmdd')-- FECHA_INICIO_TRAFICO
          and r.start_time_charge_timestamp + start_time_charge_offset / 86400 < (to_date(':fecha_fin', 'yyyymmdd') + 1)-- FECHA_FIN_TRAFICO
          AND r.tariff_info_zncode = zn.zncode
          AND r.tariff_info_tmcode = tm.tmcode
          AND r.tariff_info_tmversion = tm.vscode
          AND r.tariff_detail_ttcode = tt.ttcode
          and tu.file_id = r.record_id_udr_file_id
        GROUP BY entry_date_timestamp,
                 r.s_p_number_address,
                 record_id_udr_file_id,
                 r.start_time_charge_timestamp + start_time_charge_offset / 86400,
                 r.o_p_number_address,
                 tariff_detail_rate_type_id,
                 tm.des,
                 r.mc_info_code,
                 tariff_info_zncode,
                 follow_up_call_type,
                 zn.des,
                 tariff_info_sncode,
                 tt.des,
                 remark,
                 r.uds_stream_id,
                 r.uds_record_id,
                 free_units_info_account_key,
                 zn.zncode,
                 tu.filename,
                 r.ROWID
        ORDER BY r.start_time_charge_timestamp + start_time_charge_offset / 86400 asc";
    }
    
    private function generateReporte($cuenta, $numeroFactura, DateTime $fechaIni, DateTime $fechaFin)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();

        $strPeriodo = clone $fechaFin;
        $strPeriodo = $strPeriodo->modify("+1 day")->format("Ym");

        $strFechaIni = $fechaIni->format("Ymd");
        $strFechaFin = $fechaFin->format("Ymd");

        $results = DB::select(DB::raw("select customer_account_sc,customer_account_billing_cycle_sc ciclo
        from DWA.DW_M_SUBSCRIPTION_HIST  PARTITION(P_{$strPeriodo}) s --Cambiar Periodo
        where 
        s.customer_account_desc LIKE '%'||:p_cuenta 
        GROUP BY customer_account_sc,customer_account_billing_cycle_sc"), ["p_cuenta" => $cuenta]);

        if(count($results) === 0){
            throw new Exception("Ciclo no encontrado para '$cuenta'");
        }
        $customer_id = $results[0]->customer_account_sc;
        $ciclo = $results[0]->ciclo;
        if(($ciclo === null || $ciclo > 31 || $ciclo <= 0 ) && $ciclo <97 ){
            throw new Exception("El ciclo '{$ciclo}' no es válido");
        }
        
        // :cuenta
        // :ciclo
        // :customer_id
        // :fecha_ini
        // :fecha_fin

        $sub_query = str_replace([":cuenta", ":ciclo", ":customer_id", ":fecha_ini", ":fecha_fin"], [$cuenta, $ciclo, $customer_id, $strFechaIni, $strFechaFin], $this->main_query);
        return $sub_query;
    }

    public function getReporteByDates($cuenta, $numeroFactura, DateTime $fechaIni, DateTime $fechaFin)
    {
        // $this->generateReporte($cuenta, $numeroFactura, $fechaIni, $fechaFin);
        
        $sub_query = $this->generateReporte($cuenta, $numeroFactura, $fechaIni, $fechaFin);
        
        $results = DB::connection("oracle_bscs70")
        ->select(DB::raw("SELECT
        cuenta, ciclo, nro_factura, nro_telef_orig, to_char(fecha, 'dd/mm/yyyy') fecha, hora_inicio, hora_fin,
        pais, telef_destino, consumo, tipo_servicio, destino, operador, tipo_llamada, cargo_final
        FROM ({$sub_query})"));

        return $results;
    }

    public function getReporteByPeriodo($cuenta, $numeroFactura, DateTime $periodo)
    {
        $strPeriodo = $periodo->format("Ym");

        $fechaIni = (clone $periodo)->modify("-1 month");
        $fechaFin = (clone $periodo)->modify("-1 day");
        return $this->getReporteByDates($cuenta, $numeroFactura, $fechaIni, $fechaFin);
    }

    public function getReporteConsolidadoByDates($cuenta, $numeroFactura, string $unidad_trafico_id, string $unidad_consumo_id, DateTime $fechaIni, DateTime $fechaFin)
    {
        $this->generateReporte($cuenta, $numeroFactura, $fechaIni, $fechaFin);

        $strFechaIni = $fechaIni->format("Y-m-d");
        $strFechaFin = $fechaFin->format("Y-m-d");
        
        $sql_unidad_trafico = $this->unidad_trafico_by_id[$unidad_trafico_id];
        $sql_unidad_consumo = $this->unidad_consumo_by_id[$unidad_consumo_id];

        $def_unidad_consumo = "0";
        if($unidad_consumo_id === '1'){
            $def_unidad_consumo = "00";
        }else if ($unidad_consumo_id === '2') {
            $def_unidad_consumo = "00:00";
        }else if ($unidad_consumo_id === '3') {
            $def_unidad_consumo = "00:00:00";
        }

        $sub_query = $this->generateReporte($cuenta, $numeroFactura, $fechaIni, $fechaFin);

        $sub_query = "SELECT
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
        FROM (SELECT CUENTA,CICLO,NRO_FACTURA,nro_telef_orig as NRO_TEL_ORIGEN,FECHA,HORA_INICIO,HORA_FIN,PAIS,telef_destino as NRO_TEL_DESTINO,TO_NUMBER(CONSUMO) CONSUMO,TIPO_SERVICIO,DESTINO,
        OPERADOR,TIPO_LLAMADA,CARGO_FINAL FROM ({$sub_query})
        where to_date('{$strFechaIni}', 'yyyy-mm-dd') <= fecha and fecha <= to_date('{$strFechaFin}', 'yyyy-mm-dd'))g
        GROUP BY g.NRO_TEL_ORIGEN,g.NRO_FACTURA,g.CICLO,g.CUENTA)";

        return DB::connection('oracle_bscs70')
        ->select(DB::raw("SELECT
        cuenta,nro_telefono,factura,ciclo,cantidad_gprs,cantidad_gprs_mb,cantidad_gprs_gb,duracion_roaming_datos,
        total_cantidad,cantidad_sms,cantidad_mms,duracion_rpc,duracion_onnet,duracion_onnet_adi,
        duracion_offnetfijo,duracion_offnetmovil,duracion_roaming_voz,duracion_ldn,
        duracion_ldi, duracion_sin_cargo
        FROM ({$sub_query})"));
    }

    private function exec_sql(array $plsql)
    {
        foreach($plsql as $row){
            if(array_key_exists('params', $row)){
                DB::connection('oracle_bscs70')->statement(DB::Raw($row['sql']), $row['params']);
            }else{
                DB::connection('oracle_bscs70')->statement(DB::Raw($row['sql']));
            }
        }
    }
}
