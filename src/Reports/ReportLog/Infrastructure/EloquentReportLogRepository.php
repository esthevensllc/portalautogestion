<?php

namespace AMovil\Reports\ReportLog\Infrastructure;

use AMovil\Reports\ReportLog\Domain\ReportLogRepository;
use Carbon\Carbon;
use DateTime;
use DB;

class EloquentReportLogRepository implements ReportLogRepository
{
    public function save($name, $direccion, $area, $contacto, $responsable, $file, DateTime $ini, DateTime $fin, $lat, $estado, $mensaje)
    {
        DB::table('usraes.reporte_log')->insert([
            'hostname' => 'limnwkdaswfv01',
            'name' => $name,
            'direccion' => $direccion,
            'area' => $area,
            'contacto' => $contacto,
            'responsable' => $responsable,
            'filename' => $file,
            'ini' => Carbon::createFromFormat('Y-m-d H:i:s', $ini->format('Y-m-d H:i:s')),
            'fin' => Carbon::createFromFormat('Y-m-d H:i:s', $fin->format('Y-m-d H:i:s')),
            'lat' => $lat,
            'estado' => $estado,
            'mensaje' => $mensaje,
        ]);
    }
}
