<?php

namespace AMovil\Reports\OSINFOR\Services;

use AMovil\Reports\OSINFOR\Domain\OSINFORRepository;
use AMovil\Shared\Application\Response;
use AMovil\Shared\Exports\Domain\ExportService;
use AMovil\Shared\Exports\Domain\WriterType;
use DateTime;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ExportOSINFORReport
{
    public const CODIGO_CLIENTE = '56776408';

    private const INVALID_RANGE_MESSAGE = "El a\u{00f1}o y/o semana ingresado no existe en la tabla, por favor tener en consideraci\u{00f3}n la fecha m\u{00ed}nima y m\u{00e1}xima";

    private OSINFORRepository $repo;
    private ExportService $exportService;

    public function __construct(OSINFORRepository $repo, ExportService $exportService)
    {
        $this->repo = $repo;
        $this->exportService = $exportService;
    }

    public function getAvailableRange(): array
    {
        return $this->repo->getAvailableRange(self::CODIGO_CLIENTE);
    }

    public function __invoke(string $anio, string $semana): Response
    {
        $anio = trim($anio);
        $semana = trim($semana);

        if (!$this->isValidYear($anio) || !$this->isValidWeek($semana)) {
            return Response::respError([
                'message' => self::INVALID_RANGE_MESSAGE,
            ]);
        }

        if (!$this->repo->existsByYearAndWeek(self::CODIGO_CLIENTE, $anio, $semana)) {
            return Response::respError([
                'message' => self::INVALID_RANGE_MESSAGE,
            ]);
        }

        $data = $this->repo->getReporte(self::CODIGO_CLIENTE, $anio, $semana);
        $headers = [
            'CODIGO_CLIENTE' => ['label' => 'CODIGO_CLIENTE'],
            'y' => ['label' => 'y'],
            'semana' => ['label' => 'semana'],
            'tipo' => ['label' => 'tipo'],
            'trafico_mb' => ['label' => 'trafico_mb'],
        ];

        $this->exportService->loadData($headers, $data, [
            'rowType' => 'array',
            'sheetIndex' => 0,
            'title' => 'OSINFOR',
            'styles' => [
                'header' => [
                    'font' => ['bold' => true, 'size' => 9],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ],
                'body' => [
                    'font' => ['size' => 9],
                ],
            ],
        ]);

        $content = $this->exportService->getWriter(WriterType::XLSX)->getOutput();
        $strNow = (new DateTime())->format('YmdHis');

        return Response::respData([
            'filename' => "OSINFOR_{$strNow}.xlsx",
            'type' => 'xlsx',
            'content' => $content,
        ]);
    }

    private function isValidYear(string $anio): bool
    {
        return preg_match('/^\d{4}$/', $anio) === 1;
    }

    private function isValidWeek(string $semana): bool
    {
        if (preg_match('/^\d{1,2}$/', $semana) !== 1) {
            return false;
        }

        $week = (int) $semana;
        return $week >= 1 && $week <= 53;
    }
}
