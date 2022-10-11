<?php

namespace AMovil\Auth\Modules\Infrastructure\Repository;

use AMovil\Auth\Modules\Domain\ModuleRepository;
use DB;

class EloquentModuleRepository implements ModuleRepository
{
    private $table = 'prg_seguimiento';

    public function __construct()
    {
        if(env('APP_ENV') !== 'local'){
            $this->table = "usraes.".$this->table;
        }
    }

    private function builder(){
       return DB::table($this->table);
    }

    public function getByIds(array $ids)
    {
        return $this->builder()
        ->where('trac_status', 1)
        ->whereIn('id_tracing', $ids)
        ->get();
    }

    public function getAsTreeByIds(array $ids)
    {
        $modulos = $this->builder()
        ->where('trac_status', 1)
        ->where(function($query) use ($ids) {
            $query->whereNull('url')->OrWhereIn('id_tracing', $ids);
        })
        ->orderBy('trac_father', 'asc')
        ->orderBy('trac_order', 'asc')
        ->get()
        ->groupBy('trac_father')
        ->toArray();

        $kpis = $this->builder()->where('trac_description', 'kpi')->orderBy('trac_order', 'asc')->get();

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
            if($row->trac_description === 'menu' && $row->url !== null){
                $filtered[] = $row->id_tracing;
            }
        }
        return $filtered;
    }

    private function filterTracings($modulos){
        $filtered = [];
        foreach($modulos as $row){
            if(count($row->tracings) !== 0 || ($row->url !== null && count($row->tracings) === 0)){
                if(isset($row->modulos)){
                    $row->modulos = $this->filterTracings($row->modulos);
                }
                $filtered[] = $row;
            }
        }
        return $filtered;
    }
}
