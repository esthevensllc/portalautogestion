<?php

namespace AMovil\Reports\RepDetConsumo\Controllers;

class DetalleLLamadasController
{
    public function entrantes(){
        $data = [
            'title' => 'Detalle llamadas / entrantes'
        ];
        return view('backpack::rep_det_consumo.detalle_llamadas', compact('data'));
    }

    public function salientes(){
        $data = [
            'title' => 'Detalle llamadas / salientes'
        ];
        return view('backpack::rep_det_consumo.detalle_llamadas', compact('data'));
    }

    public function entrantes_salientes(){
        $data = [
            'title' => 'Detalle llamadas / entrantes y entrantes'
        ];
        return view('backpack::rep_det_consumo.detalle_llamadas', compact('data'));
    }
}
