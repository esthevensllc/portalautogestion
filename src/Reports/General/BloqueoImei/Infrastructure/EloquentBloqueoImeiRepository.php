<?php

namespace AMovil\Reports\General\BloqueoImei\Infrastructure;

use AMovil\Reports\General\BloqueoImei\Domain\BloqueoImeiRepository;
use DateTime;
use Illuminate\Support\Facades\DB;

class EloquentBloqueoImeiRepository implements BloqueoImeiRepository
{
    public function getReporte($id, $filename, $imeis, DateTime $fechaIni, DateTime $fechaFin)
    {
        $strFechaIni = $fechaIni->format("Y-m-d H:i:s");
        $strFechaFin = $fechaFin->format("Y-m-d H:i:s");
        $strFecha = $fechaIni->format("Ymd");

        foreach($imeis as $imei){
            DB::connection("ch-dn02")
            ->table("bloqueo_imei.base_imei")
            ->insert([
                "id_report" => $id,
                "filename" => $filename,
                "imei" => $imei
            ]);
        }

        $sql = "SELECT
        x.served_imeisv2,x.served_msisdn,x.serving_node_address1,x.serving_node_plmn_identifier,
        y.name,x.record_opening_time,x.rattype,x.cause_for_rec_closing,
        x.losd_datavolume_fbc_uplink,x.losd_datavolume_fbc_downlink,
        x.pgw_address,x.charging_id,x.served_pdppdn_address1,x.duration,x.charging_characteristics,
        x.losd_rating_group,x.uli_lac,x.uli_sac,x.uli_ci,x.uli_tai,x.uli_ecgi,x.losd_time_of_report
        from 
        (
            select toInt64(substr(toString(served_imeisv),1,14)) served_imeisv2,INET_NTOA(serving_node_address) as serving_node_address1 ,serving_node_plmn_identifier, served_msisdn,record_opening_time,rattype,access_point_name_ni,cause_for_rec_closing,losd_datavolume_fbc_uplink, losd_datavolume_fbc_downlink,
            pgw_address,charging_id,INET_NTOA(served_pdppdn_address) as served_pdppdn_address1,duration,charging_characteristics,
            losd_rating_group,uli_lac,uli_sac,uli_ci,uli_tai,uli_ecgi,losd_time_of_report
            from cdrdatos.cdr{$strFecha}
            where (toDateTime('{$strFechaIni}') <= record_opening_time and record_opening_time <= toDateTime('{$strFechaFin}'))
            AND substr(toString(served_imeisv),1,14)
            in (select imei from bloqueo_imei.base_imei where id_report = '{$id}' group by 1)
            --and toString(rattype) not in ('3')
            group by served_imeisv2, served_msisdn,serving_node_address1,serving_node_plmn_identifier,
            record_opening_time,rattype,access_point_name_ni,cause_for_rec_closing,losd_datavolume_fbc_uplink,
            losd_datavolume_fbc_downlink,
            pgw_address,charging_id,served_pdppdn_address1,duration,charging_characteristics,
            losd_rating_group,uli_lac,uli_sac,uli_ci,uli_tai,uli_ecgi,losd_time_of_report
        )  x
        join cdrdatos.access_points y on toString(x.access_point_name_ni)=toString(y.id)  
        group by 1,2,3,4,5,6,7,8,9,10,
        11,12,13,14,15,16,17,18,19,20,21,22";

        $data = DB::connection("ch-dn02")->select($sql);
        return $data;
    }

    public function getReporteFromBaseImei(DateTime $fechaIni, DateTime $fechaFin)
    {
        $strFechaIni = $fechaIni->format("Y-m-d H:i:s");
        $strFechaFin = $fechaFin->format("Y-m-d H:i:s");
        $strFecha = $fechaIni->format("Ymd");

        $sql = "SELECT
        x.served_imeisv2,x.served_msisdn,x.serving_node_address1,x.serving_node_plmn_identifier,
        y.name,x.record_opening_time,x.rattype,x.cause_for_rec_closing,
        x.losd_datavolume_fbc_uplink,x.losd_datavolume_fbc_downlink
        from
        (
            select toInt64(substr(toString(served_imeisv),1,14)) served_imeisv2,INET_NTOA(serving_node_address) as serving_node_address1 ,serving_node_plmn_identifier, served_msisdn,record_opening_time,rattype,access_point_name_ni,cause_for_rec_closing,losd_datavolume_fbc_uplink, losd_datavolume_fbc_downlink
            from cdrdatos.cdr{$strFecha}
            where (toDateTime('{$strFechaIni}') <= record_opening_time and record_opening_time <= toDateTime('{$strFechaFin}'))
            AND substr(toString(served_imeisv),1,14)
            in (select imei from bloqueo_imei.base_imei where delete_flag=0 group by 1)
            --and toString(rattype) not in ('3')
            group by served_imeisv2, served_msisdn,serving_node_address1,serving_node_plmn_identifier,
            record_opening_time,rattype,access_point_name_ni,cause_for_rec_closing,losd_datavolume_fbc_uplink,
            losd_datavolume_fbc_downlink
        )  x
        join cdrdatos.access_points y on toString(x.access_point_name_ni)=toString(y.id)  
        group by 1,2,3,4,5,6,7,8,9,10";

        $data = DB::connection("ch-dn02")->select($sql);
        return $data;
    }

