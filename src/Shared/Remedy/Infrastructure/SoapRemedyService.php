<?php

namespace AMovil\Shared\Remedy\Infrastructure;

use AMovil\Shared\Remedy\Domain\RemedyService;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;

class SoapRemedyService implements RemedyService
{
    private Client $httpClient;
    public function __construct()
    {
        $this->httpClient = new Client(["base_uri" => env("REMEDY_API")]);
    }

    public function createIncidence($values)
    {
        $xmlRequest = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:HPD_IncidentInterface_Create_WS1">
        <soapenv:Header>
        <urn:AuthenticationInfo>
        <urn:userName>C19884</urn:userName>
        <urn:password>$$02Trabajo</urn:password>
        </urn:AuthenticationInfo>
        </soapenv:Header>
        <soapenv:Body>
        <urn:HelpDesk_Submit_Service>
        <urn:Assigned_Group>BACK OFFICE PROVISIÓN-MEDIACIÓN</urn:Assigned_Group>
        <urn:Assigned_Support_Company>América Móvil Perú SAC</urn:Assigned_Support_Company>
        <urn:Assigned_Support_Organizationt>TECNOLOGIA</urn:Assigned_Support_Organizationt>
        <urn:Assignee>EDWARD R GRANADOS QUISURUCO</urn:Assignee>
        <urn:Categorization_Tier_1>APLICACIONES DE NEGOCIO</urn:Categorization_Tier_1>
        <urn:Categorization_Tier_2>SOPORTE DE LINEAS.PREPAGO</urn:Categorization_Tier_2>
        <urn:Categorization_Tier_3>INCONVENIENTE CON APROVISIONAMIENTO DE LINEA</urn:Categorization_Tier_3>
        <urn:CI_Name>APLICACION DE NEGOCIO</urn:CI_Name>
        <urn:Department>América Móvil Perú SAC</urn:Department>
        <urn:First_Name>JOEL MALLQUI TABOADA</urn:First_Name>
        <urn:Impact>1000</urn:Impact>
        <urn:Last_Name>MALLQUI TABOADA</urn:Last_Name>
        <urn:Product_Categorization_Tier_1>MOVIL</urn:Product_Categorization_Tier_1>
        <urn:Product_Categorization_Tier_2>PREPAGO</urn:Product_Categorization_Tier_2>
        <urn:Product_Categorization_Tier_3>RECARGA</urn:Product_Categorization_Tier_3>
        <urn:Reported_Source>Evento de NSN NAS</urn:Reported_Source>
        <urn:Service_Type>3</urn:Service_Type>
        <urn:Status>1</urn:Status>
        <urn:Action>CREATE</urn:Action>
        <urn:Summary>'. $values["summary"] .'</urn:Summary>
        <urn:Notes>CARGAR EL LOG DE LA TRAMA RECARGA</urn:Notes>
        <urn:Urgency>2000</urn:Urgency>
        <urn:ServiceCI>TI_Prepago</urn:ServiceCI>
        <urn:Login_ID>C19884</urn:Login_ID>
        </urn:HelpDesk_Submit_Service>
        </soapenv:Body>
        </soapenv:Envelope>';

        $xmlElement = new SimpleXMLElement($xmlRequest);
        // dd($xmlElement->asXML());

        $url = env("REMEDY_API")."/arsys/services/ARService?server=172.17.51.29&webService=HPD_IncidentInterface_Create_WS1";

        $options = [
            "headers" => [
                "Content-Type" => "text/xml",
                "SOAPAction" => '"#POST"',
            ],
            "body" => $xmlRequest
        ];

        $response = $this->httpClient->request("POST", "/arsys/services/ARService?server=172.17.51.29&webService=HPD_IncidentInterface_Create_WS1", $options);

        $xmlResponse = null;
        $incident = null;
        if($response->getStatusCode() === 200){
            $xmlResponse = $response->getBody()->getContents();
            $incident = $this->getIncidenceFromXml($xmlResponse);
            return [
                "incident" => $incident, 
                "body" => $xmlResponse
            ];
        }else{
            $xmlResponse = $response->getBody()->getContents();
            throw new Exception($xmlResponse);
        }
    }

    private function getIncidenceFromXml(string $response)
    {
        $strIni = "<ns0:Incident_Number>";
        $strEnd = "</ns0:Incident_Number>";
        $incident = str_replace("\r\n", "", substr($response, strpos($response, $strIni)+strlen($strIni)));
        $end = strpos($incident, $strEnd);
        $incident = substr($incident, 0, $end);
        return $incident;
    }
}
