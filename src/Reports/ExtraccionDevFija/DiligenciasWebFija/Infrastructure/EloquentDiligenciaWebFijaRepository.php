<?php

namespace AMovil\Reports\ExtraccionDevFija\DiligenciasWebFija\Infrastructure;

use AMovil\Reports\ExtraccionDevFija\DiligenciasWebFija\Domain\DiligenciaWebFijaRepository;
use Illuminate\Support\Facades\DB;

class EloquentDiligenciaWebFijaRepository implements DiligenciaWebFijaRepository
{
    public function getReporteDiligenciasWebCorreo($tickets) {
        $ticketBinds = $this->getQueryBinds($tickets);

        return DB::select(DB::raw("SELECT
        /*+PARALLEL(16)*/ Y.TICKET, Y.NOMCLI, Y.NRO_DOC,
        CASE WHEN X.CUSTOMER_ACCOUNT_BILLING_EMAIL IS NULL THEN X.CUSTOMER_EMAIL ELSE X.CUSTOMER_ACCOUNT_BILLING_EMAIL END AS CORREO
        FROM
        (
            SELECT ID_CARD_VALUE,CUSTOMER_ACCOUNT_BILLING_EMAIL, CUSTOMER_EMAIL
            ,ROW_NUMBER() OVER (PARTITION BY ID_CARD_VALUE ORDER BY CASE WHEN AGREEMENT_STATUS='A' THEN 1 ELSE 0 END DESC, CUSTOMER_ACCOUNT_ID DESC) ORDEN
            FROM
            (
                SELECT ID_CARD_VALUE,CUSTOMER_ACCOUNT_BILLING_EMAIL, CASE WHEN CUSTOMER_EMAIL='@iclaro.com.pe' THEN NULL ELSE CUSTOMER_EMAIL end CUSTOMER_EMAIL
                ,AGREEMENT_STATUS,CUSTOMER_ACCOUNT_ID
                FROM DWA.DW_M_SUBSCRIPTION WHERE ID_CARD_VALUE IN (
                    SELECT NRO_DOC FROM USRAES.DWH_DEVOLUCION_MASIV_DETALLE_HIST
                    where ticket in ({$ticketBinds['str_binds']})
                )
                AND (CUSTOMER_ACCOUNT_BILLING_EMAIL IS NOT NULL OR CASE WHEN CUSTOMER_EMAIL='@iclaro.com.pe' THEN NULL ELSE CUSTOMER_EMAIL end IS NOT NULL)
            ) X
        ) X
        RIGHT JOIN USRAES.DWH_DEVOLUCION_MASIV_DETALLE_HIST Y
        ON X.ID_CARD_VALUE=Y.NRO_DOC AND X.ORDEN=1
        WHERE COMENTARIOS = 'DEVOLUCION WEB'
        and y.ticket in ({$ticketBinds['str_binds']})
        ORDER BY y.TICKET"), $ticketBinds['values']);
    }

    public function getReporteDiligenciasWebDocumento($tickets) {
        $ticketBinds = $this->getQueryBinds($tickets);

        return DB::select(DB::raw("SELECT
        TICKET, NOMCLI, TIPDOC, NRO_DOC, ROUND(ROUND(MONTO_PRINCIPAL * 1.18, 2) + INTERES, 2) MTO_TOTAL_DEV_IGV
        FROM USRAES.DWH_DEVOLUCION_MASIV_DETALLE_HIST
        WHERE COMENTARIOS = 'DEVOLUCION WEB' and ticket in ({$ticketBinds['str_binds']})
        ORDER BY TICKET, NOMCLI, TIPDOC, NRO_DOC"), $ticketBinds['values']);
    }

    public function getTicketsWithDevolucionWeb($tickets) {
        $strBindsOracle = [];
        $ticketValues = [];
        foreach($tickets as $index => $ticket){
            $strBindsOracle[] = ":ticket_{$index}";
            $ticketValues["ticket_{$index}"] = $ticket;
        }
        $strBindsOracle = implode(",", $strBindsOracle);

        $result = DB::select(DB::raw("SELECT A.TICKET,B.TIPO_REPORTE FROM usraes.noc_informe_de_fallas A
        JOIN USRAES.BASE_EXT_DEV_INPUT@DBL_REPTDM B
        ON A.NUMERO_DE_REPORTE=B.NUM_REPORTE
        WHERE A.TICKET in ({$strBindsOracle}) and B.TIPO_REPORTE = 3"), $ticketValues);

        $resultMapped = [];
        foreach($result as $row){
            $resultMapped[] = $row->ticket;
        }
        return $resultMapped;
    }

    private function getQueryBinds(array $tickets){
        $str_binds = [];
        $bind_values = [];
        foreach($tickets as $index => $value){
            $str_binds[] = ":p_ticket_{$index}";
            $bind_values["p_ticket_{$index}"] = $value;
        }
        $str_binds = implode(", ", $str_binds);
        return [
            "str_binds" => $str_binds,
            "values" => $bind_values
        ];
    }
}