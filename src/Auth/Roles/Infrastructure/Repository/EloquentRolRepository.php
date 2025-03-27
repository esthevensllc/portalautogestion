<?php

namespace AMovil\Auth\Roles\Infrastructure\Repository;

use AMovil\Auth\Roles\Domain\RolRepository;
use Illuminate\Support\Facades\DB;

class EloquentRolRepository implements RolRepository
{
    private $table = "";
    private $table_modules = "";

    public function __construct()
    {
        $this->table = config('app.auth_table_rol');
        $this->table_modules = config('app.auth_table_rol_seguimiento');
    }

    private function builder(){
        return DB::table($this->table);
    }

    private function builderModules(){
        return DB::table($this->table_modules);
    }

    public function getModulesIdByIds(array $ids)
    {
        $modules = $this->builderModules()
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

    public function get()
    {
        $modules = $this->builderModules()->get()
        ->groupBy('rol_id')
        ->map(function($rows){
            $modules_id = [];
            foreach($rows as $row){
                $modules_id[] = $row->id_tracing;
            }
            return $modules_id;
        })->toArray();

        $data = $this->builder()->get();
        foreach($data as $row){
            if(array_key_exists($row->id, $modules)){
                $row->modules_id = $modules[$row->id];
            }else{
                $row->modules_id = [];
            }
        }
        return $data;
    }

    public function findById($id)
    {
        $modules = $this->builderModules()->get()
        ->groupBy('rol_id')
        ->map(function($rows){
            $modules_id = [];
            foreach($rows as $row){
                $modules_id[] = $row->id_tracing;
            }
            return $modules_id;
        })->toArray();

        $data = $this->builder()->where('id', $id)->first();
        if($data !== null){
            if(array_key_exists($data->id, $modules)){
                $data->modules_id = $modules[$data->id];
            }else{
                $data->modules_id = [];
            }
        }
        return $data;
    }

    public function update($data)
    {
        $rol = RolModel::find($data['id']);
        $rol->name = $data['name'];
        $rol->save();

        $this->builderModules()->where('rol_id', $data['id'])->delete();
        $modules_to_insert = [];
        foreach($data['modules_id'] as $id_tracing){
            $modules_to_insert[] = ['rol_id' => $data['id'], 'id_tracing' => $id_tracing];
        }
        $this->builderModules()->insert($modules_to_insert);
    }

    public function changeStatus($id, $status)
    {
        $rol = RolModel::find($id);
        $rol->status = $status;
        $rol->save();
    }

    public function create($data)
    {
        $next_id = $this->builder()->select(DB::raw("max(id) + 1 as next_id"))->first()->next_id;

        $rol = new RolModel;
        $rol->id = $next_id;
        $rol->name = $data['name'];
        $rol->save();

        $this->builderModules()->where('rol_id', $next_id)->delete();
        $modules_to_insert = [];
        foreach($data['modules_id'] as $id_tracing){
            $modules_to_insert[] = ['rol_id' => $next_id, 'id_tracing' => $id_tracing];
        }
        $this->builderModules()->insert($modules_to_insert);
    }
}
