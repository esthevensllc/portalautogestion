<?php

namespace AMovil\Reports\DAPU\ReposicionesChip\Services;

use AMovil\Reports\DAPU\ReposicionesChip\Domain\ReposicionChipRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use DateTime;

class ExportReposicionChip
{
private $repo;
    private $exportService;

    public function __construct(ReposicionChipRepository $repo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
    }

    public function __invoke($type, $linea)
    {
        $data = $this->repo->getByLinea($linea);
        $headers = [
            "codigo_subclase" => ["label" => "CODIGO_SUBCLASE"],
            "tipo_linea" => ["label" => "TIPO_LINEA"],
            "desc_tipificacion" => ["label" => "DESC_TIPIFICACION"],
            "fecha_transaccion" => ["label" => "FECHA_TRANSACCION"],
            "numero_telefono" => ["label" => "NUMERO_TELEFONO"],
            "tipo" => ["label" => "TIPO"],
            "punto_atencion" => ["label" => "PUNTO_ATENCION"],
            "x_iccid" => ["label" => "X_ICCID"],
            "codigo_tipificacion" => ["label" => "CODIGO_TIPIFICACION"],
            "usuario_reg" => ["label" => "USUARIO_REG"],
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
            'filename' => "REPOSICION_CHIP_".$dt->format("Ymd").".".strtolower($type),
            'type' => strtolower($type),
            'content' => $content
        ]);
    }
}
