<?php

namespace AMovil\Reports\DAPU\ListaExcepcionesImeiImsi\Services;

use AMovil\Reports\DAPU\ListaExcepcionesImeiImsi\Domain\ListaExcepcionesImeiImsiRepository;
use AMovil\Shared\Application\FileInput;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use DateTime;

class ExportListaExcepcionesImeiImsi
{
    private $finder;
    private $exportService;

    public function __construct(ListaExcepcionesImeiImsiFinder $finder, ExportService $exportService)
    {
        $this->finder = $finder;
        $this->exportService = $exportService;
    }

    public function __invoke($exportType, array $values): Response
    {
        $response = $this->finder->__invoke($values);
        if($response->fails()){
            return $response;
        }
        $data = $response->data();
        return $this->export($data, $exportType);
    }

    public function fromFile($exportType, FileInput $file){
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
            "imei" => ["label" => "IMEI"]
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
            'filename' => "LISTA_EXCEPCIONES_IMEI_IMSI_".$dt->format("Ymd").".".strtolower($type),
            'type' => strtolower($type),
            'content' => $content
        ]);
    }
}
