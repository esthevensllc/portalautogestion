<?php

namespace AMovil\Shared\Exports\Domain;

interface Writer
{
    public function save($filename, int $flags = 0): void;
    public function getOutput(): ?string;
}
