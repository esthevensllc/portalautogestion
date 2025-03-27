<?php

namespace AMovil\Auth\Roles\Infrastructure\Repository;

use Illuminate\Database\Eloquent\Model;

class RolModel extends Model
{
    protected $table = '';

    public function __construct(array $attributes = [])
    {
        $this->table = config('app.auth_table_rol');
        parent::__construct($attributes);
    }
}
