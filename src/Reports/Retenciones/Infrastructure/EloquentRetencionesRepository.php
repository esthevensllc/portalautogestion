<?php

namespace AMovil\Reports\Retenciones\Infrastructure;

use AMovil\Auth\AccessControl\Domain\AuthService;
use AMovil\Reports\Retenciones\Domain\RetencionesRepository;
use DateTime;
use Illuminate\Support\Facades\DB;

class EloquentRetencionesRepository implements RetencionesRepository
{
    private $authService;
    private $userIdentifier;
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
        $this->userIdentifier = $authService->getUserIdentifier();
    }

    public function saveAll($data)
    {
        DB::connection('mysql')->beginTransaction();

        try {       
            foreach ($data as $row) {
                DB::connection("mysql")
                ->table("retenciones_red.filtros_postpago_final")
                ->insert([
                    "dia_load" => $row['dia_load'],
                    "key_hash" => DB::connection('mysql')->raw($row['key_hash']),
                    "pk" => $row['pk'],
                    "operador" => $row['operador'],
                    "flag_dpto" => $row['flag_dpto'],
                    "flag" => $row['flag'],
                    "target" => $row['target'],
                    "decil" => $row['decil'],
                    "callcenter" => $row['callcenter'],
                    "final" => $row['final']
                ]);
            }
            DB::connection('mysql')->commit(); // Confirmar transacción
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback(); // Revertir transacción en caso de error
            return response()->json(['error' => 'Error al guardar los datos.'], 500);
        }
    }

    public function saveAllRutas($data)
    {
        DB::connection('mysql')->beginTransaction();
        try {       
            foreach ($data as $row) {
                DB::connection("mysql")
                ->table("retenciones_red.rutas_cc_postpago_final")
                ->insert([
                    "dia_load" => $row['dia_load'],
                    "key_hash" => DB::connection('mysql')->raw($row['key_hash']),
                    "callcenter" => $row['callcenter'],
                    "operadorfinal" => $row['operadorfinal'],
                    "fileName" => $row['fileName'],
                    "pathName" => $row['pathName']
                ]);
            }
            DB::connection('mysql')->commit(); // Confirmar transacción
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback(); // Revertir transacción en caso de error
            return response()->json(['error' => 'Error al guardar los datos.'], 500);
        }
    }
}
