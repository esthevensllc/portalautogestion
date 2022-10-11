<?php

namespace App\Http\Controllers\RepDetConsumo;

class DetalleConsumoController
{
    public function detallado(){
        $data = [
            'title' => 'Detalle consumo / detallado'
        ];
        return view('backpack::rep_det_consumo.detalle_llamadas', compact('data'));
    }

    public function consolidado(){
        $data = [
            'title' => 'Detalle consumo / consolidado'
        ];
        return view('backpack::rep_det_consumo.detalle_llamadas', compact('data'));
    }
}
