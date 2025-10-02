<?php

namespace AMovil\Reports\DAPU\Lineas\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\DAPU\Lineas\Domain\LineaRepository;
use Illuminate\Support\Facades\DB;

class EloquentLineaRepository implements LineaRepository
{
    private $authService;
    private $userIdentifier;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function getUsuariosByLinea(array $values){
        return $this->getUsuariosBy("msisdn", $values);
    }

    public function getUsuariosByDni(array $values){
        return $this->getUsuariosBy("dni", $values);
    }
    
    public function getUsuariosBy(string $field, array $values)
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
        if($field === "msisdn"){
            $queryFilter = "S.SUBSCRIPTION_ACCESS_NUMBER IN (SELECT value FROM USRAES.DAPU_LINEA_DNI_INPUT_{$this->userIdentifier})";
        } else {
            // $queryFilter = "REGEXP_REPLACE(S.ID_CARD_VALUE, '^0+', '') IN (SELECT REGEXP_REPLACE(value, '^0+', '') FROM USRAES.DAPU_LINEA_DNI_INPUT_{$this->userIdentifier})";
            $queryFilter = "S.ID_CARD_VALUE IN (SELECT value FROM USRAES.DAPU_LINEA_DNI_INPUT_{$this->userIdentifier})";
        }

        $data = DB::select(DB::raw("SELECT
        /*+ PARALLEL(8) */S.SUBSCRIPTION_ACCESS_NUMBER AS LINEA,
        S.AGREEMENT_PRODUCT_OFFERING_DESC PRODUCTO,
        S.ID_CARD_TYPE_VALUE AS TIPO_DOC,
        S.ID_CARD_VALUE AS NRO_DOC,
        S.CUSTOMER_FULL_NAME AS NOMBRE,
        NVL(S.CUSTOMER_EMAIL, S.CUSTOMER_ACCOUNT_BILLING_EMAIL) AS MAIL,
        S.AGREEMENT_MODE MODALIDAD,
        S.AGREEMENT_SERVICE_GROUP TIPO,
        S.SUBSCRIPTION_STATUS AS ESTADO,
        S.AGREEMENT_REASON_STATUS_DESC AS MOTIVO_ESTADO,
        S.SUBSCRIPTION_START_DATE F_INICIO,
        S.SUBSCRIPTION_END_DATE F_FIN,
        S.CUSTOMER_ACCOUNT_BILLING_ADDRESS DIRECCION,
        S.CUSTOMER_ACCOUNT_BILLING_DEPARTMENT DEPARTAMENTO,
        S.CUSTOMER_ACCOUNT_BILLING_PROVINCE PROVINCIA,
        S.CUSTOMER_ACCOUNT_BILLING_DISTRICT DISTRITO
        FROM DWA.DW_M_SUBSCRIPTION S
        WHERE {$queryFilter}
        ORDER BY S.SUBSCRIPTION_START_DATE"));

        DB::statement("BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE USRAES.DAPU_LINEA_DNI_INPUT_{$this->userIdentifier}';
        EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -942 THEN RAISE; END IF;
        END;");

        return $data;
    }
}
