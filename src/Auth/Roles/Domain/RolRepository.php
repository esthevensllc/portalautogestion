<?php

namespace AMovil\Auth\Roles\Domain;

interface RolRepository
{
    public function getModulesIdByIds(array $ids);
    public function get();
    public function findById($id);
    public function update($data);
    public function changeStatus($id, $status);
    public function create($data);
}
