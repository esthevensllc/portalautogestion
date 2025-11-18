<?php

namespace AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Services;

use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Domain\ExtraccionDevFijaRepository;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Domain\InformeFallasRepository;
use AMovil\Reports\ExtraccionDevFija\InformesFalla\Domain\InformeFijaTipoReporte;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ExportExtraccionFijaUsuariosAfectados
{
    private $repo;
    private $informeFallaRepo;
    private $exportService;

    public function __construct(ExtraccionDevFijaRepository $repo, InformeFallasRepository $informeFallaRepo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->informeFallaRepo = $informeFallaRepo;
        $this->exportService = $exportService;
    }

    public function __invoke($ticket)
    {
        $result = $this->informeFallaRepo->getByCriteria(["ticket.eq.{$ticket}"]);
        if(count($result["data"]) === 0){
            throw new \Exception("No se encontro el informe de fallas para el ticket");
        }
        $tipo_reporte = (int) $result["data"][0]->tipo_reporte;
        $data = [];
        if ($tipo_reporte === InformeFijaTipoReporte::BY_CODCLI) {
            $data = $this->repo->getReporteUsuariosAfectadosMantenimiento($ticket);
        } else {
            $data = $this->repo->getReporteUsuariosAfectados($ticket);
        }
        $headers = [
            "item" => ["label" => "ITEM"],
            "ticket" => ["label" => "TICKET"],
            "codigo_cliente" => ["label" => "CODIGO_CLIENTE"],
            "numero_de_documento" => ["label" => "NUMERO_DE_DOCUMENTO"],
            "nombres_apellidos" => ["label" => "NOMBRES_APELLIDOS"],
            "servicio_analizado" => ["label" => "SERVICIO_ANALIZADO"],
            "servicio" => ["label" => "SERVICIO"],
            "dpto" => ["label" => "DEPARTAMENTO"],
        ];
        $options = [
            "sheetIndex" => 0,
            'title' => "FIJA_USUARIOS_AFECTADOS",
            'styles' => [
                'header' => [
                    'font' => ['bold' => true, 'size' => 9],
                    'borders'=> [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => array('rgb'=>'000000')
                        ]
                    ]
                ],
                'body' => [
                    'font' => ['size' => 9]
                ]
            ]
        ];
        $this->exportService->loadData($headers, $data, $options);
        $content = $this->exportService->getWriter(WriterType::XLSX)->getOutput();
        return Response::respData([
            "filename" => "Base_Afectados_TK{$ticket}.xlsx",
            'type' => 'xlsx',
            "content" => $content
        ]);
    }
}
