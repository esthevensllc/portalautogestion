<?php

namespace AMovil\Shared\Cli;

class CLI
{
    private $clientPath;

    public function __construct($clientPath)
    {
        $this->clientPath = $clientPath;
    }

    public static function fromPath($clientPath){
        return new self($clientPath);
    }

    public function execute($command, $params){
        $cmd = "{$this->clientPath} {$command}";

        foreach($params as $param){
            $m = $param;
            if (!str_starts_with($param, "-")) {
                $m = escapeshellarg($param);
            }
            $cmd .= " {$m}";
        }

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

            return new CliResponse($codigo, $stdout, $stderr);
        }
        return new CliResponse(-1, null, "No se pudo ejecutar el comando");
    }
}
