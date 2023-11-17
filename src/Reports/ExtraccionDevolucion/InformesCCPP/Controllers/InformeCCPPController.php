<?php

namespace AMovil\Reports\ExtraccionDevolucion\InformesCCPP\Controllers;

use AMovil\Reports\ExtraccionDevolucion\InformesCCPP\Services\DownloadInformeCCPP;
use AMovil\Reports\ExtraccionDevolucion\InformesCCPP\Services\InformeCCPPFinder;
use AMovil\Reports\ExtraccionDevolucion\InformesCCPP\Services\LoadInformeCCPP;
use AMovil\Shared\Application\FileInput;
use Illuminate\Http\Request;

class InformeCCPPController
{
    private $importer;
    private $finder;
    private $downloader;

    public function __construct(
        LoadInformeCCPP $importer,
        InformeCCPPFinder $finder,
        DownloadInformeCCPP $downloader
    ){
        $this->importer = $importer;
        $this->finder = $finder;
        $this->downloader = $downloader;
    }

    public function view()
    {
        $config = [
            'title' => 'Informes de CCPP',
            'getApi' => url('extraccion-devolucion/informes-ccpp/search'),
            'importApi' => url('extraccion-devolucion/informes-ccpp/import'),
            'downloadApi' => url('extraccion-devolucion/informes-ccpp/[id]/download'),
        ];
        return view("extraccion_devolucion.informes_ccpp", compact("config"));
    }

    public function import(Request $request)
    {
        $file = new FileInput(
            $request->file("archivo")->getPathname(),
            $request->file("archivo")->getClientOriginalName()
        );
        $this->importer->__invoke($file);
        return response()->json([]);
    }

    public function search()
    {
        $data = $this->finder->get();
        return response()->json(["data" => $data]);
    }

    public function download($id){
        $response = $this->downloader->__invoke($id)->data();
        return response($response["content"], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment;filename="'. $response["filename"] .'"'
        ]);
    }
}
