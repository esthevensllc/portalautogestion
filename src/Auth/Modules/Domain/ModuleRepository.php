<?php

namespace AMovil\Auth\Modules\Domain;

interface ModuleRepository
{
    public function getByIds(array $ids);
    public function getAsTreeByIds(array $ids);
}
