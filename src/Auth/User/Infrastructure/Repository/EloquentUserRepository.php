<?php

namespace AMovil\Auth\User\Infrastructure\Repository;

use AMovil\Auth\User\Domain\UserRepository;
use DB;

class EloquentUserRepository implements UserRepository
{
    private $table = 'usraes.padm_user';
    private $roles_table = 'usraes.padm_user_rol';

    private $name_identifier = 'username';

    private function builder(){
        return DB::table($this->table);
    }

    private function rolBuilder(){
        return DB::table($this->roles_table);
    }

    public function findByIdentifier($value)
    {
        $usuario = $this->builder()->where($this->name_identifier, $value)->first();
        if($usuario !== null){
            $usuario->roles = $this->rolBuilder()
            ->where('user_id', $usuario->id)
            ->get();

            $roles_id = [];
            foreach($usuario->roles as $row){
                $roles_id[] = $row->rol_id;
            }
            $usuario->roles_id = $roles_id;
        }
        return $usuario;
    }

    public function findById($id)
    {
        $usuario = $this->builder()->where('id', $id)->first();
        if($usuario !== null){
            $usuario->roles = $this->rolBuilder()
            ->where('user_id', $usuario->id)
            ->get();

            $roles_id = [];
            foreach($usuario->roles as $row){
                $roles_id[] = $row->rol_id;
            }
            $usuario->roles_id = $roles_id;
        }
        return $usuario;
    }

    public function get()
    {
        $usuarios = $this->builder()->get();
        $roles_by_userid = $this->rolBuilder()->get()
        ->groupBy('user_id')
        ->map(function($roles_of_user){
            $roles_id = [];
            foreach($roles_of_user as $row){
                $roles_id[] = $row->rol_id;
            }
            return $roles_id;
        })->toArray();

        foreach($usuarios as $row){
            if(array_key_exists($row->id, $roles_by_userid)){
                $row->rols = $roles_by_userid[$row->id];
            }else{
                $row->rols = [];
            }
        }
        return $usuarios;
    }

    public function create($data)
    {
        $id = $this->builder()->count() + 1;
        $this->builder()->insert([
            'id' => $id,
            'username' => $data['username'],
            'status' => 1,
            'name' => $data['name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'direccion' => $data['direccion'] ?? null,
            'area' => $data['area'] ?? null,
        ]);
        
        $roles_to_insert = [];
        foreach($data['roles_id'] as $rol_id){
            $roles_to_insert[] = ['user_id' => $id, 'rol_id' => $rol_id];
        }
        $this->rolBuilder()->insert($roles_to_insert);
    }

    public function update($data)
    {
        $this->builder()
        ->where('id', $data['id'])
        ->update([
            'username' => $data['username'],
            // 'status' => $data['status'],
            'name' => $data['name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'direccion' => $data['direccion'] ?? null,
            'area' => $data['area'] ?? null,
        ]);

        $this->rolBuilder()->where('user_id', $data['id'])->delete();
        $roles_to_insert = [];
        foreach($data['roles_id'] as $rol_id){
            $roles_to_insert[] = ['user_id' => $data['id'], 'rol_id' => $rol_id];
        }
        $this->rolBuilder()->insert($roles_to_insert);
    }

    public function changeStatus($id, $status)
    {
        $this->builder()
        ->where('id', $id)
        ->update(['status' => $status]);
    }
}
