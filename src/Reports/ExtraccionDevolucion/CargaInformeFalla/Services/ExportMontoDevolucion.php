<?php

namespace AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Services;

use AMovil\Reports\ExtraccionDevolucion\ExtraccionDevolucion\Domain\ExtraccionRepository;
use AMovil\Reports\ReportLog\Services\SaveReportLog;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Ramsey\Uuid\Uuid;
use ZipArchive;

class ExportMontoDevolucion
{
    private $repo;
    private $exportService;
    private $saveReportLog;
    private $storage_path;

    public function __construct(ExtraccionRepository $repo, ExportService $exportService, SaveReportLog $saveReportLog)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
        $this->saveReportLog = $saveReportLog;
        $this->storage_path = storage_path('app/public');
    }

    public function __invoke($ticketOsiptel, $fechaInteres, $corteFechaIni)
    {
        $dtStart = new DateTime();
        try {
            //$dtFechaInteres = DateTime::createFromFormat("Y-m-d", $fechaInteres);
            $dtFechaInteres = new DateTime();
            $dtFechaInteres->modify("+{$fechaInteres} month");
            $dtCorteFechaIni = DateTime::createFromFormat("Y-m-d H:i:s", $corteFechaIni);

            $this->repo->getReporteMontoDevolver($dtFechaInteres, $dtCorteFechaIni);

            $options = [
                "sheetIndex" => 0,
                'title' => "",
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

            $headers = [
                "ticket" => ["label" => "TICKET"],
                "id_cliente" => ["label" => "ID_CLIENTE"],
                "nombres" => ["label" => "NOMBRES"],
                "apellidos" => ["label" => "APELLIDOS"],
                "msisdn" => ["label" => "MSISDN"],
                "tipo_documento" => ["label" => "TIPO_DOCUMENTO"],
                "nro_documento" => ["label" => "NRO_DOCUMENTO"],
                "modalidad_dev" => ["label" => "MODALIDAD_DEV"],
                "departamento" => ["label" => "DEPARTAMENTO"],
                "estado_cliente" => ["label" => "ESTADO_CLIENTE"],
                "fecha_baja" => ["label" => "FECHA_BAJA"],
            ];
            // $options["sheetIndex"]++;
            $options["title"] = $ticketOsiptel;
            $baseAbonados = $this->repo->getBaseAbonados();
            $this->exportService->loadData($headers, $baseAbonados, $options);

            $baseAbonadosTempFilename = "{$this->storage_path}/".Uuid::uuid4()->toString().".xlsx";
            $this->exportService->getWriter(WriterType::XLSX)->save($baseAbonadosTempFilename);
            
            $headers = [
                "ticket" => ["label" => "TICKET"],
                "msisdn" => ["label" => "MSISDN"],
                "msisdn_devol" => ["label" => "MSISDN_DEVOL"],
                "cargo_linea" => ["label" => "CARGO_LINEA"],
                "cargo_linea_igv" => ["label" => "CARGO_LINEA_IGV"],
                "mto_dev" => ["label" => "MTO_DEV"],
                "mto_dev_igv" => ["label" => "MTO_DEV_IGV"],
                "interes" => ["label" => "INTERES"],
                "tasa" => ["label" => "TASA"],
                "mto_total_dev_igv" => ["label" => "MTO_TOTAL_DEV_IGV"],
                "custcode" => ["label" => "CUSTCODE"],
                "customer_id" => ["label" => "CUSTOMER_ID"],
                "co_id_devolver" => ["label" => "CO_ID_DEVOLVER"],
                "ciclofacturacion" => ["label" => "CICLOFACTURACION"],
                "fuente" => ["label" => "FUENTE"],
                "fch_activacion" => ["label" => "FCH_ACTIVACION"],
                "fecha_activacion" => ["label" => "FECHA_ACTIVACION"],
                "glosa" => ["label" => "GLOSA"],
            ];
            // $options["sheetIndex"]++;
            // $options["title"] = $ticketOsiptel;
            $baseDevolucionPostpagoTempFilename = "{$this->storage_path}/".Uuid::uuid4()->toString().".xlsx";
            $baseDevolucionPostpago = $this->repo->getBaseDevolucionPostpago($dtCorteFechaIni);
            $this->exportService->reset();
            $this->exportService->loadData($headers, $baseDevolucionPostpago, $options);
            $this->exportService->getWriter(WriterType::XLSX)->save($baseDevolucionPostpagoTempFilename);

            $headers = [
                "msisdn" => ["label" => "MSISDN"],
                "msisdn_devol" => ["label" => "MSISDN_DEVOL"],
                "centimos" => ["label" => "CENTIMOS"],
                "glosa" => ["label" => "GLOSA"],
            ];
            // $options["title"] = $ticketOsiptel;
            // $baseDevolucionPrepagoTempFilename = "{$this->storage_path}/".Uuid::uuid4()->toString().".xlsx";
            $baseDevolucionPrepago = $this->repo->getBaseDevolucionPrepago($dtCorteFechaIni);
            $this->exportService->reset();
            $this->exportService->loadData($headers, $baseDevolucionPrepago, $options);
            $baseDevolucionPrepagoContent = $this->exportService->getWriter(WriterType::CSV)->getOutput();

            $zip = new ZipArchive();
            $zipFilename = "{$this->storage_path}/".Uuid::uuid4()->toString().".zip";
            $zip->open($zipFilename, ZipArchive::CREATE);
            $zip->addFile($baseAbonadosTempFilename, "Base_Abonados_Web_Movil_TK{$ticketOsiptel}.xlsx");
            $zip->addFile($baseDevolucionPostpagoTempFilename, "Base_Devolucion_Postpago_TK{$ticketOsiptel}.xlsx");
            // $zip->addFile($baseDevolucionPrepagoTempFilename, "Base_Devolucion_Prepago_TK{$ticketOsiptel}.xlsx");
            $zip->addFromString("Base_Devolucion_Prepago_TK{$ticketOsiptel}.tsv", str_replace(['"',","], ['','|'], $baseDevolucionPrepagoContent));
            $zip->close();

            $this->repo->saveResultsInLog("MONTO_DEVOLVER_".$dtStart->format('YmdHis').".zip", $dtCorteFechaIni);
            $this->reportLog($zipFilename, $dtStart, new DateTime());

            $export_content = file_get_contents($zipFilename);
            unlink($zipFilename);
            unlink($baseAbonadosTempFilename);
            unlink($baseDevolucionPostpagoTempFilename);
            // sunlink($baseDevolucionPrepagoTempFilename);
            
            return new Response([], [
                'content' => $export_content,
                'type' => 'zip',
                'filename' => "{$ticketOsiptel}.zip"
            ]);   
        } catch (\Throwable $th) {
            $this->reportLog(null, $dtStart, new DateTime(), ['mensaje' => $th->getMessage()]);
            throw $th;
        }
    }

    private function reportLog($allFilename, DateTime $ini, DateTime $fin, array $extra_data = [])
    {
        $filename = "MONTO_DEVOLVER_".$ini->format('YmdHis').".zip";
        $data = array_merge([
            'name' => 'MONTO_DEVOLVER',
            'ini' => $ini->format('Y-m-d H:i:s'),
            'fin' => $fin->format('Y-m-d H:i:s'),
            'trac_name' => 'extraccion-devolucion',
            "filename" => $allFilename !== null ? $filename : null
        ], $extra_data);
        $this->saveReportLog->__invoke($data, $allFilename, 'MONTO_DEVOLVER');
    }
}
