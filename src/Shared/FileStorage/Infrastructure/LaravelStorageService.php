<?php

namespace AMovil\Shared\FileStorage\Infrastructure;

use AMovil\Shared\FileStorage\Domain\StorageService;
use Illuminate\Support\Facades\Storage;

class LaravelStorageService implements StorageService
{
    public function getStorageSystemByName($name)
    {
        return Storage::disk($name);
    }
}
