<?php

namespace AMovil\Auth\Roles\Domain;

interface RolRepository
{
    public function getModulesIdByIds(array $ids);
}
