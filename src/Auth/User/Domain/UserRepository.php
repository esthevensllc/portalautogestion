<?php

namespace AMovil\Auth\User\Domain;

interface UserRepository
{
    public function findByIdentifier($value);
}
