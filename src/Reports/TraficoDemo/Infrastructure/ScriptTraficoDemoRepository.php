<?php

namespace AMovil\Reports\TraficoDemo\Infrastructure;

use AMovil\Reports\TraficoDemo\Domain\TraficoDemoRepository;
use AMovil\Shared\Application\FileInput;
use Exception;

class ScriptTraficoDemoRepository implements TraficoDemoRepository
{
    public function getReportBy(string $username, int $year, int $month, FileInput $file): FileInput {
        $script = "/space/scripts/portalautogestion/hfc_ftth_traffic.py -u {$username} -m {$month} -y {$year} -f {$file->getFilePath()}";
        $this->execPython($script);
        return new FileInput("/space/scripts/portalautogestion/tmp/hfc_ftth_traffic_{$username}.xlsx");
    }

    private function execPython($command){
        // $m = escapeshellarg("valor1");

        $cmd = "/space/anaconda3/bin/python {$command}";

        $descriptores = [
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w'],  // stderr
        ];

        $proceso = proc_open($cmd, $descriptores, $pipes);

        if (is_resource($proceso)) {
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);

            fclose($pipes[1]);
            fclose($pipes[2]);

            $codigo = proc_close($proceso);

            if ($codigo === 0) {
                return $stdout;
            } else {
                throw new Exception($stderr);
            }
        }
    }
}
