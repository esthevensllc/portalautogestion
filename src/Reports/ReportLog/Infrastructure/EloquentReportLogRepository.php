<?php

namespace AMovil\Reports\ReportLog\Infrastructure;

use AMovil\Reports\ReportLog\Domain\ReportLogRepository;
use AMovil\Shared\Infrastructure\Eloquent\EloquentCriteriaConverter;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\DB;

class EloquentReportLogRepository implements ReportLogRepository
{
    private $fields = [
        'id' => ["label" => 'Id', "type" => 'number'],
        'hostname' => ["label" => 'Hostname', "type" => 'string'],
        'name' => ["label" => 'name', "type" => 'string'],
        'direccion' => ["label" => 'direccion', "type" => 'string'],
        'area' => ["label" => 'area', "type" => 'string'],
        'contacto' => ["label" => 'contacto', "type" => 'string'],
        'codigo_c' => ["label" => 'codigo_c', "type" => 'string'],
        'responsable' => ["label" => 'responsable', "type" => 'string'],
        'filename' => ["label" => 'filename', "type" => 'string'],
        'ini' => ["label" => 'Fecha ini', "type" => 'datetime'],
        'fin' => ["label" => 'Fecha fin', "type" => 'datetime'],
        'estado' => ["label" => 'estado', "type" => 'string'],
        'trac_name' => ["label" => 'trac_name', "type" => 'string'],
    ];

    public function save($name, $direccion, $area, $contacto, $codigo_c, $responsable, $file, DateTime $ini, DateTime $fin, $lat, $estado, $mensaje, $trac_name)
    {
        DB::table('usraes.reporte_log')->insert([
            'hostname' => 'limnwkdaswfv01',
            'name' => $name,
            'direccion' => $direccion,
            'area' => $area,
            'contacto' => $contacto,
            'codigo_c' => $codigo_c,
            'responsable' => $responsable,
            'filename' => $file,
            'ini' => Carbon::createFromFormat('Y-m-d H:i:s', $ini->format('Y-m-d H:i:s')),
            'fin' => Carbon::createFromFormat('Y-m-d H:i:s', $fin->format('Y-m-d H:i:s')),
            'lat' => $lat,
            'estado' => $estado,
            'mensaje' => $mensaje,
            'trac_name' => $trac_name,
        ]);
    }

    public function getByCriteria(array $filters, $sortBy = [], $offset=0, $limit=0)
    {
        $builder = DB::table('usraes.reporte_log');
        EloquentCriteriaConverter::fromRawArray($builder, $this->fields, $filters, $sortBy);

        $response = [
            'recordsTotal' => DB::table('usraes.reporte_log')->count(),
            'recordsFiltered' => $builder->count(),
            'data' => []
        ];

        $builder = DB::table('usraes.reporte_log');
        EloquentCriteriaConverter::fromRawArray($builder, $this->fields, $filters, $sortBy, $offset, $limit);

        $response['data'] = $builder->get();
        return $response;
    }

}
