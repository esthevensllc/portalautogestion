<?php

namespace AMovil\Shared\Cli;

use Exception;

class CliResponse
{
    private $exitCode;
    private $stdout;
    private $stderr;

    public function __construct(int $exitCode, $stdout, $stderr)
    {
        $this->exitCode = $exitCode;
        $this->stdout = $stdout;
        $this->stderr = $stderr;
    }

    public function orElseThrow(){
        if ($this->exitCode !== 0) {
            throw new Exception($this->stderr);
        }
        return $this->stdout;
    }
}
