<?php

namespace AMovil\Reports\ExtraccionDevFija\TicketReports\Infrastructure;

use AMovil\Reports\ExtraccionDevFija\TicketReports\Domain\FijaTicketReportRepository;
use AMovil\Shared\Infrastructure\Eloquent\EloquentCriteriaConverter;
use Illuminate\Support\Facades\DB;

class EloquentFijaTicketReportRepository implements FijaTicketReportRepository
{
    private $fields = [
        'ticket' => ["label" => 'Ticket', "type" => 'string'],
        'departamento' => ["label" => 'Departamento', "type" => 'string'],
        'fecha' => ["label" => 'Fecha', "type" => 'datetime'],
        'servicio_afectado' => ["label" => 'Servicio Afectado', "type" => 'string'],
        'abonados_afectados' => ["label" => 'Abonados Afectados', "type" => 'number'],
        'acreditados' => ["label" => 'Acreditados', "type" => 'number'],
        'no_acreditados' => ["label" => 'No Acreditados', "type" => 'number'],
    ];

    public function deleteBy($ticket)
    {
        DB::table('usraes.DWH_DEVOLUCION_MASIV_DETALLE_TOTAL')
        ->where("ticket", $ticket)
        // ->where("departamento", $departamento)
        ->delete();

        DB::table('usraes.DWH_DEVOLUCION_MASIV_DETALLE_HIST')
        ->where("ticket", $ticket)
        // ->where("dpto", $departamento)
        ->delete();

        /*DB::table('usraes.INPUT_DEVO_FIJA')
        ->where("numero_reporte", $numReporte)
        ->where("ticket", $ticket)
        ->delete();

        $servicios = DB::table('usraes.INPUT_DEVO_FIJA')
        ->where("numero_reporte", $numReporte)
        ->get();

        if(count($servicios) === 0){
            DB::table('usraes.input_devo_fija_plano')
            ->where("numero_reporte", $numReporte)
            ->delete();
        }*/
    }

    public function getTicketsByCriteria(array $filters, $sortBy = [], $offset=0, $limit=0)
    {
        $builder = DB::table('usraes.DWH_DEVOLUCION_MASIV_DETALLE_TOTAL');
        EloquentCriteriaConverter::fromRawArray($builder, $this->fields, $filters, $sortBy);

        $response = [
            'recordsTotal' => DB::table('usraes.reporte_log')->count(),
            'recordsFiltered' => $builder->count(),
            'data' => []
        ];

        $builder = DB::table('usraes.DWH_DEVOLUCION_MASIV_DETALLE_TOTAL');
        EloquentCriteriaConverter::fromRawArray($builder, $this->fields, $filters, $sortBy, $offset, $limit);

        $response['data'] = $builder->get();
        return $response;
    }
}
