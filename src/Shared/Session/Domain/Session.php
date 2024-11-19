<?php

namespace AMovil\Shared\Session\Domain;

interface Session
{
    public function set($key, $value): void;
    public function get($key, $default = null);
    public function invalidate($key): void;
}
