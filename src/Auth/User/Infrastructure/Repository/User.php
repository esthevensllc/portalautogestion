<?php

namespace AMovil\Auth\User\Infrastructure\Repository;

use Illuminate\Database\Eloquent\Model;
use DB;

class User extends Model
{
    protected $table = 'usraes.padm_user';
    private $roles_table = 'usraes.padm_user_rol';

    public function getRoles()
    {
        return DB::table($this->roles_table)
        ->where('user_id', $this->id)->get();
    }
}
