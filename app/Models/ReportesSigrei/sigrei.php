<?php
namespace App\Models\ReportesSigrei;

use Illuminate\Database\Eloquent\Model;
class sigrei extends Model {

    //protected $connection = 'oracle_dwo';
    protected $table = 'reporte_sigrei_tmp';
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'contador',
	'imei',
        'estado_del_reporte',
        'fecha_reporte'
    ];
}