    public function getReporteLogByCriteria($filters)
    {
        $builder = DB::connection("ch-dn02")->table("bloqueo_imei.base_imei_log");
        foreach($filters as $row){
            $builder->where($row[0], $row[1]);
        }
        return $builder->get();
    }

    public function saveReporteLog($id, $username, $filename, $nRegistros)
    {
        $now = new DateTime();
        DB::connection("ch-dn02")
        ->table("bloqueo_imei.base_imei_log")
        ->insert([
            "id_report" => $id,
            "username" => $username,
            "filename" => $filename,
            "n_registros" => $nRegistros,
            "uploaded_at" => $now->format("Y-m-d H:i:s")
        ]);
    }

    public function deleteReporteLog($id)
    {
        DB::connection("ch-dn02")
        ->statement(DB::raw("alter table bloqueo_imei.base_imei_log update delete_flag = 1 where id_report = '{$id}'"));

        DB::connection("ch-dn02")
        ->statement(DB::raw("alter table bloqueo_imei.base_imei update delete_flag = 1 where id_report = '{$id}'"));
    }

    public function getAutomaticReportLogByCriteria($filters)
    {
        $builder = DB::connection("ch-dn02")->table("bloqueo_imei.base_imei_automatico_log");
        foreach($filters as $row){
            $builder->where($row[0], $row[1]);
        }
        return $builder->get();
    }

    public function deleteAutomaticReportLog($id)
    {
        DB::connection("ch-dn02")
        ->statement(DB::raw("alter table bloqueo_imei.base_imei_automatico_log update delete_flag = 1 where id_report = '{$id}'"));

        DB::connection("ch-dn02")
        ->statement(DB::raw("alter table bloqueo_imei.base_imei_automatico update delete_flag = 1 where id_report = '{$id}'"));
    }

    public function importBaseImeiAutomatico($id, $filename, $imeis)
    {
        foreach($imeis as $imei){
            DB::connection("ch-dn02")
            ->table("bloqueo_imei.base_imei_automatico")
            ->insert([
                "id_report" => $id,
                "filename" => $filename,
                "imei" => $imei
            ]);
        }
    }

    public function deleteBaseImeiAutomatico($imeis)
    {
        foreach($imeis as $imei){
            DB::connection("ch-dn02")
            ->statement(DB::raw("alter table bloqueo_imei.base_imei_automatico
            update delete_flag = 1
            where imei = '{$imei}'"));
        }
    }

    public function saveAutomaticReportLog($id, $username, $filename, $nRegistros)
    {
        $now = new DateTime();
        DB::connection("ch-dn02")
        ->table("bloqueo_imei.base_imei_automatico_log")
        ->insert([
            "id_report" => $id,
            "username" => $username,
            "filename" => $filename,
            "n_registros" => $nRegistros,
            "uploaded_at" => $now->format("Y-m-d H:i:s")
        ]);
    }

    public function saveAutomaticReport($id, $filename, $nRegistros, $sizeBytes, DateTime $fecha)
    {
        DB::connection("ch-dn02")
        ->statement(DB::raw("alter table bloqueo_imei.report_imei_automatico delete where id = '{$id}'"));

        $now = new DateTime();
        DB::connection("ch-dn02")
        ->table("bloqueo_imei.report_imei_automatico")
        ->insert([
            "id" => $id,
            "filename" => $filename,
            "n_registros" => $nRegistros,
            "size_bytes" => $sizeBytes,
            "fecha" => $fecha->format("Y-m-d H:i:s"),
            "created_at" => $now->format("Y-m-d H:i:s")
        ]);
    }

    public function getAutomaticReportByCriteria($filters)
    {
        $builder = DB::connection("ch-dn02")->table("bloqueo_imei.report_imei_automatico");
        foreach($filters as $row){
            $builder->where($row[0], $row[1]);
        }
        return $builder->get();
    }

    public function saveConfig(bool $generateReport)
    {
        $config = $this->getConfig();
        $now = new DateTime();

        $generateReport = $generateReport ? 1 : 0;
        if($config === null){
            DB::connection("ch-dn02")
            ->table("bloqueo_imei.imei_cdr_automatico_config")
            ->insert([
                "id" => '1',
                "generate_report" => $generateReport,
                "created_at" => $now->format("Y-m-d H:i:s"),
                "updated_at" => $now->format("Y-m-d H:i:s")
            ]);
        }else{
            DB::connection("ch-dn02")
            ->statement(DB::raw("alter table bloqueo_imei.imei_cdr_automatico_config
            update generate_report = {$generateReport}, updated_at = now() where 1 = 1"));
        }
    }
    public function getConfig()
    {
        return DB::connection("ch-dn02")->table("bloqueo_imei.imei_cdr_automatico_config")->first();
    }
}
