<?php

namespace AMovil\Auth\Modules\Infrastructure\Repository;

use AMovil\Auth\Modules\Domain\ModuleRepository;
use DB;

class EloquentModuleRepository implements ModuleRepository
{
    private $table = '';
    private $fields = [
        'id_tracing',
        'trac_name as label',
        'trac_description as type',
        'trac_status as status',
        'trac_order as order',
        'trac_father as father_id',
        'url',
        'icon',
        'name'
    ];

    public function __construct()
    {
        $this->table = config('app.auth_table_seguimiento');
    }

    private function builder(){
       return DB::table($this->table);
    }

    public function get()
    {
        return $this->builder()
        ->select($this->fields)
        ->get();
    }

    public function getByIds(array $ids)
    {
        return $this->builder()
        ->select($this->fields)
        ->where('trac_status', 1)
        ->whereIn('id_tracing', $ids)
        ->get();
    }

    public function getAsTreeByIds(array $ids)
    {
        $modulos = $this->builder()
        ->select($this->fields)
        ->where('trac_status', 1)
        ->where(function($query) use ($ids) {
            $query->whereNull('name')->OrWhereIn('id_tracing', $ids);
        })
        ->orderBy('trac_father', 'asc')
        ->orderBy('trac_order', 'asc')
        ->get()
        ->groupBy('father_id')
        ->toArray();

        $kpis = $this->builder()
        ->select($this->fields)
        ->where('trac_status', 1)
        ->where('trac_description', 'kpi')
        ->orderBy('trac_order', 'asc')
        ->get();

        $response = $this->getModulosTo($kpis, $modulos);

        $modulos = $this->filterTracings($response['modulos']);

        return $modulos;
    }

    private function getModulosTo($modulos, $modulos_by_tracfather){
        //$modulos = null;
        $tracings = [];
        foreach($modulos as $row){
            if(array_key_exists($row->id_tracing, $modulos_by_tracfather)){
                $row->modulos = $modulos_by_tracfather[$row->id_tracing];
                $response = $this->getModulosTo($row->modulos, $modulos_by_tracfather);
                $row->modulos = $response['modulos'];
                $row->tracings = array_merge($response['tracings'], $this->getArrayIdTracings($row->modulos));
                $tracings = array_merge($tracings, $row->tracings);
            }else{
                $row->tracings = [];
            }
        }
        return ['modulos' => $modulos, 'tracings' => $tracings];
    }

    private function getArrayIdTracings($modulos){
        $filtered = [];
        foreach($modulos as $row){
            if($row->type === 'menu' && $row->name !== null){
                $filtered[] = $row->id_tracing;
            }
        }
        return $filtered;
    }

    private function filterTracings($modulos){
        $filtered = [];
        foreach($modulos as $row){
            if(count($row->tracings) !== 0 || ($row->name !== null && count($row->tracings) === 0)){
                if(isset($row->modulos)){
                    $row->modulos = $this->filterTracings($row->modulos);
                }
                $filtered[] = $row;
            }
        }
        return $filtered;
    }
}
