<?php

namespace AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Services;

use AMovil\Reports\ExtraccionDevFija\ExtraccionDevFija\Domain\ExtraccionDevFijaRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Ramsey\Uuid\Uuid;
use ZipArchive;

class ExportExtraccionFijaPostpago
{
    private $repo;
    private $exportService;

    public function __construct(ExtraccionDevFijaRepository $repo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
    }

    public function __invoke($ticket)
    {
        $fuentes = $this->repo->getFuentesReportePostpago($ticket);
        $response = null;
        if(count($fuentes) > 1){
            $zip = new ZipArchive();
            $zipTempfilename = $this->getTempfilename();
            $tempfiles = [$zipTempfilename];
            $zip->open($zipTempfilename, ZipArchive::CREATE);
            foreach($fuentes as $row){
                $data = $this->repo->getReportePostpago($ticket, $row->fuente);
                $tempfile = $this->getExportTempfile($data);
                $zip->addFile($tempfile, "FIJA_POSTPAGO_{$row->fuente}.xlsx");
                $tempfiles[] = $tempfile;
            }
            $zip->close();
            $content = file_get_contents($zipTempfilename);
            foreach($tempfiles as $file){
                unlink($file);
            }
            $response = Response::respData([
                "filename" => "FIJA_POSTPAGO.zip",
                "type" => "zip",
                "content" => $content
            ]);
        } else {
            $fuente = null;
            $data = [];
            if(count($fuentes) === 1) {
                $fuente = $fuentes[0]->fuente;
                $data = $this->repo->getReportePostpago($ticket, $fuente);
            }
            $tempfile = $this->getExportTempfile($data);
            $content = file_get_contents($tempfile);
            unlink($tempfile);
            $response = Response::respData([
                "filename" => "FIJA_POSTPAGO.xlsx",
                "type" => "xlsx",
                "content" => $content
            ]);
        }
        return $response;
    }

    private function getExportTempfile($data)
    {
        $headers = [
            "ticket" => ["label" => "TICKET"],
            "msisdn" => ["label" => "MSISDN"],
            "msisdn_devolver" => ["label" => "MSISDN_DEVOLVER"],
            "cargo_linea" => ["label" => "CARGO_LINEA"],
            "cargo_linea_igv" => ["label" => "CARGO_LINEA_IGV"],
            "mto_dev" => ["label" => "MTO_DEV"],
            "mto_dev_igv" => ["label" => "MTO_DEV_IGV"],
            "interes" => ["label" => "INTERES"],
            "tasa" => ["label" => "TASA"],
            "mto_total_dev_igv" => ["label" => "MTO_TOTAL_DEV_IGV"],
            "custcode" => ["label" => "CUSTCODE"],
            "customer_id" => ["label" => "CUSTOMER_ID"],
            "idinstprod" => ["label" => "IDINSTPROD"],
            "co_id_devolver" => ["label" => "CO_ID_DEVOLVER"],
            "ciclofacturacion" => ["label" => "CICLOFACTURACION"],
            "fuente" => ["label" => "FUENTE"],
            "fecha_alta" => ["label" => "FECHA_ALTA"],
            "fecha_activacion" => ["label" => "FECHA_ACTIVACION"],
            "glosario" => ["label" => "GLOSARIO"],
        ];
        $options = [
            "sheetIndex" => 0,
            'title' => "FIJA_POSTPAGO",
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
        $this->exportService->reset();
        $this->exportService->loadData($headers, $data, $options);
        return $this->exportService->getWriter(WriterType::XLSX)->saveToTempfile();
    }

    private function getTempfilename(){
        $filename = sys_get_temp_dir() ."/phpexport-". Uuid::uuid4()->toString().".tmp";
        return $filename;
    }
}
