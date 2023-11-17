<?php

namespace AMovil\Reports\ExtraccionDevolucion\InformesCCPP\Infrastructure;

use AMovil\Reports\ExtraccionDevolucion\InformesCCPP\Domain\InformeCCPPRepository;
use AMovil\Shared\Infrastructure\Eloquent\EloquentCriteriaConverter;
use DateTime;
use Illuminate\Support\Facades\DB;

class EloquentInformeCCPPRepository implements InformeCCPPRepository
{
    private $fields = [
        'id' => ["label" => 'Id', "type" => 'number'],
        'username' => ["label" => 'Hostname', "type" => 'string'],
        'filename' => ["label" => 'name', "type" => 'string'],
        'created_at' => ["label" => 'direccion', "type" => 'datetime'],
    ];
    
    public function create(string $username, string $filename, DateTime $createdAt)
    {
        DB::table("extraccion_informe_ccpp")
        ->insert([
            "username" => $username,
            "filename" => $filename,
            "created_at" => $createdAt,
        ]);
    }

    public function getByCriteria(array $filters, $sortBy = [], $offset=0, $limit=0)
    {
        $builder = DB::table('usraes.extraccion_informe_ccpp');
        EloquentCriteriaConverter::fromRawArray($builder, $this->fields, $filters, $sortBy);

        $response = [
            'recordsTotal' => DB::table('usraes.extraccion_informe_ccpp')->count(),
            'recordsFiltered' => $builder->count(),
            'data' => []
        ];

        $builder = DB::table('usraes.extraccion_informe_ccpp');
        EloquentCriteriaConverter::fromRawArray($builder, $this->fields, $filters, $sortBy, $offset, $limit);

        $response['data'] = $builder->get();
        return $response;
    }
}
