<?php

namespace AMovil\Reports\BloqueoControlReg\Services;

use AMovil\Reports\BloqueoControlReg\Domain\BloqueoControlRegLogRepository;
use AMovil\Reports\BloqueoControlReg\Domain\BloqueoControlRegRepository;
use AMovil\Shared\Application\Response;
use Exception;

class DownloadBloqueoDocument
{
    private $repo;
    private $logRepo;

    public function __construct(
        BloqueoControlRegRepository $repo,
        BloqueoControlRegLogRepository $logRepo
    ) {
        $this->repo = $repo;
        $this->logRepo = $logRepo;
    }

    public function __invoke($id)
    {
        $report = $this->repo->findReporteDAPU($id);
        // dd($report);
        if($report === null){
            throw new Exception("El archivo no existe");
        }
        $documentos = $this->logRepo->getByCriteria([["id", $id]]);

        $content = base64_decode($report->filecontent);
        return Response::respData([
            "filename" => $documentos[0]->filename,
            "content" => $content
        ]);
    }
}
