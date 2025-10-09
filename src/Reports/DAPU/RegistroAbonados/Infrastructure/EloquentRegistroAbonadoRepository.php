<?php

namespace AMovil\Reports\DAPU\RegistroAbonados\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\DAPU\RegistroAbonados\Domain\RegistroAbonadoRepository;
use Exception;
use Illuminate\Support\Facades\DB;

class EloquentRegistroAbonadoRepository implements RegistroAbonadoRepository
{
    private $authService;
    private $userIdentifier;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    
    public function getByMsisdn(array $values) {
        return $this->getBy("msisdn", $values);
    }

    public function getByNumDocumento(array $values) {
        return $this->getBy("num_documento", $values);
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
        if($field === "msisdn"){
            $queryFilter = "MSISDN IN (SELECT value FROM USRAES.DAPU_LINEA_DNI_INPUT_{$this->userIdentifier})";
        } else if($field === "num_documento") {
            $queryFilter = "NRO_DOCUMENTO IN (SELECT value FROM USRAES.DAPU_LINEA_DNI_INPUT_{$this->userIdentifier})";
        } else {
            throw new Exception("El campo a filtrar no es valido");
        }

        $query = "SELECT /*+ PARALLEL(8) */
        MSISDN,
        NOMBRES,
        APE_PATERNO,
        APE_MATERNO,
        NRO_DOCUMENTO,
        RAZON_SOCIAL,
        IMSI,
        IMEI,
        FECHA_ACTUALIZACION
        FROM DWA.F_D_BASE_RA_HIST R
        WHERE {$queryFilter}
        AND ROWNUM <= 10
        ORDER BY FECHA_ACTUALIZACION
        ";

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
