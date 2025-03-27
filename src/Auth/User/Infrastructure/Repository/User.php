<?php

namespace AMovil\Auth\User\Infrastructure\Repository;

use Illuminate\Database\Eloquent\Model;
use DB;

class User extends Model
{
    protected $table = '';
    private $roles_table = '';

    public function __construct(array $attributes = [])
    {
        $this->table = config('app.auth_table_user');
        $this->roles_table = config('app.auth_table_user_rol');
        parent::__construct($attributes);
    }

    public function getRoles()
    {
        return DB::table($this->roles_table)
        ->where('user_id', $this->id)->get();
    }
}
