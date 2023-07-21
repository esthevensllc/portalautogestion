<?php

namespace AMovil\Reports\DAPU\TramiteConsulta\Infrastructure\Repository;

use AMovil\Reports\DAPU\TramiteConsulta\Domain\TramiteConsultaRepository;
use Illuminate\Support\Facades\DB;

class EloquentTramiteConsultaRepository implements TramiteConsultaRepository
{
    public function getReporteF1(array $input)
    {
        $data = [];
        foreach($input as $row){
            $data[] = [
                'anio' => $row[0],
                'mes' => $row[1],
                'linea' => $row[2],
                'servicioinvolucrado' => $row[3],
                'canal_atencion' => $row[4],
                'tipo_tramite' => $row[5],
                'total_tramites' => $row[6],
            ];
        }

        DB::table("USRAES.DAPU_TRAMITE_CONSULTA_FORMATO1")->truncate();
        DB::table("USRAES.DAPU_TRAMITE_CONSULTA_FORMATO1")->insert($data);
        
        $periodos = DB::table("USRAES.DAPU_TRAMITE_CONSULTA_FORMATO1")->select('anio', 'mes')
        ->groupBy('anio')->groupBy('mes')
        ->orderBy('anio')->orderBy('mes')
        ->get();

        $queries = [];
        foreach ($periodos as $row) {
            $str_periodo = $row->anio.str_pad($row->mes, 2, "0", STR_PAD_LEFT);
            $queries[] = "SELECT Y,MES,DEPARTAMENTO,SERVICIOINVOLUCRADO
            ,CANAL_ATENCION,TIPO_TRAMITE,SUM(TOTAL_TRAMITES) TOTAL_TRAMITES
            FROM 
            (
                SELECT 
                X.Y,X.MES,Y.DEPARTAMENTO_HOME DEPARTAMENTO,X.SERVICIOINVOLUCRADO,X.NUMERO
                ,X.CANAL_ATENCION,X.TIPO_TRAMITE,X.TOTAL_TRAMITES
                FROM
                (
                SELECT X.Y,X.MES,Y.SEMANA,X.SERVICIOINVOLUCRADO,X.NUMERO
                ,X.CANAL_ATENCION,X.TIPO_TRAMITE,X.TOTAL_TRAMITES
                FROM
                (
                    SELECT TO_CHAR(ANIO) Y,MES,TO_CHAR(LINEA) NUMERO,SERVICIOINVOLUCRADO,
                    CANAL_ATENCION,TIPO_TRAMITE,TOTAL_TRAMITES
                    FROM USRAES.DAPU_TRAMITE_CONSULTA_FORMATO1
                    WHERE (LINEA LIKE '519%' AND LENGTH(LINEA)=11)
                    AND ANIO = $row->anio AND MES = $row->mes
                )X
                LEFT JOIN USRAES.WEEK_MONTH Y ON X.MES=Y.MES
                )X
                LEFT JOIN
                DWS.SA_SITEHOMEWORK_BASE_ACTIVA PARTITION (P_{$str_periodo}) Y 
                ON X.Y=Y.Y AND X.SEMANA=Y.SEMANA AND X.NUMERO=Y.FONO
            )X
            GROUP BY Y,MES,DEPARTAMENTO,SERVICIOINVOLUCRADO
            ,CANAL_ATENCION,TIPO_TRAMITE";
        }

        $sql_reporte = implode(" UNION ALL ", $queries);
        return DB::select(DB::raw($sql_reporte));
    }

    public function getReporteF2(array $input)
    {
        $data = [];
        foreach($input as $row){
            $data[] = [
                'anio' => $row[0],
                'mes' => $row[1],
                'linea' => $row[2],
                'servicioinvolucrado' => $row[3],
                'canal_atencion' => $row[4],
                'tipo_tramite' => $row[5],
                'estado_tramite' => $row[6],
                'total_tramites' => $row[7],
            ];
        }
        DB::table("USRAES.DAPU_TRAMITE_CONSULTA_FORMATO2")->truncate();
        DB::table("USRAES.DAPU_TRAMITE_CONSULTA_FORMATO2")->insert($data);
        
        $periodos = DB::table("USRAES.DAPU_TRAMITE_CONSULTA_FORMATO2")->select('anio', 'mes')
        ->groupBy('anio')->groupBy('mes')
        ->orderBy('anio')->orderBy('mes')
        ->get();

        $queries = [];
        foreach ($periodos as $row) {
            $str_periodo = $row->anio.str_pad($row->mes, 2, "0", STR_PAD_LEFT);
            $queries[] = "SELECT Y,MES,DEPARTAMENTO,SERVICIOINVOLUCRADO
            ,CANAL_ATENCION,TIPO_TRAMITE,ESTADO_TRAMITE,SUM(TOTAL_TRAMITES) TOTAL_TRAMITES
            FROM 
            (
              SELECT 
              X.Y,X.MES,Y.DEPARTAMENTO_HOME DEPARTAMENTO,X.SERVICIOINVOLUCRADO,X.NUMERO
              ,X.CANAL_ATENCION,X.TIPO_TRAMITE,X.ESTADO_TRAMITE,X.TOTAL_TRAMITES
              FROM
              (
                SELECT X.Y,X.MES,Y.SEMANA,X.SERVICIOINVOLUCRADO,X.NUMERO
                ,X.CANAL_ATENCION,X.TIPO_TRAMITE,X.ESTADO_TRAMITE,X.TOTAL_TRAMITES
                FROM
                (
                  SELECT TO_CHAR(ANIO) Y,MES,TO_CHAR(LINEA) NUMERO,SERVICIOINVOLUCRADO,CANAL_ATENCION,TIPO_TRAMITE,ESTADO_TRAMITE,
                  TOTAL_TRAMITES
                  FROM USRAES.DAPU_TRAMITE_CONSULTA_FORMATO2 
                  WHERE (LINEA LIKE '519%' AND LENGTH(LINEA)=11)
                  AND ANIO = $row->anio AND MES = $row->mes
                )X
                LEFT JOIN USRAES.WEEK_MONTH Y ON X.MES=Y.MES
              )X
              LEFT JOIN
              DWS.SA_SITEHOMEWORK_BASE_ACTIVA PARTITION (P_{$str_periodo}) Y 
              ON X.Y=Y.Y AND X.SEMANA=Y.SEMANA AND X.NUMERO=Y.FONO
            )X GROUP BY Y,MES,DEPARTAMENTO,SERVICIOINVOLUCRADO
            ,CANAL_ATENCION,TIPO_TRAMITE,ESTADO_TRAMITE";
        }

        $sql_reporte = implode(" UNION ALL ", $queries);
        return DB::select(DB::raw($sql_reporte));
    }
}
