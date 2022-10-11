<?php

namespace AMovil\Auth\Roles\Infrastructure\Repository;

use AMovil\Auth\Roles\Domain\RolRepository;
use DB;

class EloquentRolRepository implements RolRepository
{
    private $table = "padm_rol_seguimiento";
    
    public function __construct()
    {
        if(env('APP_ENV') !== 'local'){
            $this->table = "usraes.".$this->table;
        }
    }

    private function builder(){
        return DB::table($this->table);
    }

    public function getModulesIdByIds(array $ids)
    {
        $modules = $this->builder()
        ->select('id_tracing')
        ->whereIn('rol_id', $ids)
        ->groupBy('id_tracing')
        ->get();
        $modules_id = [];
        foreach($modules as $row){
            $modules_id[] = $row->id_tracing;
        }
        return $modules_id;
    }
}
