<?php

namespace AMovil\Reports\DAPU\ServicioMovil\Infrastructure;

use AMovil\Reports\DAPU\ServicioMovil\Domain\ServicioMovilRepository;
use DateTime;
use Illuminate\Support\Facades\DB;

class EloquentServicioMovilRepository implements ServicioMovilRepository
{
    public function getByMsisdnFecha(string $msisdn, DateTime $fecha)
    {
        $strDate = $fecha->format("Ymd");
        $query = "SELECT
		i.CREATE_DATE,
		i.reason_3,
		i.title,
		i.last_name,
		i.FIRST_NAME,
		e.x_doc_type,
		e.x_doc_num,
		i.PHONE,
		d.x_inter_15 as lugar,
		i.X_SUBCLASE_CODE
		from dws.sa_table_interact i
		LEFT JOIN dws.sa_table_contact e ON i.interact2contact = e.objid
		LEFT JOIN dws.sa_table_x_plus_inter d ON i.objid = d.x_plus_inter2interact
		where i.PHONE = :msisdn
		and to_char(i.CREATE_DATE,'yyyymmdd') >= :fecha";
        return DB::select($query, ["msisdn" => $msisdn, "fecha" => $strDate]);
    }
}
