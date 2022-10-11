<?php

namespace AMovil\Auth\User\Infrastructure\Repository;

use AMovil\Auth\User\Domain\UserRepository;
use DB;

class EloquentUserRepository implements UserRepository
{
    private $table = 'padm_user';
    private $roles_table = 'padm_user_rol';

    private $name_identifier = 'username';

    public function __construct()
    {
        if(env('APP_ENV') !== 'local'){
            $this->table = "usraes.".$this->table;
            $this->roles_table = "usraes.".$this->roles_table;
        }
    }

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
}
