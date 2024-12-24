<?php

namespace AMovil\Reports\DAPU\ListaEir\Services;

use AMovil\Reports\DAPU\ListaEir\Domain\ListaEirRepository;
use AMovil\Shared\Application\FileInput;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use DateTime;

class ExportListaEir
{
    private $finder;
    private $exportService;

    public function __construct(ListaEirFinder $finder, ExportService $exportService)
    {
        $this->finder = $finder;
        $this->exportService = $exportService;
    }

    public function __invoke(array $values, $exportType): Response
    {
        $response = $this->finder->__invoke($values);
        if($response->fails()){
            return $response;
        }
        $data = $response->data();
        return $this->export($data, $exportType);
    }

    public function fromFile(FileInput $file, $exportType){
        $response= $this->finder->fromFile($file);
        if($response->fails()){
            return $response;
        }
        $data = $response->data();
        return $this->export($data, $exportType);
    }

    public function export($data, $type)
    {
        $headers = [
            "transact_date" => ["label" => "FECHA"],
            "task_id" => ["label" => "TASK_ID"],
            "hlrsn" => ["label" => "HLRSN"],
            "operator" => ["label" => "OPERATOR"],
            "date_time" => ["label" => "DATE_TIME"],
            "command" => ["label" => "COMMAND"],
            "cod_cmd" => ["label" => "COD_CMD"],
            "cmd_result" => ["label" => "CMD_RESULT"],
            "imei" => ["label" => "IMEI"],
        ];

        $options = [
            'styles' => [
                'header' => ['font' => ['bold' => true]]
            ]
        ];

        $this->exportService->loadData($headers, $data, $options);
        $content = $this->exportService->getWriter($type)->getOutput();
        $dt = new DateTime();
        
        return new Response([], [
            'filename' => "LISTA_EIR_".$dt->format("Ymd").".".strtolower($type),
            'type' => strtolower($type),
            'content' => $content
        ]);
    }
}
