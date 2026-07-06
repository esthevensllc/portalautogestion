<?php

namespace AMovil\Reports\IMRIMF\Controllers;

use DateTime;
use Illuminate\Http\Request;

class ImfFijaController
{

    public function __construct()
    {

    }
    
    public function view()
    {
        $config = [
            'title' => 'Consulta de Acciones IMF FIJA',
            'title_percent' => 'Porcentaje de uso de IMF'
        ];
        return view("imrimf.imf_fija", compact("config"));
    }

}
