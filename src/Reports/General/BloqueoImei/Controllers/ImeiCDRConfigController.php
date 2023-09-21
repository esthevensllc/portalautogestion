<?php

namespace AMovil\Reports\General\BloqueoImei\Controllers;

use AMovil\Reports\General\BloqueoImei\Services\GetImeiCDRConfig;
use AMovil\Reports\General\BloqueoImei\Services\SaveImeiCDRConfig;
use Illuminate\Http\Request;

class ImeiCDRConfigController
{
    private $getConfig;
    private $save;

    public function __construct(GetImeiCDRConfig $getConfig, SaveImeiCDRConfig $save)
    {
        $this->getConfig = $getConfig;
        $this->save = $save;
    }

    public function view()
    {
        $resp = $this->getConfig->__invoke()->data();
        $config = [
            'title' => 'IMEIs CDR Config',
            'url' => url('imei-cdr-automatico/config'),
            'config' => $resp,
        ];
        return view("imei_cdr.imei_cdr_config", compact("config"));
    }

    public function saveConfig(Request $request)
    {
        $this->save->__invoke($request->input("generate_report"));
        return response()->json([]);
    }
}
