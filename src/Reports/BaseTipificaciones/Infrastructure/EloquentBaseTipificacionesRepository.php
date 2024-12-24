<?php

namespace AMovil\Reports\BaseTipificaciones\Infrastructure;

use AMovil\Reports\BaseTipificaciones\Domain\BaseTipificacionesRepository;
use DateTime;
use Illuminate\Support\Facades\DB;

class EloquentBaseTipificacionesRepository implements BaseTipificacionesRepository
{
    public function procesarData(array $lista){

        DB::statement('DROP TABLE usraes.TMP_LIN_REN');
        DB::statement('CREATE TABLE usraes.TMP_LIN_REN(IMEI VARCHAR(20), IMSI VARCHAR(20), MSISDN VARCHAR(20))');

        foreach($lista as $row){

            $imei = substr($row["imei"], 0, 14);
            $imsi = substr($row["imsi"], 0, 16);
            $linea = $row["linea"];

            DB::table("usraes.TMP_LIN_REN")->insert([
                "IMEI" => $imei,
                "IMSI" => $imsi,
                "MSISDN" => $linea
            ]);
        }

        // Crear la tabla TMP_LIN_STA y realizar la búsqueda
        DB::statement('DROP TABLE usraes.TMP_LIN_STA');
        DB::statement('
            CREATE TABLE usraes.TMP_LIN_STA AS
            SELECT * FROM (
                SELECT DISTINCT 
                s.SUBSCRIPTION_ACCESS_NUMBER AS linea,
                s.ID_CARD_TYPE_VALUE AS Tipo_doc,
                s.ID_CARD_VALUE AS Nro_doc,
                s.CUSTOMER_FULL_NAME AS Nombre,
                s.CUSTOMER_TYPE,
                s.AGREEMENT_MODE,
                s.AGREEMENT_PRODUCT_OFFERING_DESC,
                s.SUBSCRIPTION_STATUS AS status,
                s.SUBSCRIPTION_SOURCE_SYSTEM_DESC,
                CASE s.SUBSCRIPTION_SOURCE_SYSTEM_DESC
                    WHEN \'IN\' THEN \'IN\'
                    WHEN \'BSCSIX\' THEN \'ONE\'
                    WHEN \'BSCS\' THEN \'ASIS\' END PLATAFOMA,
                s.SUBSCRIPTION_START_DATE,
                s.SUBSCRIPTION_END_DATE,
                s.CUSTOMER_ACCOUNT_SC ID_CLIENTE,
                s.CUSTOMER_ACCOUNT_DESC CUENTA,
                ROW_NUMBER() OVER(PARTITION BY s.SUBSCRIPTION_ACCESS_NUMBER ORDER BY s.SUBSCRIPTION_ACCESS_NUMBER ASC, s.SUBSCRIPTION_END_DATE DESC) RN
                FROM DWA.DW_M_SUBSCRIPTION s
                WHERE EXISTS (SELECT 1 FROM TMP_LIN_REN x WHERE x.MSISDN = s.SUBSCRIPTION_ACCESS_NUMBER)
            ) Z WHERE RN = \'1\'
        ');

        // Exportar el resultado
        $result = DB::select('
            SELECT 
            T.LINEA, T.TIPO_DOC, T.NRO_DOC, T.NOMBRE, T.CUSTOMER_TYPE, T.AGREEMENT_MODE, T.AGREEMENT_PRODUCT_OFFERING_DESC, T.STATUS, 
            T.SUBSCRIPTION_SOURCE_SYSTEM_DESC, T.PLATAFOMA, T.SUBSCRIPTION_START_DATE, T.SUBSCRIPTION_END_DATE, T.ID_CLIENTE, T.CUENTA,
            A.IMEI, A.IMSI 
            FROM usraes.TMP_LIN_STA T 
            INNER JOIN usraes.TMP_LIN_REN A ON T.LINEA = A.MSISDN
        ');

        return $result;
    }

    public function validarData(){

        $result = DB::select('
            SELECT A.MSISDN linea FROM usraes.TMP_LIN_REN A
            LEFT JOIN usraes.TMP_LIN_STA B ON A.MSISDN = B.LINEA
            WHERE B.LINEA IS NULL
        ');

        $count = count($result);

        return ['result' => $result, 'count' => $count];
    }
}
