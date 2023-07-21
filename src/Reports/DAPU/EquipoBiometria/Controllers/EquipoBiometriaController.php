<?php

namespace AMovil\Reports\DAPU\EquipoBiometria\Controllers;

use AMovil\Reports\DAPU\EquipoBiometria\Services\ExportEquipoBiometria;
use AMovil\Reports\DAPU\EquipoBiometria\Services\GetEquipoBiometria;
use Illuminate\Http\Request;

class EquipoBiometriaController
{
    private $get;
    private $export;

    public function __construct(GetEquipoBiometria $get, ExportEquipoBiometria $export)
    {
        $this->get = $get;
        $this->export = $export;
    }

    public function view()
    {
        $config = [
            "title" => "EQUIPO DE BIOMETRIA",
            "url" => asset("dapu/equipo-biometria/json"),
            "url_export" => asset("dapu/equipo-biometria/export"),
            "fields" => [
                "biometric_date" => ["label" => "BIOMETRIC_DATE"],
                "phone" => ["label" => "PHONE"],
                "iccid" => ["label" => "ICCID"],
                "le" => ["label" => "LE"],
                "agreement_mode_desc" => ["label" => "AGREEMENT_MODE_DESC"],
                "system_code" => ["label" => "SYSTEM_CODE"],
                "operation_type_sc" => ["label" => "OPERATION_TYPE_SC"],
                "operation_type_desc" => ["label" => "OPERATION_TYPE_DESC"],
                "validation_type" => ["label" => "VALIDATION_TYPE"],
                "biometric_pdv_desc" => ["label" => "BIOMETRIC_PDV_DESC"],
                "biometric_pdv_channel_desc" => ["label" => "BIOMETRIC_PDV_CHANNEL_DESC"],
                "is_mobile_desc" => ["label" => "IS_MOBILE_DESC"],
                "customer_document_type_desc" => ["label" => "CUSTOMER_DOCUMENT_TYPE_DESC"],
                "customer_id_card_value" => ["label" => "CUSTOMER_ID_CARD_VALUE"],
            ],
            "form_view" => "dapu.equipo_biometria_form",
        ];
        return view("dapu.shared", compact("config"));
    }

    public function getData(Request $request)
    {
        $response = $this->get->__invoke(
            $request->input('dni'),
            $request->input('fono'),
            $request->input('periodo')
        )->data();
        return response()->json($response);
    }

    public function export(Request $request)
    {
        $response = $this->export->__invoke(
            $request->input('type'),
            $request->input('dni'),
            $request->input('fono'),
            $request->input('periodo'),
        )->data();

        $headers_type = [
            'csv' => [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment;filename="'.$response['filename'].'"'
            ],
            'xlsx' => [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment;filename="'.$response['filename'].'"'
            ],
        ];

        $headers = $headers_type[$response['type']];
        
        return response($response['content'], 200, $headers);
    }
}
