<?php

namespace AMovil\Reports\DetallePlanes\Planes\Infrastructure;

use AMovil\Reports\DetallePlanes\Planes\Domain\DetallePlanRepository;
use Illuminate\Support\Facades\DB;

class EloquentDetallePlanRepository implements DetallePlanRepository
{
    private function getBuilder()
    {
        return DB::table("DWA.DW_M_SUBSCRIPTION A")
        ->selectRaw("SUBSTR(A.SUBSCRIPTION_ACCESS_NUMBER,3,9) LINEA,
        A.AGREEMENT_CONTRACT_NUMBER COID,
        A.CUSTOMER_ACCOUNT_SC CUSTOMER_ID,
        A.CUSTOMER_ACCOUNT_DESC CUENTA,
        A.CUSTOMER_ACCOUNT_BILLING_CYCLE_SC CICLO,
        A.ID_CARD_VALUE RUC,
        A.CUSTOMER_SOCIAL_REASON_NAME RAZON_SOCIAL,
        TO_CHAR(A.CUSTOMER_ACCOUNT_ACTIVATION_DATE, 'DD/MM/YYYY') FEC_ACTI_CTA,
        A.AGREEMENT_MODE MODALIDAD,
        A.SUBSCRIPTION_STATUS EST_LINEA,
        A.AGREEMENT_PRODUCT_OFFERING_SC COD_PLAN,
        A.AGREEMENT_PRODUCT_OFFERING_DESC PLAN")
        ->where(function($query) {
            $query->whereRaw("A.AGREEMENT_STATUS<>'D'
            AND SUBSTR(A.ID_CARD_VALUE,1,2) IN ('10','20')
            AND LENGTH(A.ID_CARD_VALUE) IN (11)
            AND A.AGREEMENT_MODE IN ('POSTPAGO')");
        });
    }

    public function getByNumDocumento(string $numDocumento)
    {
        return $this->getBuilder()
        ->where("A.ID_CARD_VALUE", $numDocumento)
        ->get();
    }

    public function getByNumCuenta(string $numCuenta)
    {
        return $this->getBuilder()
        ->where("A.CUSTOMER_ACCOUNT_DESC", $numCuenta)
        ->get();
    }

    public function getByLineas(array $lineas)
    {
        return $this->getBuilder()
        ->whereIn(DB::raw("SUBSTR(A.SUBSCRIPTION_ACCESS_NUMBER,3,9)"), $lineas)
        ->get();
    }

    public function getTiposInput()
    {
        $data = [];
        $data[] = ["id" => 1, "label" => "NUMERO DE DOCUMENTO"];
        $data[] = ["id" => 2, "label" => "NUMERO DE CUENTA"];
        $data[] = ["id" => 3, "label" => "LINEAS SEPARADAS POR COMAS"];
        $data[] = ["id" => 4, "label" => "EXCEL"];
        return json_decode(json_encode($data), false);
    }
}
