<?php

namespace AMovil\Auth\AccessControl\Domain;

interface AuthService
{
    public function login(string $username, string $password);
    public function logout();
    public function validateSession(array $params = []);
    public function getUserIdentifier();
    public function getConfig($name);
}