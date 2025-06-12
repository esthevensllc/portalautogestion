<?php

namespace AMovil\Shared\Cli;

class CliPython extends CLI
{
    public function __construct()
    {
        parent::__construct("/space/anaconda3/bin/python");
    }
}
