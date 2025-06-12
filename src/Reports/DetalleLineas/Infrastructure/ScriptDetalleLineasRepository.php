<?php

namespace AMovil\Reports\DetalleLineas\Infrastructure;

use AMovil\Reports\DetalleLineas\Domain\DetalleLineasRepository;
use AMovil\Shared\Application\FileInput;
use AMovil\Shared\Cli\CliPython;

class ScriptDetalleLineasRepository implements DetalleLineasRepository
{
    private $cliPython;

    public function __construct()
    {
        $this->cliPython = new CliPython();
    }

    public function getReportBy(string $username, FileInput $file): FileInput {
        $response = $this->cliPython->execute("/space/scripts/portalautogestion/info_linea.py", ["-u", $username, "-f", $file->getFilePath()]);
        $response->orElseThrow();
        return new FileInput("/space/scripts/portalautogestion/tmp/info_linea/info_linea_{$username}.xlsx");
    }
}
