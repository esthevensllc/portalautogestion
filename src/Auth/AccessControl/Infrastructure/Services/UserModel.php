<?php

namespace AMovil\Auth\AccessControl\Infrastructure\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class UserModel extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = '';
    protected $primaryKey = 'id';

    public function __construct(array $attributes = [])
    {
        $this->table = config('app.auth_table_user');
        parent::__construct($attributes);
    }
}
