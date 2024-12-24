<?php

namespace AMovil\Shared\Session\Infrastructure;

use AMovil\Shared\Session\Domain\Session;

class LaravelSession implements Session
{
    public function set($key, $value): void
    {
        session()->put($key, $value);
    }

    public function get($key, $default = null)
    {
        return session($key, $default);
    }

    public function invalidate($key): void
    {
        session($key, null);
    }
}
