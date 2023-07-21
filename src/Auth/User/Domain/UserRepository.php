<?php

namespace AMovil\Auth\User\Domain;

interface UserRepository
{
    public function findByIdentifier($value);
    public function findById($id);
    public function get();
    public function create($data);
    public function update($data);
    public function changeStatus($id, $status);
}
