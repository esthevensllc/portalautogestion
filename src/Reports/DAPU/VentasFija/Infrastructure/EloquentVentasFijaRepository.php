<?php

namespace AMovil\Reports\DAPU\VentasFija\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\DAPU\VentasFija\Domain\VentasFijaRepository;
use Exception;
use Illuminate\Support\Facades\DB;

class EloquentVentasFijaRepository implements VentasFijaRepository
{
    private $authService;
    private $userIdentifier;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function getByNumDocumento(array $values) {
        return $this->getBy("num_documento", $values);
    }

    public function getBySOT(array $values) {
        return $this->getBy("sot", $values);
    }

    public function getBy(string $field, array $values)
    {
        $this->userIdentifier = $this->authService->getUserIdentifier();
        DB::statement("BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.DAPU_LINEA_DNI_INPUT_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;");
        DB::statement("CREATE TABLE USRAES.DAPU_LINEA_DNI_INPUT_{$this->userIdentifier}(value varchar2(100))");

        foreach($values as $value){
            DB::table("USRAES.DAPU_LINEA_DNI_INPUT_{$this->userIdentifier}")->insert(["value" => $value]);
        }

        $queryFilter = null;
        if($field === "num_documento") {
            $queryFilter = "B.CONTV_NRO_DOC_CLIENTE IN (SELECT value FROM USRAES.DAPU_LINEA_DNI_INPUT_{$this->userIdentifier})";
        } else if($field === "sot") {
            $queryFilter = "NRO_SOT IN (SELECT value FROM USRAES.DAPU_LINEA_DNI_INPUT_{$this->userIdentifier})";
        } else {
            throw new Exception("El campo a filtrar no es valido");
        }

        $query = "SELECT /*+ PARALLEL(8) */
        DISTINCT B.CONTD_FECHA_CONTRATO,
        DN.DN_NUM,
        B.CONTN_NUMERO_CONTRATO,
        B.CONTN_NUMERO_SEC,
        EE.DESCRIPCION ESTADO_DESC,
        A.PAQUETE,
        B.CONTC_OFICINA_VENTA,
        PD.PDV_CANAL,
        PD.PDV_DES,
        PD.PDV_RAZON_SOCIAL,
        M.TDOCV_DESCRIPCION TIPO_DOC_CLIENTE,
        B.CONTV_NRO_DOC_CLIENTE,
        NVL(B.CONTV_NOMBRE||' '||B.CONTV_APE_PAT||' '||B.CONTV_APE_MAT, B.CONTV_RAZONSOCIAL) NOMBRE_RAZON_SOCIAL_CLIENTE,
        B.CONTV_CODIGO_VENDEDOR,
        B.CONTV_VENDEDOR,
        S.CODSOLOT NRO_SOT,
        CC.NOMBRE CONTRATA,
        S.DIRECCION
        FROM DWS.SA_SISACT_SOLICITUD_PLAN_HFC A
        LEFT JOIN DWS.SA_SISACT_AP_CONTRATO B ON B.CONTN_NUMERO_SEC = A.SOLIN_CODIGO
        LEFT JOIN DWS.SA_SISACT_AP_CONTRATO_DET B2 ON B2.ID_CONTRATO = B.CONTN_NUMERO_CONTRATO
        LEFT JOIN DWS.SA_SISACT_INFO_VENTA_SGA C ON C.ID_CONTRATO = B.CONTN_NUMERO_CONTRATO
        LEFT JOIN DWS.SA_SOLOT S ON S.CODSOLOT = C.NRO_SOT
        LEFT JOIN DWS.SA_ESTSOL EE ON EE.ESTSOL = S.ESTSOL
        LEFT JOIN DMRED.CONTR_SERVICES_CAP CSC ON CSC.CO_ID = B2.CO_ID
        LEFT JOIN DMRED.DIRECTORY_NUMBER DN ON DN.DN_ID = CSC.DN_ID
        LEFT JOIN DWS.SA_SECT_TIPO_DOCUMENTO M ON B.CONTC_TIPO_DOC_CLIENTE = M.TDOCC_CODIGO
        LEFT JOIN DWS.SA_SECT_DIRECCION EE ON A.SOLIN_CODIGO = EE.SOLIN_CODIGO -- correo
        LEFT JOIN DWA.AYF_RTM_BASE_PDV PD ON B.CONTC_OFICINA_VENTA = PD.PDV_CODIGO
        LEFT JOIN DWS.SA_AGENDAMIENTO AA ON S.CODSOLOT = AA.CODSOLOT
        LEFT JOIN DWS.SA_CONTRATA CC ON AA.CODCON = CC.CODCON
        WHERE {$queryFilter}";

        $data = DB::select(DB::raw($query));

        DB::statement("BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.DAPU_LINEA_DNI_INPUT_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;");

        return $data;
    }
}
